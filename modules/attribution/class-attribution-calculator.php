<?php
/**
 * Attribution Calculator
 *
 * Processes conversions and calculates attribution.
 *
 * @package PeanutSuite\Attribution
 */

namespace PeanutSuite\Attribution;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Attribution calculation engine.
 */
class Attribution_Calculator {

    /**
     * Default lookback window in days.
     */
    const DEFAULT_LOOKBACK_DAYS = 30;

    /**
     * Calculate attribution for a conversion.
     *
     * @param int   $conversion_id Conversion ID.
     * @param array $models        Models to calculate (empty = all).
     * @return array
     */
    public static function calculate_for_conversion(int $conversion_id, array $models = []): array {
        $conversion = Attribution_Database::get_conversion($conversion_id);

        if (!$conversion) {
            return ['error' => 'Conversion not found'];
        }

        // Get all touches for this visitor before conversion
        $lookback_date = gmdate('Y-m-d H:i:s', strtotime('-' . self::DEFAULT_LOOKBACK_DAYS . ' days', strtotime($conversion['converted_at'])));

        $touches = Attribution_Database::get_visitor_touches($conversion['visitor_id'], [
            'before' => $conversion['converted_at'],
            'after' => $lookback_date,
        ]);

        if (empty($touches)) {
            return ['error' => 'No touches found for this conversion'];
        }

        // Link touches to conversion
        $touch_ids = array_column($touches, 'id');
        Attribution_Database::link_touches_to_conversion($conversion_id, $touch_ids);

        // Calculate attribution
        $models_to_calculate = empty($models) ? array_keys(Attribution_Models::MODELS) : $models;
        $results = [];

        foreach ($models_to_calculate as $model) {
            $credits = Attribution_Models::calculate($model, $touches, $conversion['converted_at']);

            foreach ($credits as $touch_id => $credit) {
                if ($credit > 0) {
                    Attribution_Database::save_attribution_result($conversion_id, $touch_id, $model, $credit);
                }
            }

            $results[$model] = $credits;
        }

        return [
            'conversion_id' => $conversion_id,
            'touches_count' => count($touches),
            'results' => $results,
        ];
    }

    /**
     * Process all unattributed conversions.
     *
     * @param int $limit Maximum conversions to process.
     * @return array
     */
    public static function process_pending_conversions(int $limit = 100): array {
        global $wpdb;

        $conversions_table = Attribution_Database::get_conversions_table();
        $results_table = Attribution_Database::get_results_table();

        // Find conversions without attribution
        $pending = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.id FROM {$conversions_table} c
                LEFT JOIN {$results_table} r ON c.id = r.conversion_id
                WHERE r.id IS NULL
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        $processed = 0;
        $errors = 0;

        foreach ($pending as $row) {
            $result = self::calculate_for_conversion((int) $row['id']);

            if (isset($result['error'])) {
                $errors++;
            } else {
                $processed++;
            }
        }

        return [
            'processed' => $processed,
            'errors' => $errors,
            'pending' => count($pending),
        ];
    }

    /**
     * Record a touch from visitor event.
     *
     * @param string $visitor_id Visitor identifier.
     * @param array  $event_data Event data.
     * @return int|false Touch ID or false.
     */
    public static function record_touch_from_event(string $visitor_id, array $event_data) {
        // Determine channel from UTM params or referrer
        $channel = self::determine_channel(
            $event_data['referrer'] ?? null,
            [
                'utm_source' => $event_data['utm_source'] ?? null,
                'utm_medium' => $event_data['utm_medium'] ?? null,
            ]
        );

        $touch_data = [
            'visitor_id' => $visitor_id,
            'session_id' => $event_data['session_id'] ?? null,
            'touch_type' => $event_data['event_type'] ?? 'pageview',
            'channel' => $channel,
            'source' => $event_data['utm_source'] ?? null,
            'medium' => $event_data['utm_medium'] ?? null,
            'campaign' => $event_data['utm_campaign'] ?? null,
            'content' => $event_data['utm_content'] ?? null,
            'term' => $event_data['utm_term'] ?? null,
            'landing_page' => $event_data['page_url'] ?? null,
            'referrer' => $event_data['referrer'] ?? null,
        ];

        return Attribution_Database::record_touch($touch_data);
    }

    /**
     * Record a conversion from webhook or event.
     *
     * @param string $visitor_id      Visitor identifier.
     * @param string $conversion_type Conversion type.
     * @param array  $data            Additional data.
     * @return array
     */
    public static function record_conversion(string $visitor_id, string $conversion_type, array $data = []): array {
        $conversion_data = [
            'visitor_id' => $visitor_id,
            'conversion_type' => $conversion_type,
            'conversion_value' => $data['value'] ?? 0.00,
            'source' => $data['source'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'customer_email' => $data['email'] ?? null,
            'customer_name' => $data['name'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ];

        $conversion_id = Attribution_Database::record_conversion($conversion_data);

        if (!$conversion_id) {
            return ['error' => 'Failed to record conversion'];
        }

        // Calculate attribution immediately
        $result = self::calculate_for_conversion($conversion_id);

        // Fire action
        do_action('peanut_conversion_created', $conversion_id, $visitor_id);
        do_action('peanut_attribution_calculated', $conversion_id, $result);

        return [
            'conversion_id' => $conversion_id,
            'attribution' => $result,
        ];
    }

    /**
     * Determine traffic channel from referrer and UTM params.
     *
     * @param string|null $referrer   Referrer URL.
     * @param array       $utm_params UTM parameters.
     * @return string
     */
    public static function determine_channel(?string $referrer, array $utm_params = []): string {
        // Check UTM medium first
        if (!empty($utm_params['utm_medium'])) {
            $medium = strtolower($utm_params['utm_medium']);

            if (in_array($medium, ['cpc', 'ppc', 'paid', 'paidsearch'], true)) {
                return 'Paid Search';
            }
            if (in_array($medium, ['display', 'banner', 'cpm'], true)) {
                return 'Display';
            }
            if (in_array($medium, ['social', 'social-media', 'social-paid'], true)) {
                return !empty($utm_params['utm_source']) && stripos($utm_params['utm_source'], 'paid') !== false
                    ? 'Paid Social'
                    : 'Social';
            }
            if (in_array($medium, ['email', 'e-mail', 'newsletter'], true)) {
                return 'Email';
            }
            if (in_array($medium, ['affiliate', 'partner', 'referral'], true)) {
                return 'Affiliate';
            }
            if ($medium === 'organic') {
                return 'Organic Search';
            }
        }

        // Check UTM source
        if (!empty($utm_params['utm_source'])) {
            $source = strtolower($utm_params['utm_source']);

            if (in_array($source, ['google', 'bing', 'yahoo', 'duckduckgo'], true)) {
                return 'Organic Search';
            }
            if (in_array($source, ['facebook', 'twitter', 'linkedin', 'instagram', 'tiktok'], true)) {
                return 'Social';
            }
        }

        // No referrer = Direct
        if (empty($referrer)) {
            return 'Direct';
        }

        // Parse referrer
        $ref_host = wp_parse_url($referrer, PHP_URL_HOST);
        if (!$ref_host) {
            return 'Direct';
        }

        $ref_host = strtolower($ref_host);

        // Search engines
        $search_engines = ['google', 'bing', 'yahoo', 'duckduckgo', 'baidu', 'yandex'];
        foreach ($search_engines as $engine) {
            if (strpos($ref_host, $engine) !== false) {
                return 'Organic Search';
            }
        }

        // Social networks
        $social_networks = [
            'facebook.com', 'fb.com', 'twitter.com', 'x.com', 't.co',
            'linkedin.com', 'instagram.com', 'pinterest.com',
            'youtube.com', 'tiktok.com', 'reddit.com',
        ];
        foreach ($social_networks as $domain) {
            if (strpos($ref_host, str_replace('.com', '', $domain)) !== false) {
                return 'Social';
            }
        }

        return 'Referral';
    }

    /**
     * Get attribution report.
     *
     * @param string $model Attribution model.
     * @param array  $args  Report arguments.
     * @return array
     */
    public static function get_report(string $model, array $args = []): array {
        $defaults = [
            'date_from' => gmdate('Y-m-d', strtotime('-30 days')),
            'date_to' => gmdate('Y-m-d'),
        ];

        $args = wp_parse_args($args, $defaults);

        // Channel performance
        $channels = Attribution_Database::get_channel_performance($model, $args);

        // Summary stats
        $stats = Attribution_Database::get_stats();

        return [
            'model' => $model,
            'model_name' => Attribution_Models::MODELS[$model] ?? $model,
            'model_description' => Attribution_Models::get_description($model),
            'date_range' => [
                'from' => $args['date_from'],
                'to' => $args['date_to'],
            ],
            'channels' => $channels,
            'stats' => $stats,
        ];
    }

    /**
     * Compare all models.
     *
     * @param array $args Report arguments.
     * @return array
     */
    public static function compare_models(array $args = []): array {
        $defaults = [
            'date_from' => gmdate('Y-m-d', strtotime('-30 days')),
            'date_to' => gmdate('Y-m-d'),
        ];

        $args = wp_parse_args($args, $defaults);

        $comparison = [];

        foreach (array_keys(Attribution_Models::MODELS) as $model) {
            $comparison[$model] = Attribution_Database::get_channel_performance($model, $args);
        }

        return [
            'date_range' => [
                'from' => $args['date_from'],
                'to' => $args['date_to'],
            ],
            'models' => Attribution_Models::MODELS,
            'comparison' => $comparison,
        ];
    }
}

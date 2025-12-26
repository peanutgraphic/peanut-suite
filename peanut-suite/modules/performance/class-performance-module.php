<?php
/**
 * Performance Module - Core Web Vitals
 *
 * Monitor PageSpeed Insights and Core Web Vitals metrics.
 * Features:
 * - Google PageSpeed Insights API integration
 * - Track LCP, FID/INP, CLS scores over time
 * - Historical performance tracking
 * - Scheduled daily checks
 * - Performance alerts
 *
 * Pro tier feature.
 */

namespace PeanutSuite\Performance;

if (!defined('ABSPATH')) {
    exit;
}

class Performance_Module {

    /**
     * Module instance
     */
    private static ?Performance_Module $instance = null;

    /**
     * Settings
     */
    private array $settings = [];

    /**
     * Get singleton instance
     */
    public static function instance(): Performance_Module {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('peanut_performance_settings', $this->get_defaults());
    }

    /**
     * Initialize module
     */
    public function init(): void {
        // Register API routes
        add_action('rest_api_init', [$this, 'register_routes']);

        // Schedule cron jobs
        add_action('init', [$this, 'schedule_cron']);
        add_action('peanut_performance_check', [$this, 'run_scheduled_check']);

        // Dashboard stats
        add_filter('peanut_dashboard_stats', [$this, 'add_dashboard_stats'], 10, 2);
    }

    /**
     * Get default settings
     */
    private function get_defaults(): array {
        return [
            'api_key' => '',
            'strategy' => 'mobile', // mobile or desktop
            'auto_check_enabled' => false,
            'check_frequency' => 'daily', // daily, weekly
            'urls' => [home_url('/')],
            'alert_enabled' => false,
            'alert_threshold' => 50,
            'alert_email' => get_option('admin_email'),
        ];
    }

    /**
     * Schedule cron events
     */
    public function schedule_cron(): void {
        if (!$this->settings['auto_check_enabled']) {
            wp_clear_scheduled_hook('peanut_performance_check');
            return;
        }

        $frequency = $this->settings['check_frequency'] === 'weekly' ? 'weekly' : 'daily';

        if (!wp_next_scheduled('peanut_performance_check')) {
            wp_schedule_event(time(), $frequency, 'peanut_performance_check');
        }
    }

    /**
     * Get table name
     */
    private function table_name(): string {
        global $wpdb;
        return $wpdb->prefix . PEANUT_TABLE_PREFIX . 'webvitals';
    }

    /**
     * Create database tables
     */
    public static function create_tables(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . PEANUT_TABLE_PREFIX . 'webvitals';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            url varchar(500) NOT NULL,
            strategy enum('mobile','desktop') DEFAULT 'mobile',
            overall_score int DEFAULT NULL,
            performance_score int DEFAULT NULL,
            accessibility_score int DEFAULT NULL,
            best_practices_score int DEFAULT NULL,
            seo_score int DEFAULT NULL,
            lcp_ms decimal(10,2) DEFAULT NULL,
            fid_ms decimal(10,2) DEFAULT NULL,
            inp_ms decimal(10,2) DEFAULT NULL,
            cls decimal(10,4) DEFAULT NULL,
            fcp_ms decimal(10,2) DEFAULT NULL,
            ttfb_ms decimal(10,2) DEFAULT NULL,
            tti_ms decimal(10,2) DEFAULT NULL,
            tbt_ms decimal(10,2) DEFAULT NULL,
            speed_index decimal(10,2) DEFAULT NULL,
            opportunities text,
            diagnostics text,
            raw_response longtext,
            checked_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY url (url(191)),
            KEY strategy (strategy),
            KEY checked_at (checked_at),
            KEY overall_score (overall_score)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Register REST routes
     */
    public function register_routes(): void {
        register_rest_route(PEANUT_API_NAMESPACE, '/performance/settings', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_settings'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'update_settings'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
        ]);

        register_rest_route(PEANUT_API_NAMESPACE, '/performance/scores', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'get_scores'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route(PEANUT_API_NAMESPACE, '/performance/history', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'get_history'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route(PEANUT_API_NAMESPACE, '/performance/check', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'run_check'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route(PEANUT_API_NAMESPACE, '/performance/urls', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_urls'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'add_url'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
        ]);

        register_rest_route(PEANUT_API_NAMESPACE, '/performance/urls/(?P<id>\d+)', [
            'methods' => \WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_url'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * Get settings via API
     */
    public function get_settings(\WP_REST_Request $request): \WP_REST_Response {
        // Don't expose the full API key
        $settings = $this->settings;
        if (!empty($settings['api_key'])) {
            $settings['api_key_set'] = true;
            $settings['api_key'] = '••••' . substr($settings['api_key'], -4);
        } else {
            $settings['api_key_set'] = false;
            $settings['api_key'] = '';
        }

        return new \WP_REST_Response($settings, 200);
    }

    /**
     * Update settings via API
     */
    public function update_settings(\WP_REST_Request $request): \WP_REST_Response {
        $data = $request->get_json_params();

        // Preserve existing API key if not changed
        $new_api_key = $data['api_key'] ?? '';
        if (empty($new_api_key) || strpos($new_api_key, '••••') === 0) {
            $data['api_key'] = $this->settings['api_key'];
        }

        $settings = wp_parse_args($data, $this->get_defaults());

        // Sanitize
        $settings['api_key'] = sanitize_text_field($settings['api_key']);
        $settings['strategy'] = in_array($settings['strategy'], ['mobile', 'desktop']) ? $settings['strategy'] : 'mobile';
        $settings['auto_check_enabled'] = (bool) ($settings['auto_check_enabled'] ?? false);
        $settings['check_frequency'] = in_array($settings['check_frequency'], ['daily', 'weekly']) ? $settings['check_frequency'] : 'daily';
        $settings['urls'] = array_filter(array_map('esc_url_raw', $settings['urls'] ?? [home_url('/')]));
        $settings['alert_enabled'] = (bool) ($settings['alert_enabled'] ?? false);
        $settings['alert_threshold'] = min(100, max(0, absint($settings['alert_threshold'])));
        $settings['alert_email'] = sanitize_email($settings['alert_email']);

        update_option('peanut_performance_settings', $settings);
        $this->settings = $settings;

        // Reschedule cron if needed
        $this->schedule_cron();

        return new \WP_REST_Response(['success' => true, 'settings' => $settings], 200);
    }

    /**
     * Get latest scores for all tracked URLs
     */
    public function get_scores(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $table = $this->table_name();
        $strategy = $request->get_param('strategy') ?? $this->settings['strategy'];

        // Get latest score for each URL
        $scores = $wpdb->get_results($wpdb->prepare(
            "SELECT w1.*
             FROM $table w1
             INNER JOIN (
                 SELECT url, MAX(checked_at) as max_checked
                 FROM $table
                 WHERE strategy = %s
                 GROUP BY url
             ) w2 ON w1.url = w2.url AND w1.checked_at = w2.max_checked
             WHERE w1.strategy = %s
             ORDER BY w1.overall_score ASC",
            $strategy,
            $strategy
        ), ARRAY_A);

        // Decode JSON fields
        foreach ($scores as &$score) {
            $score['opportunities'] = json_decode($score['opportunities'] ?? '[]', true);
            $score['diagnostics'] = json_decode($score['diagnostics'] ?? '[]', true);
            unset($score['raw_response']); // Don't send raw response to frontend
        }

        // Calculate averages
        $averages = [
            'overall' => 0,
            'performance' => 0,
            'accessibility' => 0,
            'best_practices' => 0,
            'seo' => 0,
            'lcp' => 0,
            'cls' => 0,
            'fid' => 0,
        ];

        if (!empty($scores)) {
            foreach ($scores as $score) {
                $averages['overall'] += (float) ($score['overall_score'] ?? 0);
                $averages['performance'] += (float) ($score['performance_score'] ?? 0);
                $averages['accessibility'] += (float) ($score['accessibility_score'] ?? 0);
                $averages['best_practices'] += (float) ($score['best_practices_score'] ?? 0);
                $averages['seo'] += (float) ($score['seo_score'] ?? 0);
                $averages['lcp'] += (float) ($score['lcp_ms'] ?? 0);
                $averages['cls'] += (float) ($score['cls'] ?? 0);
                $averages['fid'] += (float) ($score['fid_ms'] ?? $score['inp_ms'] ?? 0);
            }

            $count = count($scores);
            foreach ($averages as $key => $value) {
                $averages[$key] = round($value / $count, 2);
            }
        }

        return new \WP_REST_Response([
            'scores' => $scores,
            'averages' => $averages,
            'strategy' => $strategy,
        ], 200);
    }

    /**
     * Get historical scores for a URL
     */
    public function get_history(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $table = $this->table_name();

        $url = $request->get_param('url') ?? home_url('/');
        $days = min(90, max(7, absint($request->get_param('days') ?? 30)));
        $strategy = $request->get_param('strategy') ?? $this->settings['strategy'];

        $history = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE(checked_at) as date,
                AVG(overall_score) as overall_score,
                AVG(performance_score) as performance_score,
                AVG(lcp_ms) as lcp_ms,
                AVG(cls) as cls,
                AVG(COALESCE(fid_ms, inp_ms)) as fid_ms
             FROM $table
             WHERE url = %s
             AND strategy = %s
             AND checked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(checked_at)
             ORDER BY date ASC",
            $url,
            $strategy,
            $days
        ), ARRAY_A);

        return new \WP_REST_Response([
            'history' => $history,
            'url' => $url,
            'days' => $days,
        ], 200);
    }

    /**
     * Run a performance check
     */
    public function run_check(\WP_REST_Request $request): \WP_REST_Response {
        $url = $request->get_param('url') ?? home_url('/');
        $strategy = $request->get_param('strategy') ?? $this->settings['strategy'];

        $result = $this->check_url($url, $strategy);

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => $result->get_error_message(),
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'score' => $result,
        ], 200);
    }

    /**
     * Get tracked URLs
     */
    public function get_urls(\WP_REST_Request $request): \WP_REST_Response {
        return new \WP_REST_Response([
            'urls' => $this->settings['urls'] ?? [home_url('/')],
        ], 200);
    }

    /**
     * Add a URL to track
     */
    public function add_url(\WP_REST_Request $request): \WP_REST_Response {
        $url = $request->get_param('url');

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new \WP_REST_Response(['error' => 'Invalid URL'], 400);
        }

        $urls = $this->settings['urls'] ?? [];
        if (!in_array($url, $urls)) {
            $urls[] = esc_url_raw($url);
            $this->settings['urls'] = $urls;
            update_option('peanut_performance_settings', $this->settings);
        }

        return new \WP_REST_Response(['success' => true, 'urls' => $urls], 200);
    }

    /**
     * Delete a URL from tracking
     */
    public function delete_url(\WP_REST_Request $request): \WP_REST_Response {
        $id = (int) $request->get_param('id');
        $urls = $this->settings['urls'] ?? [];

        if (isset($urls[$id])) {
            unset($urls[$id]);
            $this->settings['urls'] = array_values($urls);
            update_option('peanut_performance_settings', $this->settings);
        }

        return new \WP_REST_Response(['success' => true, 'urls' => $this->settings['urls']], 200);
    }

    /**
     * Check a URL with PageSpeed Insights API
     */
    public function check_url(string $url, string $strategy = 'mobile'): array|\WP_Error {
        $api_key = $this->settings['api_key'];

        // Build API URL
        $api_url = add_query_arg([
            'url' => urlencode($url),
            'strategy' => $strategy,
            'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
        ], 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed');

        if (!empty($api_key)) {
            $api_url = add_query_arg('key', $api_key, $api_url);
        }

        // Make API request
        $response = wp_remote_get($api_url, [
            'timeout' => 60,
            'sslverify' => true,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);
        if ($status !== 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $message = $body['error']['message'] ?? 'API request failed';
            return new \WP_Error('api_error', $message);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data) {
            return new \WP_Error('parse_error', 'Failed to parse API response');
        }

        // Extract scores
        $lighthouse = $data['lighthouseResult'] ?? [];
        $categories = $lighthouse['categories'] ?? [];
        $audits = $lighthouse['audits'] ?? [];

        $score_data = [
            'url' => $url,
            'strategy' => $strategy,
            'overall_score' => round(($categories['performance']['score'] ?? 0) * 100),
            'performance_score' => round(($categories['performance']['score'] ?? 0) * 100),
            'accessibility_score' => round(($categories['accessibility']['score'] ?? 0) * 100),
            'best_practices_score' => round(($categories['best-practices']['score'] ?? 0) * 100),
            'seo_score' => round(($categories['seo']['score'] ?? 0) * 100),
            'lcp_ms' => $audits['largest-contentful-paint']['numericValue'] ?? null,
            'fid_ms' => $audits['max-potential-fid']['numericValue'] ?? null,
            'inp_ms' => $audits['experimental-interaction-to-next-paint']['numericValue'] ?? null,
            'cls' => $audits['cumulative-layout-shift']['numericValue'] ?? null,
            'fcp_ms' => $audits['first-contentful-paint']['numericValue'] ?? null,
            'ttfb_ms' => $audits['server-response-time']['numericValue'] ?? null,
            'tti_ms' => $audits['interactive']['numericValue'] ?? null,
            'tbt_ms' => $audits['total-blocking-time']['numericValue'] ?? null,
            'speed_index' => $audits['speed-index']['numericValue'] ?? null,
        ];

        // Extract opportunities
        $opportunities = [];
        foreach ($audits as $key => $audit) {
            if (isset($audit['details']['type']) && $audit['details']['type'] === 'opportunity') {
                $opportunities[] = [
                    'id' => $key,
                    'title' => $audit['title'] ?? '',
                    'description' => $audit['description'] ?? '',
                    'savings_ms' => $audit['details']['overallSavingsMs'] ?? 0,
                    'savings_bytes' => $audit['details']['overallSavingsBytes'] ?? 0,
                ];
            }
        }

        // Extract diagnostics
        $diagnostics = [];
        foreach ($audits as $key => $audit) {
            if (isset($audit['details']['type']) && $audit['details']['type'] === 'table') {
                if (($audit['score'] ?? 1) < 0.9) {
                    $diagnostics[] = [
                        'id' => $key,
                        'title' => $audit['title'] ?? '',
                        'description' => $audit['description'] ?? '',
                        'score' => $audit['score'] ?? null,
                    ];
                }
            }
        }

        $score_data['opportunities'] = json_encode(array_slice($opportunities, 0, 10));
        $score_data['diagnostics'] = json_encode(array_slice($diagnostics, 0, 10));
        $score_data['raw_response'] = $body;
        $score_data['checked_at'] = current_time('mysql');

        // Save to database
        global $wpdb;
        $wpdb->insert($this->table_name(), $score_data);

        // Check for alerts
        if ($this->settings['alert_enabled'] && $score_data['overall_score'] < $this->settings['alert_threshold']) {
            $this->send_alert($score_data);
        }

        // Remove raw response for return
        unset($score_data['raw_response']);
        $score_data['opportunities'] = $opportunities;
        $score_data['diagnostics'] = $diagnostics;

        return $score_data;
    }

    /**
     * Run scheduled check for all URLs
     */
    public function run_scheduled_check(): void {
        $urls = $this->settings['urls'] ?? [home_url('/')];
        $strategy = $this->settings['strategy'];

        foreach ($urls as $url) {
            $this->check_url($url, $strategy);

            // Small delay between checks
            sleep(2);
        }
    }

    /**
     * Send performance alert
     */
    private function send_alert(array $score): void {
        $email = $this->settings['alert_email'] ?? get_option('admin_email');
        $site_name = get_bloginfo('name');

        $subject = sprintf(
            __('[%s] Low Performance Score Alert: %d%%', 'peanut-suite'),
            $site_name,
            $score['overall_score']
        );

        $message = sprintf(
            __("A performance check for %s scored below your threshold.\n\n", 'peanut-suite'),
            $score['url']
        );

        $message .= sprintf(__("Performance Score: %d%%\n", 'peanut-suite'), $score['performance_score']);
        $message .= sprintf(__("Accessibility Score: %d%%\n", 'peanut-suite'), $score['accessibility_score']);
        $message .= sprintf(__("Best Practices Score: %d%%\n", 'peanut-suite'), $score['best_practices_score']);
        $message .= sprintf(__("SEO Score: %d%%\n\n", 'peanut-suite'), $score['seo_score']);

        $message .= __("Core Web Vitals:\n", 'peanut-suite');
        $message .= sprintf(__("- LCP: %.2f ms\n", 'peanut-suite'), $score['lcp_ms']);
        $message .= sprintf(__("- CLS: %.4f\n", 'peanut-suite'), $score['cls']);
        $message .= sprintf(__("- FID: %.2f ms\n\n", 'peanut-suite'), $score['fid_ms'] ?? $score['inp_ms']);

        $message .= sprintf(
            __("View full report: %s", 'peanut-suite'),
            admin_url('admin.php?page=peanut-performance')
        );

        wp_mail($email, $subject, $message);
    }

    /**
     * Add stats to dashboard
     */
    public function add_dashboard_stats(array $stats, string $period): array {
        global $wpdb;
        $table = $this->table_name();

        // Get latest average score
        $latest = $wpdb->get_row(
            "SELECT AVG(overall_score) as avg_score, COUNT(DISTINCT url) as url_count
             FROM (
                 SELECT url, overall_score
                 FROM $table w1
                 WHERE checked_at = (
                     SELECT MAX(checked_at) FROM $table w2 WHERE w2.url = w1.url
                 )
             ) latest",
            ARRAY_A
        );

        $stats['performance_score'] = round((float) ($latest['avg_score'] ?? 0));
        $stats['performance_urls'] = (int) ($latest['url_count'] ?? 0);

        return $stats;
    }

    /**
     * Deactivate - clean up cron
     */
    public function deactivate(): void {
        wp_clear_scheduled_hook('peanut_performance_check');
    }
}

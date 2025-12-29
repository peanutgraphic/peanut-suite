<?php
/**
 * FormFlow Event Processor
 *
 * Processes queued FormFlow events for analytics and reporting.
 *
 * @package Peanut_Suite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FormFlow_Processor {

    /**
     * Process pending events
     *
     * Called periodically to aggregate data and generate reports.
     */
    public static function process_pending(): void {
        // Update daily aggregates
        self::update_daily_aggregates();

        // Update attribution calculations
        self::update_attribution();
    }

    /**
     * Update daily aggregated statistics
     */
    private static function update_daily_aggregates(): void {
        global $wpdb;

        $submissions_table = $wpdb->prefix . 'peanut_formflow_submissions';
        $views_table = $wpdb->prefix . 'peanut_formflow_views';

        // Check if tables exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$submissions_table'") !== $submissions_table) {
            return;
        }

        $today = date('Y-m-d');

        // Get today's stats
        $stats = [
            'views' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$views_table} WHERE DATE(viewed_at) = %s",
                $today
            )) ?? 0,
            'submissions' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$submissions_table}
                 WHERE DATE(created_at) = %s AND status = 'completed'",
                $today
            )) ?? 0,
        ];

        // Store in transient for quick dashboard access
        set_transient('peanut_formflow_today_stats', $stats, HOUR_IN_SECONDS);
    }

    /**
     * Update attribution data for reporting
     */
    private static function update_attribution(): void {
        global $wpdb;

        $submissions_table = $wpdb->prefix . 'peanut_formflow_submissions';

        if ($wpdb->get_var("SHOW TABLES LIKE '$submissions_table'") !== $submissions_table) {
            return;
        }

        // Get last 30 days attribution summary
        $start_date = date('Y-m-d', strtotime('-30 days'));

        $attribution = $wpdb->get_results($wpdb->prepare(
            "SELECT
                utm_source,
                utm_medium,
                utm_campaign,
                COUNT(*) as conversions,
                COUNT(DISTINCT visitor_id) as unique_visitors
             FROM {$submissions_table}
             WHERE created_at >= %s
             AND status = 'completed'
             AND utm_campaign IS NOT NULL
             AND utm_campaign != ''
             GROUP BY utm_source, utm_medium, utm_campaign
             ORDER BY conversions DESC
             LIMIT 50",
            $start_date
        ), ARRAY_A);

        // Store for quick access
        set_transient('peanut_formflow_attribution_30d', $attribution, HOUR_IN_SECONDS);
    }

    /**
     * Get cached daily stats
     *
     * @return array Today's statistics
     */
    public static function get_today_stats(): array {
        $cached = get_transient('peanut_formflow_today_stats');

        if ($cached !== false) {
            return $cached;
        }

        // Regenerate if not cached
        self::update_daily_aggregates();
        return get_transient('peanut_formflow_today_stats') ?: [
            'views' => 0,
            'submissions' => 0,
        ];
    }

    /**
     * Get cached attribution data
     *
     * @return array Attribution data
     */
    public static function get_attribution_summary(): array {
        $cached = get_transient('peanut_formflow_attribution_30d');

        if ($cached !== false) {
            return $cached;
        }

        // Regenerate if not cached
        self::update_attribution();
        return get_transient('peanut_formflow_attribution_30d') ?: [];
    }

    /**
     * Calculate conversion funnel for a date range
     *
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return array Funnel data
     */
    public static function get_funnel(string $start_date, string $end_date): array {
        global $wpdb;

        $submissions_table = $wpdb->prefix . 'peanut_formflow_submissions';
        $views_table = $wpdb->prefix . 'peanut_formflow_views';

        $end_datetime = $end_date . ' 23:59:59';

        // Get unique visitors at each stage
        $views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_id) FROM {$views_table}
             WHERE viewed_at BETWEEN %s AND %s",
            $start_date, $end_datetime
        )) ?? 0;

        $started = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_id) FROM {$submissions_table}
             WHERE created_at BETWEEN %s AND %s",
            $start_date, $end_datetime
        )) ?? 0;

        $completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_id) FROM {$submissions_table}
             WHERE created_at BETWEEN %s AND %s
             AND status = 'completed'",
            $start_date, $end_datetime
        )) ?? 0;

        return [
            'stages' => [
                ['name' => 'Form Views', 'count' => (int)$views],
                ['name' => 'Started', 'count' => (int)$started],
                ['name' => 'Completed', 'count' => (int)$completed],
            ],
            'rates' => [
                'view_to_start' => $views > 0 ? round($started / $views * 100, 2) : 0,
                'start_to_complete' => $started > 0 ? round($completed / $started * 100, 2) : 0,
                'overall' => $views > 0 ? round($completed / $views * 100, 2) : 0,
            ],
        ];
    }
}

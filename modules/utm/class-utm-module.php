<?php
/**
 * UTM Campaigns Module
 *
 * Create and manage UTM tracking codes for marketing campaigns.
 */

if (!defined('ABSPATH')) {
    exit;
}

class UTM_Module {

    /**
     * Initialize module
     */
    public function init(): void {
        // Register API routes
        add_action('peanut_register_routes', [$this, 'register_routes']);

        // Register hooks
        add_filter('peanut_dashboard_stats', [$this, 'add_dashboard_stats'], 10, 2);
    }

    /**
     * Register REST routes
     */
    public function register_routes(): void {
        require_once __DIR__ . '/api/class-utm-controller.php';
        $controller = new UTM_Controller();
        $controller->register_routes();
    }

    /**
     * Add stats to dashboard
     */
    public function add_dashboard_stats(array $stats, string $period): array {
        global $wpdb;
        $table = Peanut_Database::utms_table();
        $user_id = get_current_user_id();

        $date_clause = $this->get_date_clause($period);

        // Total UTMs
        $stats['utm_total'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND is_archived = 0",
            $user_id
        ));

        // UTMs created in period
        $stats['utm_created'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND is_archived = 0 $date_clause",
            $user_id
        ));

        // Total clicks
        $stats['utm_clicks'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(click_count) FROM $table WHERE user_id = %d AND is_archived = 0",
            $user_id
        ));

        // Top campaigns
        $stats['utm_top_campaigns'] = $wpdb->get_results($wpdb->prepare(
            "SELECT utm_campaign, SUM(click_count) as clicks, COUNT(*) as count
             FROM $table
             WHERE user_id = %d AND is_archived = 0
             GROUP BY utm_campaign
             ORDER BY clicks DESC
             LIMIT 5",
            $user_id
        ), ARRAY_A);

        return $stats;
    }

    /**
     * Get date clause for queries
     */
    private function get_date_clause(string $period): string {
        return match ($period) {
            '7d' => "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            '30d' => "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            '90d' => "AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
            'year' => "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)",
            default => '',
        };
    }

    /**
     * Build full UTM URL
     */
    public static function build_url(array $params): string {
        $base = rtrim($params['base_url'], '?&');
        $separator = str_contains($base, '?') ? '&' : '?';

        $utm_params = [];

        if (!empty($params['utm_source'])) {
            $utm_params['utm_source'] = $params['utm_source'];
        }
        if (!empty($params['utm_medium'])) {
            $utm_params['utm_medium'] = $params['utm_medium'];
        }
        if (!empty($params['utm_campaign'])) {
            $utm_params['utm_campaign'] = $params['utm_campaign'];
        }
        if (!empty($params['utm_term'])) {
            $utm_params['utm_term'] = $params['utm_term'];
        }
        if (!empty($params['utm_content'])) {
            $utm_params['utm_content'] = $params['utm_content'];
        }

        return $base . $separator . http_build_query($utm_params);
    }
}

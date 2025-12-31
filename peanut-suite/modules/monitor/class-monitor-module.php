<?php
/**
 * Monitor Module
 *
 * Multi-site management, health monitoring, and aggregated analytics.
 * Agency tier feature.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Monitor_Module {

    /**
     * @var Monitor_Sites
     */
    private Monitor_Sites $sites;

    /**
     * @var Monitor_Health
     */
    private Monitor_Health $health;

    /**
     * @var Monitor_WebVitals
     */
    private Monitor_WebVitals $webvitals;

    /**
     * Initialize module
     */
    public function init(): void {
        $this->load_dependencies();

        // Ensure database tables exist
        Monitor_Database::create_tables();

        // Register API routes
        add_action('peanut_register_routes', [$this, 'register_routes']);

        // Register hooks
        add_filter('peanut_dashboard_stats', [$this, 'add_dashboard_stats'], 10, 2);

        // Schedule cron jobs
        add_action('init', [$this, 'schedule_cron']);
        add_action('peanut_monitor_health_check', [$this, 'run_scheduled_health_checks']);
        add_action('peanut_monitor_uptime_check', [$this, 'run_uptime_checks']);
        add_action('peanut_monitor_webvitals_check', [$this, 'run_webvitals_checks']);
        add_action('peanut_monitor_server_health_check', [$this, 'run_server_health_checks']);

        // AJAX handlers
        add_action('wp_ajax_peanut_add_monitor_site', [$this, 'ajax_add_site']);
        add_action('wp_ajax_peanut_test_monitor_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_peanut_refresh_monitor_site', [$this, 'ajax_refresh_site']);
        add_action('wp_ajax_peanut_delete_monitor_site', [$this, 'ajax_delete_site']);

        // Initialize services
        $this->sites = new Monitor_Sites();
        $this->health = new Monitor_Health();
        $this->webvitals = new Monitor_WebVitals();
    }

    /**
     * Load required files
     */
    private function load_dependencies(): void {
        require_once __DIR__ . '/class-monitor-database.php';
        require_once __DIR__ . '/class-monitor-sites.php';
        require_once __DIR__ . '/class-monitor-health.php';
        require_once __DIR__ . '/class-monitor-webvitals.php';
    }

    /**
     * Register REST routes
     */
    public function register_routes(): void {
        require_once __DIR__ . '/api/class-monitor-controller.php';
        $controller = new Monitor_Controller($this->sites, $this->health);
        $controller->register_routes();
    }

    /**
     * Schedule cron events
     */
    public function schedule_cron(): void {
        if (!wp_next_scheduled('peanut_monitor_health_check')) {
            wp_schedule_event(time(), 'hourly', 'peanut_monitor_health_check');
        }

        if (!wp_next_scheduled('peanut_monitor_uptime_check')) {
            wp_schedule_event(time(), 'peanut_five_minutes', 'peanut_monitor_uptime_check');
        }

        // Web Vitals check - daily (API rate limits)
        if (!wp_next_scheduled('peanut_monitor_webvitals_check')) {
            wp_schedule_event(time(), 'daily', 'peanut_monitor_webvitals_check');
        }

        // Plesk server health check - hourly
        if (!wp_next_scheduled('peanut_monitor_server_health_check')) {
            wp_schedule_event(time(), 'hourly', 'peanut_monitor_server_health_check');
        }
    }

    /**
     * Run scheduled health checks on all sites
     */
    public function run_scheduled_health_checks(): void {
        $sites = $this->sites->get_all_active();

        foreach ($sites as $site) {
            $this->health->check_site($site);
        }
    }

    /**
     * Run uptime checks on all sites
     */
    public function run_uptime_checks(): void {
        $sites = $this->sites->get_all_active();

        foreach ($sites as $site) {
            $this->health->check_uptime($site);
        }
    }

    /**
     * Run Core Web Vitals checks on all sites
     */
    public function run_webvitals_checks(): void {
        $sites = $this->sites->get_all_active();

        foreach ($sites as $site) {
            $this->webvitals->check_site($site);

            // Small delay between checks to avoid rate limiting
            sleep(2);
        }
    }

    /**
     * Run health checks on all Plesk servers
     */
    public function run_server_health_checks(): void {
        global $wpdb;

        $table = Peanut_Database::monitor_servers_table();

        // Get all active servers
        $servers = $wpdb->get_results(
            "SELECT id FROM {$table} WHERE status = 'active'",
            ARRAY_A
        );

        foreach ($servers as $server) {
            Monitor_Plesk::check_health($server['id']);

            // Small delay between checks to avoid overwhelming servers
            sleep(2);
        }

        // Cleanup old health history (keep 90 days)
        Monitor_Plesk::cleanup_old_history(90);
    }

    /**
     * Add stats to dashboard
     */
    public function add_dashboard_stats(array $stats, string $period): array {
        global $wpdb;
        $sites_table = Monitor_Database::sites_table();
        $user_id = get_current_user_id();

        // Total connected sites
        $stats['monitor_sites_total'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $sites_table WHERE user_id = %d AND status = 'active'",
            $user_id
        ));

        // Sites with issues
        $stats['monitor_sites_issues'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $sites_table
             WHERE user_id = %d AND status = 'active'
             AND JSON_EXTRACT(last_health, '$.status') IN ('warning', 'critical')",
            $user_id
        ));

        // Aggregated Peanut Suite stats from all sites
        $analytics_table = Monitor_Database::analytics_table();
        $aggregated = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(JSON_EXTRACT(metrics, '$.contacts')) as total_contacts,
                SUM(JSON_EXTRACT(metrics, '$.utm_clicks')) as total_utm_clicks,
                SUM(JSON_EXTRACT(metrics, '$.link_clicks')) as total_link_clicks
             FROM $analytics_table a
             INNER JOIN $sites_table s ON a.site_id = s.id
             WHERE s.user_id = %d AND a.period = 'month'
             AND a.period_start = DATE_FORMAT(NOW(), '%%Y-%%m-01')",
            $user_id
        ));

        $stats['monitor_total_contacts'] = (int) ($aggregated->total_contacts ?? 0);
        $stats['monitor_total_utm_clicks'] = (int) ($aggregated->total_utm_clicks ?? 0);
        $stats['monitor_total_link_clicks'] = (int) ($aggregated->total_link_clicks ?? 0);

        return $stats;
    }

    /**
     * Get sites instance
     */
    public function get_sites(): Monitor_Sites {
        return $this->sites;
    }

    /**
     * Get health instance
     */
    public function get_health(): Monitor_Health {
        return $this->health;
    }

    /**
     * Get webvitals instance
     */
    public function get_webvitals(): Monitor_WebVitals {
        return $this->webvitals;
    }

    /**
     * AJAX: Add a new site to monitor
     */
    public function ajax_add_site(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'peanut-suite')]);
        }

        if (!wp_verify_nonce($_POST['peanut_nonce'] ?? '', 'peanut_admin_nonce')) {
            wp_send_json_error(['message' => __('Invalid security token.', 'peanut-suite')]);
        }

        $site_name = sanitize_text_field($_POST['name'] ?? '');
        $site_url = esc_url_raw($_POST['url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');

        if (empty($site_name) || empty($site_url) || empty($api_key)) {
            wp_send_json_error(['message' => __('All fields are required.', 'peanut-suite')]);
        }

        // Check if user can add more sites (license limit)
        if (!$this->sites->can_add_site()) {
            wp_send_json_error(['message' => __('You have reached your site limit. Please upgrade your license to add more sites.', 'peanut-suite')]);
        }

        // Add the site
        $result = $this->sites->add([
            'site_name' => $site_name,
            'site_url' => $site_url,
            'site_key' => $api_key,
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        // Store the encrypted site key
        $this->sites->store_site_key($result, $api_key);

        // Run immediate full health check to populate all data
        $site = $this->sites->get($result);
        if ($site) {
            $health_data = $this->health->check_site($site);
        }

        wp_send_json_success([
            'message' => __('Site added successfully!', 'peanut-suite'),
            'site_id' => $result,
        ]);
    }

    /**
     * AJAX: Test connection to a site
     */
    public function ajax_test_connection(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'peanut-suite')]);
        }

        if (!wp_verify_nonce($_POST['peanut_nonce'] ?? '', 'peanut_admin_nonce')) {
            wp_send_json_error(['message' => __('Invalid security token.', 'peanut-suite')]);
        }

        $site_url = esc_url_raw($_POST['url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');

        if (empty($site_url) || empty($api_key)) {
            wp_send_json_error(['message' => __('Site URL and API key are required.', 'peanut-suite')]);
        }

        // Verify connection
        $result = $this->sites->verify_connection($site_url, $api_key);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Connection successful! Site is reachable.', 'peanut-suite'),
            'site_info' => $result,
        ]);
    }

    /**
     * AJAX: Refresh a single site's data
     */
    public function ajax_refresh_site(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'peanut-suite')]);
        }

        if (!wp_verify_nonce($_POST['peanut_nonce'] ?? '', 'peanut_admin_nonce')) {
            wp_send_json_error(['message' => __('Invalid security token.', 'peanut-suite')]);
        }

        $site_id = (int) ($_POST['site_id'] ?? 0);

        if (!$site_id) {
            wp_send_json_error(['message' => __('Invalid site ID.', 'peanut-suite')]);
        }

        $site = $this->sites->get($site_id);

        if (!$site) {
            wp_send_json_error(['message' => __('Site not found.', 'peanut-suite')]);
        }

        // Run health check
        $health_data = $this->health->check_site($site);

        wp_send_json_success([
            'message' => __('Site refreshed successfully.', 'peanut-suite'),
            'health' => $health_data,
        ]);
    }

    /**
     * AJAX: Delete/disconnect a site
     */
    public function ajax_delete_site(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'peanut-suite')]);
        }

        if (!wp_verify_nonce($_POST['peanut_nonce'] ?? '', 'peanut_admin_nonce')) {
            wp_send_json_error(['message' => __('Invalid security token.', 'peanut-suite')]);
        }

        $site_id = (int) ($_POST['site_id'] ?? 0);

        if (!$site_id) {
            wp_send_json_error(['message' => __('Invalid site ID.', 'peanut-suite')]);
        }

        $result = $this->sites->disconnect($site_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        // Delete stored site key
        $this->sites->delete_site_key($site_id);

        wp_send_json_success([
            'message' => __('Site disconnected successfully.', 'peanut-suite'),
        ]);
    }

    /**
     * Deactivate module - clean up cron jobs
     */
    public function deactivate(): void {
        wp_clear_scheduled_hook('peanut_monitor_health_check');
        wp_clear_scheduled_hook('peanut_monitor_uptime_check');
        wp_clear_scheduled_hook('peanut_monitor_webvitals_check');
    }
}

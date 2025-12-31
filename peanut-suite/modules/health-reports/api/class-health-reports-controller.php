<?php
/**
 * Health Reports REST Controller
 *
 * Handles API endpoints for health reports.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Health_Reports_Controller {

    /**
     * @var Health_Reports_Module
     */
    private Health_Reports_Module $module;

    /**
     * @var Health_Report_Generator
     */
    private Health_Report_Generator $generator;

    /**
     * @var Health_Recommendations
     */
    private Health_Recommendations $recommendations;

    /**
     * @var string
     */
    private string $namespace = 'peanut/v1';

    /**
     * Constructor
     */
    public function __construct(Health_Reports_Module $module) {
        $this->module = $module;
        $this->generator = $module->get_generator();
        $this->recommendations = $module->get_recommendations();
    }

    /**
     * Register routes
     */
    public function register_routes(): void {
        // Get settings
        register_rest_route($this->namespace, '/health-reports/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_settings'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Update settings
        register_rest_route($this->namespace, '/health-reports/settings', [
            'methods' => 'POST',
            'callback' => [$this, 'update_settings'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Get latest report
        register_rest_route($this->namespace, '/health-reports/latest', [
            'methods' => 'GET',
            'callback' => [$this, 'get_latest'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Get report history
        register_rest_route($this->namespace, '/health-reports/history', [
            'methods' => 'GET',
            'callback' => [$this, 'get_history'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Get specific report
        register_rest_route($this->namespace, '/health-reports/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_report'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // Generate report now
        register_rest_route($this->namespace, '/health-reports/generate', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_report'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Preview current state
        register_rest_route($this->namespace, '/health-reports/preview', [
            'methods' => 'GET',
            'callback' => [$this, 'preview_report'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Send report now
        register_rest_route($this->namespace, '/health-reports/send', [
            'methods' => 'POST',
            'callback' => [$this, 'send_report'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Get available sites and servers for selection
        register_rest_route($this->namespace, '/health-reports/available-items', [
            'methods' => 'GET',
            'callback' => [$this, 'get_available_items'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    /**
     * Check permissions
     */
    public function check_permission(WP_REST_Request $request): bool|WP_Error {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                __('Authentication required.', 'peanut-suite'),
                ['status' => 401]
            );
        }

        if (!wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
            return new WP_Error(
                'rest_invalid_nonce',
                __('Invalid security token.', 'peanut-suite'),
                ['status' => 403]
            );
        }

        return current_user_can('manage_options');
    }

    /**
     * Get user's report settings
     */
    public function get_settings(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();

        $settings = $this->module->get_user_settings($user_id);

        return new WP_REST_Response([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update user's report settings
     */
    public function update_settings(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();

        $settings = [
            'enabled' => $request->get_param('enabled'),
            'frequency' => $request->get_param('frequency'),
            'day_of_week' => $request->get_param('day_of_week'),
            'send_time' => $request->get_param('send_time'),
            'recipients' => $request->get_param('recipients'),
            'include_sites' => $request->get_param('include_sites'),
            'include_servers' => $request->get_param('include_servers'),
            'include_recommendations' => $request->get_param('include_recommendations'),
            'selected_site_ids' => $request->get_param('selected_site_ids'),
            'selected_server_ids' => $request->get_param('selected_server_ids'),
        ];

        $result = $this->module->save_user_settings($user_id, $settings);

        if (!$result) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Failed to save settings.',
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Settings saved successfully.',
            'data' => $this->module->get_user_settings($user_id),
        ]);
    }

    /**
     * Get the latest report
     */
    public function get_latest(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();

        $report = $this->generator->get_latest($user_id);

        return new WP_REST_Response([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get report history
     */
    public function get_history(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $limit = (int) ($request->get_param('limit') ?: 10);

        $history = $this->generator->get_history($user_id, $limit);

        return new WP_REST_Response([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get a specific report
     */
    public function get_report(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $report_id = (int) $request->get_param('id');

        $report = $this->generator->get_report($report_id, $user_id);

        if (!$report) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Report not found.',
            ], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Generate a new report
     */
    public function generate_report(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();

        $result = $this->module->generate_and_send_report($user_id);

        if (is_wp_error($result)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Report generated successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Preview current state (without saving)
     */
    public function preview_report(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();

        $settings = $this->module->get_user_settings($user_id);

        $preview = $this->generator->preview($user_id, $settings);

        return new WP_REST_Response([
            'success' => true,
            'data' => $preview,
        ]);
    }

    /**
     * Send the latest report via email
     */
    public function send_report(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $report_id = $request->get_param('report_id');

        // Get the report
        if ($report_id) {
            $report = $this->generator->get_report((int) $report_id, $user_id);
        } else {
            $report = $this->generator->get_latest($user_id);
        }

        if (!$report) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No report found to send.',
            ], 404);
        }

        $settings = $this->module->get_user_settings($user_id);

        if (empty($settings['recipients'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No recipients configured. Please add email addresses in settings.',
            ], 400);
        }

        // Send the email
        $recipients = array_filter(array_map('trim', explode("\n", $settings['recipients'])));

        $html = $this->generator->render_email_html($report);

        $grade = $report['overall']['grade'] ?? 'N/A';
        $score = $report['overall']['score'] ?? 0;
        $subject = sprintf(
            'Health Report - Grade: %s (%d/100)',
            $grade,
            $score
        );

        $brand_name = get_option('peanut_white_label_name', 'Marketing Suite');
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $brand_name . ' <' . get_option('admin_email') . '>',
        ];

        $sent_count = 0;
        foreach ($recipients as $recipient) {
            if (is_email($recipient) && wp_mail($recipient, $subject, $html, $headers)) {
                $sent_count++;
            }
        }

        if ($sent_count === 0) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Failed to send emails. Please check your email configuration.',
            ], 500);
        }

        // Update sent_at
        if (!empty($report['id'])) {
            global $wpdb;
            $reports_table = Peanut_Database::health_reports_table();
            $wpdb->update(
                $reports_table,
                ['sent_at' => current_time('mysql')],
                ['id' => $report['id']]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => sprintf('Report sent to %d recipient%s.', $sent_count, $sent_count !== 1 ? 's' : ''),
        ]);
    }

    /**
     * Get available sites and servers for selection
     */
    public function get_available_items(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $user_id = get_current_user_id();

        // Build list of allowed user IDs (same logic as Monitor_Sites::get_all)
        $account_user_ids = [$user_id];
        if (class_exists('Peanut_Account_Service')) {
            $account = Peanut_Account_Service::get_or_create_for_user($user_id);
            if ($account) {
                $members = Peanut_Account_Service::get_members($account['id']);
                if (!empty($members)) {
                    $account_user_ids = array_map(fn($m) => (int) $m['user_id'], $members);
                }
            }
        }

        // Build IN clause for allowed user IDs
        $placeholders = implode(',', array_fill(0, count($account_user_ids), '%d'));

        // Load Monitor_Database if not already loaded
        if (!class_exists('Monitor_Database')) {
            require_once PEANUT_PLUGIN_DIR . 'modules/monitor/class-monitor-database.php';
        }

        // Get monitored sites (from Monitor module) - using account-based access
        $sites_table = Monitor_Database::sites_table();
        $sites = $wpdb->get_results($wpdb->prepare(
            "SELECT id, site_name as name, site_url as url FROM {$sites_table} WHERE user_id IN ($placeholders) ORDER BY site_name ASC",
            ...$account_user_ids
        ), ARRAY_A);

        // Get Plesk servers (from core database) - using account-based access
        $servers_table = Peanut_Database::monitor_servers_table();
        $servers = $wpdb->get_results($wpdb->prepare(
            "SELECT id, server_name as name, server_host as host FROM {$servers_table} WHERE user_id IN ($placeholders) ORDER BY server_name ASC",
            ...$account_user_ids
        ), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'sites' => $sites ?: [],
                'servers' => $servers ?: [],
            ],
        ]);
    }
}

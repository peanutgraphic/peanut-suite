<?php
/**
 * Health Reports Module
 *
 * Generates weekly/monthly health reports for WP sites and Plesk servers.
 * Agency tier feature.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Health_Reports_Module {

    /**
     * @var Health_Report_Generator
     */
    private Health_Report_Generator $generator;

    /**
     * @var Health_Recommendations
     */
    private Health_Recommendations $recommendations;

    /**
     * Initialize module
     */
    public function init(): void {
        $this->load_dependencies();

        // Register API routes
        add_action('peanut_register_routes', [$this, 'register_routes']);

        // Schedule cron jobs
        add_action('init', [$this, 'schedule_cron']);
        add_action('peanut_generate_health_reports', [$this, 'run_scheduled_reports']);

        // Initialize services
        $this->generator = new Health_Report_Generator();
        $this->recommendations = new Health_Recommendations();
    }

    /**
     * Load required files
     */
    private function load_dependencies(): void {
        require_once __DIR__ . '/class-health-report-generator.php';
        require_once __DIR__ . '/class-health-recommendations.php';
    }

    /**
     * Register REST routes
     */
    public function register_routes(): void {
        require_once __DIR__ . '/api/class-health-reports-controller.php';
        $controller = new Health_Reports_Controller($this);
        $controller->register_routes();
    }

    /**
     * Schedule cron events
     */
    public function schedule_cron(): void {
        // Weekly report generation - runs every Monday at configured time
        if (!wp_next_scheduled('peanut_generate_health_reports')) {
            // Schedule for next Monday at 8 AM server time
            $next_monday = strtotime('next Monday 08:00:00');
            wp_schedule_event($next_monday, 'weekly', 'peanut_generate_health_reports');
        }
    }

    /**
     * Run scheduled report generation for all users with enabled settings
     */
    public function run_scheduled_reports(): void {
        global $wpdb;

        $settings_table = Peanut_Database::health_report_settings_table();

        // Get all users with enabled report settings
        $users = $wpdb->get_results(
            "SELECT user_id, frequency, day_of_week FROM {$settings_table} WHERE enabled = 1",
            ARRAY_A
        );

        $current_day = (int) date('w'); // 0 = Sunday, 1 = Monday, etc.

        foreach ($users as $user_settings) {
            $user_id = (int) $user_settings['user_id'];
            $frequency = $user_settings['frequency'];
            $day_of_week = (int) $user_settings['day_of_week'];

            // Check if this is the right day for the user
            if ($frequency === 'weekly' && $current_day !== $day_of_week) {
                continue;
            }

            if ($frequency === 'monthly') {
                // For monthly, only run on the 1st
                if ((int) date('j') !== 1) {
                    continue;
                }
            }

            // Generate and send report
            $this->generate_and_send_report($user_id);
        }
    }

    /**
     * Generate and send report for a specific user
     */
    public function generate_and_send_report(int $user_id): array|WP_Error {
        // Get user settings
        $settings = $this->get_user_settings($user_id);

        if (!$settings || !$settings['enabled']) {
            return new WP_Error('disabled', 'Health reports are disabled for this user.');
        }

        // Calculate period
        $period_end = new DateTime('yesterday');
        if ($settings['frequency'] === 'weekly') {
            $period_start = (clone $period_end)->modify('-6 days');
        } else {
            $period_start = (clone $period_end)->modify('first day of this month');
        }

        // Generate report
        $report = $this->generator->generate(
            $user_id,
            $period_start->format('Y-m-d'),
            $period_end->format('Y-m-d'),
            $settings
        );

        if (is_wp_error($report)) {
            return $report;
        }

        // Send email if recipients are configured
        if (!empty($settings['recipients'])) {
            $this->send_report_email($report, $settings);
        }

        return $report;
    }

    /**
     * Get user's report settings
     */
    public function get_user_settings(int $user_id): ?array {
        global $wpdb;

        $table = Peanut_Database::health_report_settings_table();

        $settings = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d",
            $user_id
        ), ARRAY_A);

        if (!$settings) {
            // Return default settings
            return [
                'user_id' => $user_id,
                'enabled' => true,
                'frequency' => 'weekly',
                'day_of_week' => 1, // Monday
                'send_time' => '08:00',
                'recipients' => '',
                'include_sites' => true,
                'include_servers' => true,
                'include_recommendations' => true,
                'selected_site_ids' => [],
                'selected_server_ids' => [],
            ];
        }

        // Parse JSON fields
        $settings['selected_site_ids'] = !empty($settings['selected_site_ids'])
            ? json_decode($settings['selected_site_ids'], true) ?? []
            : [];
        $settings['selected_server_ids'] = !empty($settings['selected_server_ids'])
            ? json_decode($settings['selected_server_ids'], true) ?? []
            : [];

        return $settings;
    }

    /**
     * Save user's report settings
     */
    public function save_user_settings(int $user_id, array $settings): bool {
        global $wpdb;

        $table = Peanut_Database::health_report_settings_table();

        // Sanitize selected IDs arrays
        $selected_site_ids = [];
        if (!empty($settings['selected_site_ids']) && is_array($settings['selected_site_ids'])) {
            $selected_site_ids = array_map('absint', $settings['selected_site_ids']);
        }

        $selected_server_ids = [];
        if (!empty($settings['selected_server_ids']) && is_array($settings['selected_server_ids'])) {
            $selected_server_ids = array_map('absint', $settings['selected_server_ids']);
        }

        $data = [
            'user_id' => $user_id,
            'enabled' => !empty($settings['enabled']) ? 1 : 0,
            'frequency' => in_array($settings['frequency'] ?? '', ['weekly', 'monthly']) ? $settings['frequency'] : 'weekly',
            'day_of_week' => max(0, min(6, (int) ($settings['day_of_week'] ?? 1))),
            'send_time' => sanitize_text_field($settings['send_time'] ?? '08:00'),
            'recipients' => sanitize_textarea_field($settings['recipients'] ?? ''),
            'include_sites' => !empty($settings['include_sites']) ? 1 : 0,
            'include_servers' => !empty($settings['include_servers']) ? 1 : 0,
            'include_recommendations' => !empty($settings['include_recommendations']) ? 1 : 0,
            'selected_site_ids' => wp_json_encode($selected_site_ids),
            'selected_server_ids' => wp_json_encode($selected_server_ids),
        ];

        // Check if settings exist
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d",
            $user_id
        ));

        if ($exists) {
            return $wpdb->update($table, $data, ['user_id' => $user_id]) !== false;
        }

        return $wpdb->insert($table, $data) !== false;
    }

    /**
     * Send report via email
     */
    private function send_report_email(array $report, array $settings): bool {
        $recipients = array_filter(array_map('trim', explode("\n", $settings['recipients'])));

        if (empty($recipients)) {
            return false;
        }

        $html = $this->generator->render_email_html($report);

        $grade = $report['overall']['grade'] ?? 'N/A';
        $score = $report['overall']['score'] ?? 0;
        $subject = sprintf(
            '%s Health Report - Grade: %s (%d/100)',
            ucfirst($settings['frequency']),
            $grade,
            $score
        );

        // Get brand name for from address
        $brand_name = get_option('peanut_white_label_name', 'Marketing Suite');

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $brand_name . ' <' . get_option('admin_email') . '>',
        ];

        $sent = true;
        foreach ($recipients as $recipient) {
            if (is_email($recipient)) {
                $result = wp_mail($recipient, $subject, $html, $headers);
                if (!$result) {
                    $sent = false;
                }
            }
        }

        // Update sent_at in the report record
        if ($sent && !empty($report['id'])) {
            global $wpdb;
            $reports_table = Peanut_Database::health_reports_table();
            $wpdb->update(
                $reports_table,
                ['sent_at' => current_time('mysql')],
                ['id' => $report['id']]
            );
        }

        return $sent;
    }

    /**
     * Get generator instance
     */
    public function get_generator(): Health_Report_Generator {
        return $this->generator;
    }

    /**
     * Get recommendations instance
     */
    public function get_recommendations(): Health_Recommendations {
        return $this->recommendations;
    }

    /**
     * Deactivate module - clean up cron jobs
     */
    public function deactivate(): void {
        wp_clear_scheduled_hook('peanut_generate_health_reports');
    }
}

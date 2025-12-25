<?php
/**
 * FormFlow Database Handler
 *
 * Manages storage and retrieval of FormFlow data in Peanut Suite.
 *
 * @package Peanut_Suite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FormFlow_Database {

    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Table name for FormFlow submissions
     *
     * @var string
     */
    private string $submissions_table;

    /**
     * Table name for FormFlow views
     *
     * @var string
     */
    private string $views_table;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->submissions_table = $wpdb->prefix . 'peanut_formflow_submissions';
        $this->views_table = $wpdb->prefix . 'peanut_formflow_views';
    }

    /**
     * Create database tables
     */
    public static function create_tables(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $submissions_table = $wpdb->prefix . 'peanut_formflow_submissions';
        $views_table = $wpdb->prefix . 'peanut_formflow_views';

        $sql = "CREATE TABLE IF NOT EXISTS {$submissions_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            visitor_id VARCHAR(64),
            instance_id INT UNSIGNED,
            submission_id INT UNSIGNED,
            email VARCHAR(255),
            status VARCHAR(20) DEFAULT 'pending',
            conversion_type VARCHAR(50) DEFAULT 'enrollment',
            utm_source VARCHAR(100),
            utm_medium VARCHAR(100),
            utm_campaign VARCHAR(255),
            utm_term VARCHAR(255),
            utm_content VARCHAR(255),
            metadata JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME,
            INDEX idx_visitor (visitor_id),
            INDEX idx_instance (instance_id),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) {$charset_collate};

        CREATE TABLE IF NOT EXISTS {$views_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            visitor_id VARCHAR(64),
            instance_id INT UNSIGNED,
            viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_visitor (visitor_id),
            INDEX idx_instance (instance_id),
            INDEX idx_viewed (viewed_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Record a form submission
     *
     * @param array $data Submission data
     * @return int|false Submission ID or false on failure
     */
    public function record_submission(array $data): int|false {
        $result = $this->wpdb->insert($this->submissions_table, [
            'visitor_id' => $data['visitor_id'] ?? null,
            'instance_id' => $data['instance_id'] ?? null,
            'submission_id' => $data['submission_id'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'conversion_type' => $data['conversion_type'] ?? 'enrollment',
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_term' => $data['utm_term'] ?? null,
            'utm_content' => $data['utm_content'] ?? null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'completed_at' => $data['status'] === 'completed' ? current_time('mysql') : null,
        ]);

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Record a form view
     *
     * @param int $instance_id Instance ID
     * @param string $visitor_id Visitor ID
     * @return int|false View ID or false on failure
     */
    public function record_view(int $instance_id, string $visitor_id): int|false {
        $result = $this->wpdb->insert($this->views_table, [
            'visitor_id' => $visitor_id,
            'instance_id' => $instance_id,
        ]);

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Get submissions for a visitor
     *
     * @param string $visitor_id Visitor ID
     * @return array List of submissions
     */
    public function get_visitor_submissions(string $visitor_id): array {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->submissions_table}
             WHERE visitor_id = %s
             ORDER BY created_at DESC",
            $visitor_id
        ), ARRAY_A) ?: [];
    }

    /**
     * Get statistics for a date range
     *
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Statistics
     */
    public function get_stats(string $start_date, string $end_date): array {
        $end_datetime = $end_date . ' 23:59:59';

        // Get view count
        $views = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->views_table}
             WHERE viewed_at BETWEEN %s AND %s",
            $start_date,
            $end_datetime
        )) ?? 0;

        // Get submission count
        $submissions = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->submissions_table}
             WHERE created_at BETWEEN %s AND %s
             AND status = 'completed'",
            $start_date,
            $end_datetime
        )) ?? 0;

        // Calculate completion rate
        $completion_rate = $views > 0 ? round(($submissions / $views) * 100, 2) : 0;

        // Get submissions by status
        $by_status = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT status, COUNT(*) as count
             FROM {$this->submissions_table}
             WHERE created_at BETWEEN %s AND %s
             GROUP BY status",
            $start_date,
            $end_datetime
        ), ARRAY_A) ?: [];

        // Get submissions by instance
        $by_instance = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT instance_id, COUNT(*) as count
             FROM {$this->submissions_table}
             WHERE created_at BETWEEN %s AND %s
             AND status = 'completed'
             GROUP BY instance_id
             ORDER BY count DESC",
            $start_date,
            $end_datetime
        ), ARRAY_A) ?: [];

        // Get daily trend
        $daily_trend = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count
             FROM {$this->submissions_table}
             WHERE created_at BETWEEN %s AND %s
             AND status = 'completed'
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $start_date,
            $end_datetime
        ), ARRAY_A) ?: [];

        return [
            'views' => (int)$views,
            'submissions' => (int)$submissions,
            'completion_rate' => $completion_rate,
            'by_status' => $by_status,
            'by_instance' => $by_instance,
            'daily_trend' => $daily_trend,
        ];
    }

    /**
     * Get attribution data for a date range
     *
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return array Attribution data
     */
    public function get_attribution_data(string $start_date, string $end_date): array {
        $end_datetime = $end_date . ' 23:59:59';

        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT
                utm_source,
                utm_medium,
                utm_campaign,
                COUNT(*) as conversions
             FROM {$this->submissions_table}
             WHERE created_at BETWEEN %s AND %s
             AND status = 'completed'
             AND utm_campaign IS NOT NULL
             GROUP BY utm_source, utm_medium, utm_campaign
             ORDER BY conversions DESC",
            $start_date,
            $end_datetime
        ), ARRAY_A) ?: [];
    }

    /**
     * Clean up old records
     *
     * @param int $days_to_keep Number of days to retain
     * @return int Number of records deleted
     */
    public function cleanup(int $days_to_keep = 365): int {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));

        $deleted_views = $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM {$this->views_table} WHERE viewed_at < %s",
            $cutoff
        ));

        return $deleted_views ?: 0;
    }
}

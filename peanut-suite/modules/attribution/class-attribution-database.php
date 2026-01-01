<?php
/**
 * Attribution Database Handler
 *
 * @package PeanutSuite\Attribution
 */

namespace PeanutSuite\Attribution;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database operations for attribution tracking.
 */
class Attribution_Database {

    /**
     * Get touches table name.
     *
     * @return string
     */
    public static function get_touches_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_touches';
    }

    /**
     * Get conversions table name.
     *
     * @return string
     */
    public static function get_conversions_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_conversions';
    }

    /**
     * Get attribution results table name.
     *
     * @return string
     */
    public static function get_results_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_attribution_results';
    }

    /**
     * Create database tables.
     */
    public static function create_tables(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $touches_table = self::get_touches_table();
        $conversions_table = self::get_conversions_table();
        $results_table = self::get_results_table();

        $sql = "CREATE TABLE {$touches_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            visitor_id varchar(64) NOT NULL,
            session_id varchar(64) DEFAULT NULL,
            touch_type varchar(50) NOT NULL,
            channel varchar(100) DEFAULT NULL,
            source varchar(100) DEFAULT NULL,
            medium varchar(100) DEFAULT NULL,
            campaign varchar(255) DEFAULT NULL,
            content varchar(255) DEFAULT NULL,
            term varchar(255) DEFAULT NULL,
            landing_page text DEFAULT NULL,
            referrer text DEFAULT NULL,
            touch_time datetime DEFAULT CURRENT_TIMESTAMP,
            conversion_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY visitor_id (visitor_id),
            KEY session_id (session_id),
            KEY channel (channel),
            KEY conversion_id (conversion_id),
            KEY touch_time (touch_time)
        ) {$charset_collate};

        CREATE TABLE {$conversions_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            visitor_id varchar(64) NOT NULL,
            conversion_type varchar(50) NOT NULL,
            conversion_value decimal(10,2) DEFAULT 0.00,
            source varchar(50) DEFAULT NULL,
            source_id varchar(100) DEFAULT NULL,
            customer_email varchar(255) DEFAULT NULL,
            customer_name varchar(255) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            converted_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY visitor_id (visitor_id),
            KEY conversion_type (conversion_type),
            KEY converted_at (converted_at),
            KEY source (source)
        ) {$charset_collate};

        CREATE TABLE {$results_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversion_id bigint(20) unsigned NOT NULL,
            touch_id bigint(20) unsigned NOT NULL,
            model varchar(50) NOT NULL,
            credit decimal(5,4) DEFAULT 0.0000,
            calculated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_result (conversion_id, touch_id, model),
            KEY conversion_id (conversion_id),
            KEY touch_id (touch_id),
            KEY model (model)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Record a touch point.
     *
     * @param array $data Touch data.
     * @return int|false Insert ID or false on failure.
     */
    public static function record_touch(array $data) {
        global $wpdb;
        $table = self::get_touches_table();

        $defaults = [
            'touch_time' => current_time('mysql'),
            'created_at' => current_time('mysql'),
        ];

        $data = array_merge($defaults, $data);

        $result = $wpdb->insert($table, $data);

        return $result !== false ? $wpdb->insert_id : false;
    }

    /**
     * Get touches for a visitor.
     *
     * @param string $visitor_id   Visitor identifier.
     * @param array  $args         Query arguments.
     * @return array
     */
    public static function get_visitor_touches(string $visitor_id, array $args = []): array {
        global $wpdb;
        $table = self::get_touches_table();

        $defaults = [
            'conversion_id' => null,
            'before' => null,
            'after' => null,
            'limit' => 100,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['visitor_id = %s'];
        $params = [$visitor_id];

        if ($args['conversion_id']) {
            $where[] = '(conversion_id IS NULL OR conversion_id = %d)';
            $params[] = $args['conversion_id'];
        }

        if ($args['before']) {
            $where[] = 'touch_time <= %s';
            $params[] = $args['before'];
        }

        if ($args['after']) {
            $where[] = 'touch_time >= %s';
            $params[] = $args['after'];
        }

        $where_clause = implode(' AND ', $where);
        $params[] = (int) $args['limit'];

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY touch_time ASC LIMIT %d",
                ...$params
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Record a conversion.
     *
     * @param array $data Conversion data.
     * @return int|false Insert ID or false on failure.
     */
    public static function record_conversion(array $data) {
        global $wpdb;
        $table = self::get_conversions_table();

        $defaults = [
            'converted_at' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'conversion_value' => 0.00,
        ];

        $data = array_merge($defaults, $data);

        if (!empty($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = wp_json_encode($data['metadata']);
        }

        $result = $wpdb->insert($table, $data);

        return $result !== false ? $wpdb->insert_id : false;
    }

    /**
     * Get conversion by ID.
     *
     * @param int $id Conversion ID.
     * @return array|null
     */
    public static function get_conversion(int $id): ?array {
        global $wpdb;
        $table = self::get_conversions_table();

        $conversion = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if ($conversion && !empty($conversion['metadata'])) {
            $conversion['metadata'] = json_decode($conversion['metadata'], true);
        }

        return $conversion ?: null;
    }

    /**
     * Get conversions with pagination.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public static function get_conversions(array $args = []): array {
        global $wpdb;
        $table = self::get_conversions_table();

        $defaults = [
            'page' => 1,
            'per_page' => 20,
            'visitor_id' => null,
            'conversion_type' => null,
            'source' => null,
            'date_from' => null,
            'date_to' => null,
            'order_by' => 'converted_at',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $where = ['1=1'];
        $params = [];

        if ($args['visitor_id']) {
            $where[] = 'visitor_id = %s';
            $params[] = $args['visitor_id'];
        }

        if ($args['conversion_type']) {
            $where[] = 'conversion_type = %s';
            $params[] = $args['conversion_type'];
        }

        if ($args['source']) {
            $where[] = 'source = %s';
            $params[] = $args['source'];
        }

        if ($args['date_from']) {
            $where[] = 'converted_at >= %s';
            $params[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where[] = 'converted_at <= %s';
            $params[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);

        $allowed_order_by = ['id', 'converted_at', 'conversion_value', 'conversion_type'];
        $order_by = in_array($args['order_by'], $allowed_order_by, true) ? $args['order_by'] : 'converted_at';
        $order = $args['order'] === 'ASC' ? 'ASC' : 'DESC';

        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
        $total = $wpdb->get_var(
            empty($params) ? $count_sql : $wpdb->prepare($count_sql, ...$params)
        );

        // Get conversions
        $query_params = array_merge($params, [(int) $args['per_page'], (int) $offset]);
        $sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d";

        $conversions = $wpdb->get_results(
            $wpdb->prepare($sql, ...$query_params),
            ARRAY_A
        );

        // Decode metadata
        foreach ($conversions as &$conv) {
            if (!empty($conv['metadata'])) {
                $conv['metadata'] = json_decode($conv['metadata'], true);
            }
        }

        return [
            'data' => $conversions ?: [],
            'total' => (int) $total,
            'total_pages' => ceil($total / $args['per_page']),
            'page' => (int) $args['page'],
            'per_page' => (int) $args['per_page'],
        ];
    }

    /**
     * Save attribution result.
     *
     * @param int    $conversion_id Conversion ID.
     * @param int    $touch_id      Touch ID.
     * @param string $model         Attribution model.
     * @param float  $credit        Credit value (0-1).
     * @return bool
     */
    public static function save_attribution_result(int $conversion_id, int $touch_id, string $model, float $credit): bool {
        global $wpdb;
        $table = self::get_results_table();

        $result = $wpdb->replace(
            $table,
            [
                'conversion_id' => $conversion_id,
                'touch_id' => $touch_id,
                'model' => $model,
                'credit' => $credit,
                'calculated_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%f', '%s']
        );

        return $result !== false;
    }

    /**
     * Get attribution results for a conversion.
     *
     * @param int    $conversion_id Conversion ID.
     * @param string $model         Attribution model (optional).
     * @return array
     */
    public static function get_attribution_results(int $conversion_id, ?string $model = null): array {
        global $wpdb;
        $results_table = self::get_results_table();
        $touches_table = self::get_touches_table();

        $where = 'r.conversion_id = %d';
        $params = [$conversion_id];

        if ($model) {
            $where .= ' AND r.model = %s';
            $params[] = $model;
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.*, t.channel, t.source, t.medium, t.campaign, t.touch_time
                FROM {$results_table} r
                JOIN {$touches_table} t ON r.touch_id = t.id
                WHERE {$where}
                ORDER BY t.touch_time ASC",
                ...$params
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Link touches to a conversion.
     *
     * @param int   $conversion_id Conversion ID.
     * @param array $touch_ids     Array of touch IDs.
     * @return bool
     */
    public static function link_touches_to_conversion(int $conversion_id, array $touch_ids): bool {
        global $wpdb;
        $table = self::get_touches_table();

        if (empty($touch_ids)) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count($touch_ids), '%d'));
        $params = array_merge([$conversion_id], $touch_ids);

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET conversion_id = %d WHERE id IN ({$placeholders})",
                ...$params
            )
        );

        return $result !== false;
    }

    /**
     * Get channel performance summary.
     *
     * @param string $model      Attribution model.
     * @param array  $args       Query arguments.
     * @return array
     */
    public static function get_channel_performance(string $model, array $args = []): array {
        global $wpdb;
        $results_table = self::get_results_table();
        $touches_table = self::get_touches_table();
        $conversions_table = self::get_conversions_table();

        $defaults = [
            'date_from' => gmdate('Y-m-d', strtotime('-30 days')),
            'date_to' => gmdate('Y-m-d'),
        ];

        $args = wp_parse_args($args, $defaults);

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    t.channel,
                    COUNT(DISTINCT r.conversion_id) as conversions,
                    SUM(r.credit) as attributed_credit,
                    SUM(r.credit * c.conversion_value) as attributed_value,
                    COUNT(DISTINCT t.id) as touches
                FROM {$results_table} r
                JOIN {$touches_table} t ON r.touch_id = t.id
                JOIN {$conversions_table} c ON r.conversion_id = c.id
                WHERE r.model = %s
                    AND c.converted_at >= %s
                    AND c.converted_at <= %s
                GROUP BY t.channel
                ORDER BY attributed_credit DESC",
                $model,
                $args['date_from'] . ' 00:00:00',
                $args['date_to'] . ' 23:59:59'
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Get attribution statistics.
     *
     * @return array
     */
    public static function get_stats(): array {
        global $wpdb;
        $conversions_table = self::get_conversions_table();
        $touches_table = self::get_touches_table();

        $today = current_time('Y-m-d');
        $month_start = gmdate('Y-m-01');

        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(*) as total_conversions,
                    SUM(conversion_value) as total_value,
                    SUM(CASE WHEN DATE(converted_at) = %s THEN 1 ELSE 0 END) as today_conversions,
                    SUM(CASE WHEN DATE(converted_at) = %s THEN conversion_value ELSE 0 END) as today_value,
                    SUM(CASE WHEN converted_at >= %s THEN 1 ELSE 0 END) as month_conversions,
                    SUM(CASE WHEN converted_at >= %s THEN conversion_value ELSE 0 END) as month_value
                FROM {$conversions_table}",
                $today,
                $today,
                $month_start,
                $month_start
            ),
            ARRAY_A
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from class method
        $touches = $wpdb->get_var("SELECT COUNT(*) FROM " . esc_sql($touches_table));

        return [
            'total_conversions' => (int) ($stats['total_conversions'] ?? 0),
            'total_value' => (float) ($stats['total_value'] ?? 0),
            'today_conversions' => (int) ($stats['today_conversions'] ?? 0),
            'today_value' => (float) ($stats['today_value'] ?? 0),
            'month_conversions' => (int) ($stats['month_conversions'] ?? 0),
            'month_value' => (float) ($stats['month_value'] ?? 0),
            'total_touches' => (int) ($touches ?? 0),
        ];
    }

    /**
     * Delete old unlinked touches.
     *
     * @param int $days_to_keep Number of days to keep.
     * @return int Number of deleted rows.
     */
    public static function cleanup_old_touches(int $days_to_keep = 90): int {
        global $wpdb;
        $table = self::get_touches_table();

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table}
                WHERE conversion_id IS NULL
                AND touch_time < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days_to_keep
            )
        );

        return (int) $deleted;
    }
}

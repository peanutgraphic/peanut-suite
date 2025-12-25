<?php
/**
 * Analytics Database Handler
 *
 * @package PeanutSuite\Analytics
 */

namespace PeanutSuite\Analytics;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database operations for analytics aggregation.
 */
class Analytics_Database {

    /**
     * Get daily stats table name.
     *
     * @return string
     */
    public static function get_stats_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_daily_stats';
    }

    /**
     * Create database tables.
     */
    public static function create_tables(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table = self::get_stats_table();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            stat_date date NOT NULL,
            source varchar(50) NOT NULL,
            instance_id bigint(20) unsigned DEFAULT 0,
            metric varchar(50) NOT NULL,
            dimension varchar(100) DEFAULT 'total',
            dimension_value varchar(255) DEFAULT 'all',
            count int(11) DEFAULT 0,
            value decimal(10,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_stat (stat_date, source, instance_id, metric, dimension, dimension_value),
            KEY stat_date (stat_date),
            KEY source (source),
            KEY metric (metric)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Record or update a stat.
     *
     * @param string $date           Date (Y-m-d format).
     * @param string $source         Source module (visitors, attribution, etc).
     * @param string $metric         Metric name.
     * @param int    $count          Count value.
     * @param float  $value          Decimal value.
     * @param int    $instance_id    Optional instance ID.
     * @param string $dimension      Dimension name.
     * @param string $dimension_value Dimension value.
     * @return bool
     */
    public static function record_stat(
        string $date,
        string $source,
        string $metric,
        int $count = 0,
        float $value = 0.00,
        int $instance_id = 0,
        string $dimension = 'total',
        string $dimension_value = 'all'
    ): bool {
        global $wpdb;
        $table = self::get_stats_table();

        $result = $wpdb->replace(
            $table,
            [
                'stat_date' => $date,
                'source' => $source,
                'instance_id' => $instance_id,
                'metric' => $metric,
                'dimension' => $dimension,
                'dimension_value' => $dimension_value,
                'count' => $count,
                'value' => $value,
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%d', '%f']
        );

        return $result !== false;
    }

    /**
     * Increment a stat counter.
     *
     * @param string $date           Date (Y-m-d format).
     * @param string $source         Source module.
     * @param string $metric         Metric name.
     * @param int    $increment      Amount to increment.
     * @param int    $instance_id    Optional instance ID.
     * @param string $dimension      Dimension name.
     * @param string $dimension_value Dimension value.
     * @return bool
     */
    public static function increment_stat(
        string $date,
        string $source,
        string $metric,
        int $increment = 1,
        int $instance_id = 0,
        string $dimension = 'total',
        string $dimension_value = 'all'
    ): bool {
        global $wpdb;
        $table = self::get_stats_table();

        // Try to update existing
        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table} (stat_date, source, instance_id, metric, dimension, dimension_value, count)
                VALUES (%s, %s, %d, %s, %s, %s, %d)
                ON DUPLICATE KEY UPDATE count = count + %d",
                $date, $source, $instance_id, $metric, $dimension, $dimension_value, $increment, $increment
            )
        );

        return $result !== false;
    }

    /**
     * Get stats for a date range.
     *
     * @param string      $source    Source module.
     * @param string      $metric    Metric name.
     * @param string      $date_from Start date.
     * @param string      $date_to   End date.
     * @param string|null $dimension Optional dimension filter.
     * @return array
     */
    public static function get_stats(
        string $source,
        string $metric,
        string $date_from,
        string $date_to,
        ?string $dimension = null
    ): array {
        global $wpdb;
        $table = self::get_stats_table();

        $where = 'source = %s AND metric = %s AND stat_date >= %s AND stat_date <= %s';
        $params = [$source, $metric, $date_from, $date_to];

        if ($dimension) {
            $where .= ' AND dimension = %s';
            $params[] = $dimension;
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE {$where} ORDER BY stat_date ASC",
                ...$params
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Get aggregated stats for a date range.
     *
     * @param string $source    Source module.
     * @param string $metric    Metric name.
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @return array
     */
    public static function get_aggregated_stats(
        string $source,
        string $metric,
        string $date_from,
        string $date_to
    ): array {
        global $wpdb;
        $table = self::get_stats_table();

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    SUM(count) as total_count,
                    SUM(value) as total_value,
                    AVG(count) as avg_count,
                    AVG(value) as avg_value,
                    MAX(count) as max_count,
                    MIN(count) as min_count
                FROM {$table}
                WHERE source = %s AND metric = %s
                    AND stat_date >= %s AND stat_date <= %s
                    AND dimension = 'total'",
                $source, $metric, $date_from, $date_to
            ),
            ARRAY_A
        );

        return $result ?: [];
    }

    /**
     * Get timeline data for charts.
     *
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @param array  $metrics   Array of [source => metric] pairs.
     * @return array
     */
    public static function get_timeline(string $date_from, string $date_to, array $metrics): array {
        global $wpdb;
        $table = self::get_stats_table();

        // Generate date range
        $dates = [];
        $current = strtotime($date_from);
        $end = strtotime($date_to);

        while ($current <= $end) {
            $dates[gmdate('Y-m-d', $current)] = [];
            foreach ($metrics as $key => $config) {
                $dates[gmdate('Y-m-d', $current)][$key] = 0;
            }
            $current = strtotime('+1 day', $current);
        }

        // Fetch data for each metric
        foreach ($metrics as $key => $config) {
            $source = $config['source'];
            $metric = $config['metric'];

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT stat_date, SUM(count) as total
                    FROM {$table}
                    WHERE source = %s AND metric = %s
                        AND stat_date >= %s AND stat_date <= %s
                        AND dimension = 'total'
                    GROUP BY stat_date",
                    $source, $metric, $date_from, $date_to
                ),
                ARRAY_A
            );

            foreach ($results as $row) {
                if (isset($dates[$row['stat_date']])) {
                    $dates[$row['stat_date']][$key] = (int) $row['total'];
                }
            }
        }

        // Convert to array format
        $timeline = [];
        foreach ($dates as $date => $data) {
            $timeline[] = array_merge(['date' => $date], $data);
        }

        return $timeline;
    }

    /**
     * Get breakdown by dimension.
     *
     * @param string $source    Source module.
     * @param string $metric    Metric name.
     * @param string $dimension Dimension name.
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @param int    $limit     Max results.
     * @return array
     */
    public static function get_breakdown(
        string $source,
        string $metric,
        string $dimension,
        string $date_from,
        string $date_to,
        int $limit = 10
    ): array {
        global $wpdb;
        $table = self::get_stats_table();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    dimension_value,
                    SUM(count) as total_count,
                    SUM(value) as total_value
                FROM {$table}
                WHERE source = %s AND metric = %s AND dimension = %s
                    AND stat_date >= %s AND stat_date <= %s
                GROUP BY dimension_value
                ORDER BY total_count DESC
                LIMIT %d",
                $source, $metric, $dimension, $date_from, $date_to, $limit
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Get overview stats for dashboard.
     *
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @return array
     */
    public static function get_overview(string $date_from, string $date_to): array {
        global $wpdb;
        $table = self::get_stats_table();

        // Get all totals grouped by source and metric
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    source,
                    metric,
                    SUM(count) as total_count,
                    SUM(value) as total_value
                FROM {$table}
                WHERE stat_date >= %s AND stat_date <= %s
                    AND dimension = 'total'
                GROUP BY source, metric",
                $date_from, $date_to
            ),
            ARRAY_A
        );

        // Organize by source
        $overview = [];
        foreach ($results as $row) {
            $source = $row['source'];
            $metric = $row['metric'];

            if (!isset($overview[$source])) {
                $overview[$source] = [];
            }

            $overview[$source][$metric] = [
                'count' => (int) $row['total_count'],
                'value' => (float) $row['total_value'],
            ];
        }

        return $overview;
    }

    /**
     * Compare two date ranges.
     *
     * @param string $source         Source module.
     * @param string $metric         Metric name.
     * @param string $current_from   Current period start.
     * @param string $current_to     Current period end.
     * @param string $previous_from  Previous period start.
     * @param string $previous_to    Previous period end.
     * @return array
     */
    public static function compare_periods(
        string $source,
        string $metric,
        string $current_from,
        string $current_to,
        string $previous_from,
        string $previous_to
    ): array {
        $current = self::get_aggregated_stats($source, $metric, $current_from, $current_to);
        $previous = self::get_aggregated_stats($source, $metric, $previous_from, $previous_to);

        $current_total = (int) ($current['total_count'] ?? 0);
        $previous_total = (int) ($previous['total_count'] ?? 0);

        $change = 0;
        $change_percent = 0;

        if ($previous_total > 0) {
            $change = $current_total - $previous_total;
            $change_percent = round(($change / $previous_total) * 100, 1);
        } elseif ($current_total > 0) {
            $change_percent = 100;
        }

        return [
            'current' => $current_total,
            'previous' => $previous_total,
            'change' => $change,
            'change_percent' => $change_percent,
            'trend' => $change >= 0 ? 'up' : 'down',
        ];
    }

    /**
     * Cleanup old stats.
     *
     * @param int $days_to_keep Number of days to keep.
     * @return int Number of deleted rows.
     */
    public static function cleanup_old_stats(int $days_to_keep = 365): int {
        global $wpdb;
        $table = self::get_stats_table();

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE stat_date < DATE_SUB(CURDATE(), INTERVAL %d DAY)",
                $days_to_keep
            )
        );

        return (int) $deleted;
    }
}

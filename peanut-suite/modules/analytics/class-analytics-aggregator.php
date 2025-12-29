<?php
/**
 * Analytics Aggregator
 *
 * Collects and aggregates stats from various modules.
 *
 * @package PeanutSuite\Analytics
 */

namespace PeanutSuite\Analytics;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stats aggregation engine.
 */
class Analytics_Aggregator {

    /**
     * Run full aggregation for a date.
     *
     * @param string|null $date Date to aggregate (defaults to yesterday).
     * @return array
     */
    public static function aggregate(string $date = null): array {
        if (!$date) {
            $date = gmdate('Y-m-d', strtotime('-1 day'));
        }

        $results = [
            'date' => $date,
            'visitors' => self::aggregate_visitors($date),
            'conversions' => self::aggregate_conversions($date),
            'webhooks' => self::aggregate_webhooks($date),
            'touches' => self::aggregate_touches($date),
        ];

        return $results;
    }

    /**
     * Aggregate visitor stats.
     *
     * @param string $date Date to aggregate.
     * @return array
     */
    public static function aggregate_visitors(string $date): array {
        global $wpdb;

        // Check if visitors table exists
        $visitors_table = $wpdb->prefix . 'peanut_visitors';
        $events_table = $wpdb->prefix . 'peanut_visitor_events';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$visitors_table}'") !== $visitors_table) {
            return ['skipped' => 'Table not found'];
        }

        $results = [];

        // New visitors
        $new_visitors = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$visitors_table} WHERE DATE(first_seen) = %s",
                $date
            )
        );
        Analytics_Database::record_stat($date, 'visitors', 'new_visitors', (int) $new_visitors);
        $results['new_visitors'] = (int) $new_visitors;

        // Identified visitors
        $identified = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$visitors_table}
                WHERE DATE(first_seen) = %s AND email IS NOT NULL AND email != ''",
                $date
            )
        );
        Analytics_Database::record_stat($date, 'visitors', 'identified', (int) $identified);
        $results['identified'] = (int) $identified;

        // Pageviews
        $pageviews = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$events_table}
                WHERE DATE(created_at) = %s AND event_type = 'pageview'",
                $date
            )
        );
        Analytics_Database::record_stat($date, 'visitors', 'pageviews', (int) $pageviews);
        $results['pageviews'] = (int) $pageviews;

        // Unique visitors (by events)
        $unique_visitors = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT visitor_id) FROM {$events_table}
                WHERE DATE(created_at) = %s",
                $date
            )
        );
        Analytics_Database::record_stat($date, 'visitors', 'unique_visitors', (int) $unique_visitors);
        $results['unique_visitors'] = (int) $unique_visitors;

        // Device breakdown
        $devices = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT device_type, COUNT(*) as count
                FROM {$visitors_table}
                WHERE DATE(first_seen) = %s
                GROUP BY device_type",
                $date
            ),
            ARRAY_A
        );

        foreach ($devices as $device) {
            $device_type = $device['device_type'] ?: 'unknown';
            Analytics_Database::record_stat(
                $date, 'visitors', 'new_visitors',
                (int) $device['count'], 0, 0,
                'device', $device_type
            );
        }
        $results['devices'] = $devices;

        // Browser breakdown
        $browsers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT browser, COUNT(*) as count
                FROM {$visitors_table}
                WHERE DATE(first_seen) = %s
                GROUP BY browser",
                $date
            ),
            ARRAY_A
        );

        foreach ($browsers as $browser) {
            $browser_name = $browser['browser'] ?: 'unknown';
            Analytics_Database::record_stat(
                $date, 'visitors', 'new_visitors',
                (int) $browser['count'], 0, 0,
                'browser', $browser_name
            );
        }
        $results['browsers'] = $browsers;

        // Traffic sources from UTM
        $sources = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    COALESCE(utm_source, 'direct') as source,
                    COUNT(*) as count
                FROM {$events_table}
                WHERE DATE(created_at) = %s AND event_type = 'pageview'
                GROUP BY utm_source",
                $date
            ),
            ARRAY_A
        );

        foreach ($sources as $source) {
            Analytics_Database::record_stat(
                $date, 'visitors', 'pageviews',
                (int) $source['count'], 0, 0,
                'source', $source['source']
            );
        }
        $results['sources'] = $sources;

        return $results;
    }

    /**
     * Aggregate conversion stats.
     *
     * @param string $date Date to aggregate.
     * @return array
     */
    public static function aggregate_conversions(string $date): array {
        global $wpdb;

        $conversions_table = $wpdb->prefix . 'peanut_conversions';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$conversions_table}'") !== $conversions_table) {
            return ['skipped' => 'Table not found'];
        }

        $results = [];

        // Total conversions
        $total = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) as count, COALESCE(SUM(conversion_value), 0) as value
                FROM {$conversions_table}
                WHERE DATE(converted_at) = %s",
                $date
            ),
            ARRAY_A
        );

        Analytics_Database::record_stat(
            $date, 'conversions', 'total',
            (int) $total['count'],
            (float) $total['value']
        );
        $results['total'] = $total;

        // By conversion type
        $types = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    conversion_type,
                    COUNT(*) as count,
                    COALESCE(SUM(conversion_value), 0) as value
                FROM {$conversions_table}
                WHERE DATE(converted_at) = %s
                GROUP BY conversion_type",
                $date
            ),
            ARRAY_A
        );

        foreach ($types as $type) {
            Analytics_Database::record_stat(
                $date, 'conversions', 'total',
                (int) $type['count'],
                (float) $type['value'],
                0, 'type', $type['conversion_type']
            );
        }
        $results['types'] = $types;

        // By source
        $sources = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    COALESCE(source, 'unknown') as source,
                    COUNT(*) as count,
                    COALESCE(SUM(conversion_value), 0) as value
                FROM {$conversions_table}
                WHERE DATE(converted_at) = %s
                GROUP BY source",
                $date
            ),
            ARRAY_A
        );

        foreach ($sources as $source) {
            Analytics_Database::record_stat(
                $date, 'conversions', 'total',
                (int) $source['count'],
                (float) $source['value'],
                0, 'source', $source['source']
            );
        }
        $results['sources'] = $sources;

        return $results;
    }

    /**
     * Aggregate webhook stats.
     *
     * @param string $date Date to aggregate.
     * @return array
     */
    public static function aggregate_webhooks(string $date): array {
        global $wpdb;

        $webhooks_table = $wpdb->prefix . 'peanut_webhooks_received';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$webhooks_table}'") !== $webhooks_table) {
            return ['skipped' => 'Table not found'];
        }

        $results = [];

        // Total received
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$webhooks_table} WHERE DATE(created_at) = %s",
                $date
            )
        );
        Analytics_Database::record_stat($date, 'webhooks', 'received', (int) $total);
        $results['received'] = (int) $total;

        // By status
        $statuses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT status, COUNT(*) as count
                FROM {$webhooks_table}
                WHERE DATE(created_at) = %s
                GROUP BY status",
                $date
            ),
            ARRAY_A
        );

        foreach ($statuses as $status) {
            Analytics_Database::record_stat(
                $date, 'webhooks', 'received',
                (int) $status['count'], 0, 0,
                'status', $status['status']
            );
        }
        $results['statuses'] = $statuses;

        // By source
        $sources = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT source, COUNT(*) as count
                FROM {$webhooks_table}
                WHERE DATE(created_at) = %s
                GROUP BY source",
                $date
            ),
            ARRAY_A
        );

        foreach ($sources as $source) {
            Analytics_Database::record_stat(
                $date, 'webhooks', 'received',
                (int) $source['count'], 0, 0,
                'source', $source['source']
            );
        }
        $results['sources'] = $sources;

        return $results;
    }

    /**
     * Aggregate touch point stats.
     *
     * @param string $date Date to aggregate.
     * @return array
     */
    public static function aggregate_touches(string $date): array {
        global $wpdb;

        $touches_table = $wpdb->prefix . 'peanut_touches';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$touches_table}'") !== $touches_table) {
            return ['skipped' => 'Table not found'];
        }

        $results = [];

        // Total touches
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$touches_table} WHERE DATE(touch_time) = %s",
                $date
            )
        );
        Analytics_Database::record_stat($date, 'touches', 'total', (int) $total);
        $results['total'] = (int) $total;

        // By channel
        $channels = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COALESCE(channel, 'unknown') as channel, COUNT(*) as count
                FROM {$touches_table}
                WHERE DATE(touch_time) = %s
                GROUP BY channel",
                $date
            ),
            ARRAY_A
        );

        foreach ($channels as $channel) {
            Analytics_Database::record_stat(
                $date, 'touches', 'total',
                (int) $channel['count'], 0, 0,
                'channel', $channel['channel']
            );
        }
        $results['channels'] = $channels;

        return $results;
    }

    /**
     * Backfill stats for a date range.
     *
     * @param string $from Start date.
     * @param string $to   End date.
     * @return array
     */
    public static function backfill(string $from, string $to): array {
        $current = strtotime($from);
        $end = strtotime($to);
        $results = [];

        while ($current <= $end) {
            $date = gmdate('Y-m-d', $current);
            $results[$date] = self::aggregate($date);
            $current = strtotime('+1 day', $current);
        }

        return $results;
    }

    /**
     * Get real-time stats (not from aggregated table).
     *
     * @return array
     */
    public static function get_realtime_stats(): array {
        global $wpdb;

        $stats = [];
        $today = current_time('Y-m-d');
        $now = current_time('mysql');
        $hour_ago = gmdate('Y-m-d H:i:s', strtotime('-1 hour'));

        // Active visitors (last hour)
        $visitors_table = $wpdb->prefix . 'peanut_visitors';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$visitors_table}'") === $visitors_table) {
            $stats['active_visitors'] = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$visitors_table} WHERE last_seen >= %s",
                    $hour_ago
                )
            );
        }

        // Events in last hour
        $events_table = $wpdb->prefix . 'peanut_visitor_events';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$events_table}'") === $events_table) {
            $stats['recent_events'] = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$events_table} WHERE created_at >= %s",
                    $hour_ago
                )
            );

            $stats['today_pageviews'] = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$events_table}
                    WHERE DATE(created_at) = %s AND event_type = 'pageview'",
                    $today
                )
            );
        }

        // Conversions today
        $conversions_table = $wpdb->prefix . 'peanut_conversions';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$conversions_table}'") === $conversions_table) {
            $conv = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT COUNT(*) as count, COALESCE(SUM(conversion_value), 0) as value
                    FROM {$conversions_table}
                    WHERE DATE(converted_at) = %s",
                    $today
                ),
                ARRAY_A
            );

            $stats['today_conversions'] = (int) ($conv['count'] ?? 0);
            $stats['today_revenue'] = (float) ($conv['value'] ?? 0);
        }

        return $stats;
    }
}

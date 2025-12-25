<?php
/**
 * Analytics REST API Controller
 *
 * @package PeanutSuite\Analytics
 */

namespace PeanutSuite\Analytics;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API endpoints for analytics.
 */
class Analytics_Controller {

    /**
     * REST namespace.
     *
     * @var string
     */
    protected string $namespace = 'peanut/v1';

    /**
     * Register REST routes.
     */
    public function register_routes(): void {
        // Overview stats
        register_rest_route($this->namespace, '/analytics/overview', [
            'methods' => 'GET',
            'callback' => [$this, 'get_overview'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'period' => [
                    'default' => '30d',
                    'type' => 'string',
                ],
            ],
        ]);

        // Real-time stats
        register_rest_route($this->namespace, '/analytics/realtime', [
            'methods' => 'GET',
            'callback' => [$this, 'get_realtime'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Timeline data
        register_rest_route($this->namespace, '/analytics/timeline', [
            'methods' => 'GET',
            'callback' => [$this, 'get_timeline'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'period' => [
                    'default' => '30d',
                    'type' => 'string',
                ],
                'metrics' => [
                    'type' => 'string',
                ],
            ],
        ]);

        // Traffic sources
        register_rest_route($this->namespace, '/analytics/sources', [
            'methods' => 'GET',
            'callback' => [$this, 'get_sources'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'period' => [
                    'default' => '30d',
                    'type' => 'string',
                ],
            ],
        ]);

        // Device/browser breakdown
        register_rest_route($this->namespace, '/analytics/devices', [
            'methods' => 'GET',
            'callback' => [$this, 'get_devices'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'period' => [
                    'default' => '30d',
                    'type' => 'string',
                ],
            ],
        ]);

        // Conversion funnel
        register_rest_route($this->namespace, '/analytics/funnel', [
            'methods' => 'GET',
            'callback' => [$this, 'get_funnel'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'period' => [
                    'default' => '30d',
                    'type' => 'string',
                ],
            ],
        ]);

        // Compare periods
        register_rest_route($this->namespace, '/analytics/compare', [
            'methods' => 'GET',
            'callback' => [$this, 'compare_periods'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'period' => [
                    'default' => '30d',
                    'type' => 'string',
                ],
            ],
        ]);

        // Manual aggregation trigger
        register_rest_route($this->namespace, '/analytics/aggregate', [
            'methods' => 'POST',
            'callback' => [$this, 'trigger_aggregation'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => [
                'date' => [
                    'type' => 'string',
                ],
            ],
        ]);
    }

    /**
     * Check admin permission.
     *
     * @return bool
     */
    public function check_permission(): bool {
        return current_user_can('manage_options');
    }

    /**
     * Get date range from period.
     *
     * @param string $period Period string (7d, 30d, 90d, year).
     * @return array
     */
    private function get_date_range(string $period): array {
        $to = gmdate('Y-m-d');

        switch ($period) {
            case '7d':
                $from = gmdate('Y-m-d', strtotime('-7 days'));
                $prev_from = gmdate('Y-m-d', strtotime('-14 days'));
                $prev_to = gmdate('Y-m-d', strtotime('-8 days'));
                break;
            case '90d':
                $from = gmdate('Y-m-d', strtotime('-90 days'));
                $prev_from = gmdate('Y-m-d', strtotime('-180 days'));
                $prev_to = gmdate('Y-m-d', strtotime('-91 days'));
                break;
            case 'year':
                $from = gmdate('Y-m-d', strtotime('-365 days'));
                $prev_from = gmdate('Y-m-d', strtotime('-730 days'));
                $prev_to = gmdate('Y-m-d', strtotime('-366 days'));
                break;
            default: // 30d
                $from = gmdate('Y-m-d', strtotime('-30 days'));
                $prev_from = gmdate('Y-m-d', strtotime('-60 days'));
                $prev_to = gmdate('Y-m-d', strtotime('-31 days'));
        }

        return [
            'from' => $from,
            'to' => $to,
            'prev_from' => $prev_from,
            'prev_to' => $prev_to,
        ];
    }

    /**
     * Get overview stats.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_overview(\WP_REST_Request $request): \WP_REST_Response {
        $period = $request->get_param('period');
        $dates = $this->get_date_range($period);

        $overview = Analytics_Database::get_overview($dates['from'], $dates['to']);
        $realtime = Analytics_Aggregator::get_realtime_stats();

        // Calculate totals and comparisons
        $visitors_current = Analytics_Database::get_aggregated_stats(
            'visitors', 'new_visitors', $dates['from'], $dates['to']
        );
        $visitors_prev = Analytics_Database::get_aggregated_stats(
            'visitors', 'new_visitors', $dates['prev_from'], $dates['prev_to']
        );

        $pageviews_current = Analytics_Database::get_aggregated_stats(
            'visitors', 'pageviews', $dates['from'], $dates['to']
        );

        $conversions_current = Analytics_Database::get_aggregated_stats(
            'conversions', 'total', $dates['from'], $dates['to']
        );
        $conversions_prev = Analytics_Database::get_aggregated_stats(
            'conversions', 'total', $dates['prev_from'], $dates['prev_to']
        );

        $visitors_total = (int) ($visitors_current['total_count'] ?? 0);
        $visitors_prev_total = (int) ($visitors_prev['total_count'] ?? 0);
        $visitors_change = $visitors_prev_total > 0
            ? round((($visitors_total - $visitors_prev_total) / $visitors_prev_total) * 100, 1)
            : 0;

        $conversions_total = (int) ($conversions_current['total_count'] ?? 0);
        $conversions_prev_total = (int) ($conversions_prev['total_count'] ?? 0);
        $conversions_change = $conversions_prev_total > 0
            ? round((($conversions_total - $conversions_prev_total) / $conversions_prev_total) * 100, 1)
            : 0;

        return new \WP_REST_Response([
            'period' => [
                'from' => $dates['from'],
                'to' => $dates['to'],
            ],
            'realtime' => $realtime,
            'summary' => [
                'visitors' => [
                    'total' => $visitors_total,
                    'change' => $visitors_change,
                    'trend' => $visitors_change >= 0 ? 'up' : 'down',
                ],
                'pageviews' => [
                    'total' => (int) ($pageviews_current['total_count'] ?? 0),
                ],
                'conversions' => [
                    'total' => $conversions_total,
                    'value' => (float) ($conversions_current['total_value'] ?? 0),
                    'change' => $conversions_change,
                    'trend' => $conversions_change >= 0 ? 'up' : 'down',
                ],
            ],
            'breakdown' => $overview,
        ]);
    }

    /**
     * Get real-time stats.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_realtime(\WP_REST_Request $request): \WP_REST_Response {
        $stats = Analytics_Aggregator::get_realtime_stats();
        return new \WP_REST_Response($stats);
    }

    /**
     * Get timeline data.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_timeline(\WP_REST_Request $request): \WP_REST_Response {
        $period = $request->get_param('period');
        $dates = $this->get_date_range($period);

        $metrics = [
            'visitors' => ['source' => 'visitors', 'metric' => 'new_visitors'],
            'pageviews' => ['source' => 'visitors', 'metric' => 'pageviews'],
            'conversions' => ['source' => 'conversions', 'metric' => 'total'],
        ];

        $timeline = Analytics_Database::get_timeline($dates['from'], $dates['to'], $metrics);

        return new \WP_REST_Response([
            'period' => [
                'from' => $dates['from'],
                'to' => $dates['to'],
            ],
            'timeline' => $timeline,
        ]);
    }

    /**
     * Get traffic sources breakdown.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_sources(\WP_REST_Request $request): \WP_REST_Response {
        $period = $request->get_param('period');
        $dates = $this->get_date_range($period);

        $sources = Analytics_Database::get_breakdown(
            'visitors', 'pageviews', 'source',
            $dates['from'], $dates['to'], 10
        );

        $channels = Analytics_Database::get_breakdown(
            'touches', 'total', 'channel',
            $dates['from'], $dates['to'], 10
        );

        return new \WP_REST_Response([
            'period' => [
                'from' => $dates['from'],
                'to' => $dates['to'],
            ],
            'sources' => $sources,
            'channels' => $channels,
        ]);
    }

    /**
     * Get device/browser breakdown.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_devices(\WP_REST_Request $request): \WP_REST_Response {
        $period = $request->get_param('period');
        $dates = $this->get_date_range($period);

        $devices = Analytics_Database::get_breakdown(
            'visitors', 'new_visitors', 'device',
            $dates['from'], $dates['to'], 10
        );

        $browsers = Analytics_Database::get_breakdown(
            'visitors', 'new_visitors', 'browser',
            $dates['from'], $dates['to'], 10
        );

        return new \WP_REST_Response([
            'period' => [
                'from' => $dates['from'],
                'to' => $dates['to'],
            ],
            'devices' => $devices,
            'browsers' => $browsers,
        ]);
    }

    /**
     * Get conversion funnel data.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_funnel(\WP_REST_Request $request): \WP_REST_Response {
        $period = $request->get_param('period');
        $dates = $this->get_date_range($period);

        $visitors = Analytics_Database::get_aggregated_stats(
            'visitors', 'unique_visitors', $dates['from'], $dates['to']
        );

        $identified = Analytics_Database::get_aggregated_stats(
            'visitors', 'identified', $dates['from'], $dates['to']
        );

        $conversions = Analytics_Database::get_aggregated_stats(
            'conversions', 'total', $dates['from'], $dates['to']
        );

        $visitors_total = (int) ($visitors['total_count'] ?? 0);
        $identified_total = (int) ($identified['total_count'] ?? 0);
        $conversions_total = (int) ($conversions['total_count'] ?? 0);

        return new \WP_REST_Response([
            'period' => [
                'from' => $dates['from'],
                'to' => $dates['to'],
            ],
            'funnel' => [
                [
                    'stage' => 'Visitors',
                    'count' => $visitors_total,
                    'rate' => 100,
                ],
                [
                    'stage' => 'Identified',
                    'count' => $identified_total,
                    'rate' => $visitors_total > 0 ? round(($identified_total / $visitors_total) * 100, 1) : 0,
                ],
                [
                    'stage' => 'Converted',
                    'count' => $conversions_total,
                    'rate' => $visitors_total > 0 ? round(($conversions_total / $visitors_total) * 100, 1) : 0,
                ],
            ],
        ]);
    }

    /**
     * Compare time periods.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function compare_periods(\WP_REST_Request $request): \WP_REST_Response {
        $period = $request->get_param('period');
        $dates = $this->get_date_range($period);

        $comparisons = [];

        // Visitors comparison
        $comparisons['visitors'] = Analytics_Database::compare_periods(
            'visitors', 'new_visitors',
            $dates['from'], $dates['to'],
            $dates['prev_from'], $dates['prev_to']
        );

        // Pageviews comparison
        $comparisons['pageviews'] = Analytics_Database::compare_periods(
            'visitors', 'pageviews',
            $dates['from'], $dates['to'],
            $dates['prev_from'], $dates['prev_to']
        );

        // Conversions comparison
        $comparisons['conversions'] = Analytics_Database::compare_periods(
            'conversions', 'total',
            $dates['from'], $dates['to'],
            $dates['prev_from'], $dates['prev_to']
        );

        return new \WP_REST_Response([
            'current_period' => [
                'from' => $dates['from'],
                'to' => $dates['to'],
            ],
            'previous_period' => [
                'from' => $dates['prev_from'],
                'to' => $dates['prev_to'],
            ],
            'comparisons' => $comparisons,
        ]);
    }

    /**
     * Trigger manual aggregation.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function trigger_aggregation(\WP_REST_Request $request): \WP_REST_Response {
        $date = $request->get_param('date');

        if (!$date) {
            $date = gmdate('Y-m-d', strtotime('-1 day'));
        }

        $result = Analytics_Aggregator::aggregate($date);

        return new \WP_REST_Response([
            'success' => true,
            'date' => $date,
            'result' => $result,
        ]);
    }
}

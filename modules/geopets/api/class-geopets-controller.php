<?php
/**
 * GEOPETS REST API Controller
 *
 * Handles REST API endpoints for GEOPETS waitlist management.
 *
 * @package PeanutSuite
 * @since 2.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Geopets_Controller {

    use Peanut_REST_Response;

    /**
     * API namespace
     */
    private string $namespace;

    /**
     * Supabase configuration
     */
    private string $supabase_url = '';
    private string $supabase_key = '';

    /**
     * Constructor
     */
    public function __construct() {
        $this->namespace = PEANUT_API_NAMESPACE;
        $this->load_settings();
    }

    /**
     * Load Supabase settings
     */
    private function load_settings(): void {
        $settings = get_option('peanut_geopets_settings', []);
        $this->supabase_url = $settings['supabase_url'] ?? '';
        $this->supabase_key = $settings['supabase_key'] ?? '';
    }

    /**
     * Register REST routes
     */
    public function register_routes(): void {
        // Waitlist endpoints
        register_rest_route($this->namespace, '/geopets/waitlist', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_waitlist'],
                'permission_callback' => [$this, 'check_admin_permission'],
                'args' => [
                    'page' => [
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ],
                    'per_page' => [
                        'default' => 20,
                        'sanitize_callback' => 'absint',
                    ],
                    'search' => [
                        'default' => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'source' => [
                        'default' => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/geopets/waitlist/(?P<id>[a-f0-9-]+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_waitlist_entry'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_waitlist_entry'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);

        // Stats endpoint
        register_rest_route($this->namespace, '/geopets/stats', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_stats'],
                'permission_callback' => [$this, 'check_admin_permission'],
                'args' => [
                    'period' => [
                        'default' => 'week',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);

        // Export endpoint
        register_rest_route($this->namespace, '/geopets/export', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'export_waitlist'],
                'permission_callback' => [$this, 'check_admin_permission'],
                'args' => [
                    'format' => [
                        'default' => 'csv',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);

        // Settings endpoints
        register_rest_route($this->namespace, '/geopets/settings', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_settings'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'update_settings'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);

        // Test connection endpoint
        register_rest_route($this->namespace, '/geopets/test-connection', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'test_connection'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);
    }

    /**
     * Check admin permission
     */
    public function check_admin_permission(): bool {
        return current_user_can('manage_options');
    }

    /**
     * Get waitlist entries
     */
    public function get_waitlist(\WP_REST_Request $request): \WP_REST_Response {
        if (!$this->is_configured()) {
            return $this->error('Supabase not configured', 'not_configured', 400);
        }

        $page = $request->get_param('page');
        $per_page = min($request->get_param('per_page'), 100);
        $search = $request->get_param('search');
        $source = $request->get_param('source');

        $offset = ($page - 1) * $per_page;

        $params = [
            'select' => 'id,email,source,referral_code,metadata,created_at',
            'order' => 'created_at.desc',
            'limit' => $per_page,
            'offset' => $offset,
        ];

        if (!empty($search)) {
            $params['email'] = 'ilike.*' . $search . '*';
        }

        if (!empty($source)) {
            $params['source'] = 'eq.' . $source;
        }

        $response = $this->supabase_request('waitlist', $params);

        if (is_wp_error($response)) {
            return $this->server_error($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $headers = wp_remote_retrieve_headers($response);

        // Parse total from content-range header
        $total = 0;
        if (isset($headers['content-range'])) {
            preg_match('/\/(\d+)$/', $headers['content-range'], $matches);
            $total = (int) ($matches[1] ?? 0);
        }

        return $this->paginated($body ?? [], $total, $page, $per_page);
    }

    /**
     * Get single waitlist entry
     */
    public function get_waitlist_entry(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        if (!$this->is_configured()) {
            return $this->error('Supabase not configured', 'not_configured', 400);
        }

        $id = $request->get_param('id');

        $response = $this->supabase_request('waitlist', [
            'select' => '*',
            'id' => 'eq.' . $id,
        ]);

        if (is_wp_error($response)) {
            return $this->server_error($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body)) {
            return $this->not_found('Entry not found');
        }

        return $this->success($body[0]);
    }

    /**
     * Delete waitlist entry
     */
    public function delete_waitlist_entry(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        if (!$this->is_configured()) {
            return $this->error('Supabase not configured', 'not_configured', 400);
        }

        $id = $request->get_param('id');

        $response = $this->supabase_request('waitlist', [
            'id' => 'eq.' . $id,
        ], 'DELETE');

        if (is_wp_error($response)) {
            return $this->server_error($response->get_error_message());
        }

        $status = wp_remote_retrieve_response_code($response);

        if ($status >= 400) {
            return $this->error('Failed to delete entry', 'delete_failed', $status);
        }

        // Clear stats cache
        delete_transient('geopets_stats_day');
        delete_transient('geopets_stats_week');
        delete_transient('geopets_stats_month');

        return $this->success(['deleted' => true]);
    }

    /**
     * Get statistics
     */
    public function get_stats(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        if (!$this->is_configured()) {
            return $this->error('Supabase not configured', 'not_configured', 400);
        }

        $period = $request->get_param('period');

        // Get total count
        $total_response = $this->supabase_request('waitlist', [
            'select' => 'id',
        ], 'HEAD');

        $total = 0;
        if (!is_wp_error($total_response)) {
            $headers = wp_remote_retrieve_headers($total_response);
            if (isset($headers['content-range'])) {
                preg_match('/\/(\d+)$/', $headers['content-range'], $matches);
                $total = (int) ($matches[1] ?? 0);
            }
        }

        // Get signups by source
        $source_response = $this->supabase_request('waitlist', [
            'select' => 'source',
        ]);

        $sources = [];
        if (!is_wp_error($source_response)) {
            $body = json_decode(wp_remote_retrieve_body($source_response), true);
            if (is_array($body)) {
                foreach ($body as $entry) {
                    $source = $entry['source'] ?? 'unknown';
                    $sources[$source] = ($sources[$source] ?? 0) + 1;
                }
            }
        }

        // Get daily signups for chart
        $chart_data = $this->get_chart_data($period);

        return $this->success([
            'total' => $total,
            'sources' => $sources,
            'chart' => $chart_data,
            'period' => $period,
        ]);
    }

    /**
     * Export waitlist data
     */
    public function export_waitlist(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        if (!$this->is_configured()) {
            return $this->error('Supabase not configured', 'not_configured', 400);
        }

        $format = $request->get_param('format');

        // Fetch all waitlist entries
        $response = $this->supabase_request('waitlist', [
            'select' => 'email,source,referral_code,created_at',
            'order' => 'created_at.desc',
            'limit' => 10000,
        ]);

        if (is_wp_error($response)) {
            return $this->server_error($response->get_error_message());
        }

        $entries = json_decode(wp_remote_retrieve_body($response), true) ?? [];

        if ($format === 'json') {
            return $this->success($entries);
        }

        // Generate CSV
        $csv_lines = ['Email,Source,Referral Code,Signup Date'];
        foreach ($entries as $entry) {
            $csv_lines[] = sprintf(
                '"%s","%s","%s","%s"',
                str_replace('"', '""', $entry['email'] ?? ''),
                str_replace('"', '""', $entry['source'] ?? ''),
                str_replace('"', '""', $entry['referral_code'] ?? ''),
                $entry['created_at'] ?? ''
            );
        }

        return new \WP_REST_Response(implode("\n", $csv_lines), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="geopets-waitlist-' . date('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Get settings
     */
    public function get_settings(): \WP_REST_Response {
        $settings = get_option('peanut_geopets_settings', []);

        // Mask the API key for security
        if (!empty($settings['supabase_key'])) {
            $settings['supabase_key_masked'] = substr($settings['supabase_key'], 0, 20) . '...';
            $settings['supabase_key'] = '';
        }

        $settings['is_configured'] = $this->is_configured();

        return $this->success($settings);
    }

    /**
     * Update settings
     */
    public function update_settings(\WP_REST_Request $request): \WP_REST_Response {
        $body = $request->get_json_params();

        $current = get_option('peanut_geopets_settings', []);

        $settings = [
            'supabase_url' => sanitize_url($body['supabase_url'] ?? $current['supabase_url'] ?? ''),
            'supabase_key' => sanitize_text_field($body['supabase_key'] ?? $current['supabase_key'] ?? ''),
        ];

        update_option('peanut_geopets_settings', $settings);

        // Clear stats cache
        delete_transient('geopets_stats_day');
        delete_transient('geopets_stats_week');
        delete_transient('geopets_stats_month');

        // Reload settings
        $this->load_settings();

        return $this->success(['saved' => true, 'is_configured' => $this->is_configured()]);
    }

    /**
     * Test Supabase connection
     */
    public function test_connection(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        $body = $request->get_json_params();

        $url = sanitize_url($body['supabase_url'] ?? $this->supabase_url);
        $key = sanitize_text_field($body['supabase_key'] ?? $this->supabase_key);

        if (empty($url) || empty($key)) {
            return $this->error('URL and API key required', 'missing_params', 400);
        }

        // Test by fetching a single row
        $test_url = rtrim($url, '/') . '/rest/v1/waitlist?limit=1';

        $response = wp_remote_get($test_url, [
            'headers' => [
                'apikey' => $key,
                'Authorization' => 'Bearer ' . $key,
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            return $this->server_error('Connection failed: ' . $response->get_error_message());
        }

        $status = wp_remote_retrieve_response_code($response);

        if ($status === 401) {
            return $this->unauthorized('Invalid API key');
        }

        if ($status >= 400) {
            return $this->error('Connection failed with status ' . $status, 'connection_failed', $status);
        }

        return $this->success([
            'connected' => true,
            'message' => 'Successfully connected to Supabase',
        ]);
    }

    /**
     * Check if Supabase is configured
     */
    private function is_configured(): bool {
        return !empty($this->supabase_url) && !empty($this->supabase_key);
    }

    /**
     * Make request to Supabase
     */
    private function supabase_request(string $table, array $params = [], string $method = 'GET'): array|\WP_Error {
        $url = rtrim($this->supabase_url, '/') . '/rest/v1/' . $table;

        $query_parts = [];
        foreach ($params as $key => $value) {
            if (in_array($key, ['head', 'limit', 'offset', 'order'])) {
                if ($key === 'head') continue;
                $query_parts[] = $key . '=' . urlencode($value);
            } else {
                $query_parts[] = urlencode($key) . '=' . urlencode($value);
            }
        }

        if (!empty($query_parts)) {
            $url .= '?' . implode('&', $query_parts);
        }

        $args = [
            'method' => $method,
            'headers' => [
                'apikey' => $this->supabase_key,
                'Authorization' => 'Bearer ' . $this->supabase_key,
                'Content-Type' => 'application/json',
                'Prefer' => 'count=exact',
            ],
            'timeout' => 15,
        ];

        return wp_remote_request($url, $args);
    }

    /**
     * Get chart data for signups over time
     */
    private function get_chart_data(string $period): array {
        $days = match($period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            'year' => 365,
            default => 7,
        };

        $start_date = new DateTime('-' . $days . ' days', new DateTimeZone('UTC'));

        $response = $this->supabase_request('waitlist', [
            'select' => 'created_at',
            'created_at' => 'gte.' . $start_date->format('Y-m-d\TH:i:s\Z'),
            'order' => 'created_at.asc',
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $entries = json_decode(wp_remote_retrieve_body($response), true) ?? [];

        // Group by date
        $by_date = [];
        foreach ($entries as $entry) {
            $date = substr($entry['created_at'], 0, 10);
            $by_date[$date] = ($by_date[$date] ?? 0) + 1;
        }

        // Fill in missing dates
        $chart = [];
        $current = clone $start_date;
        $end = new DateTime('now', new DateTimeZone('UTC'));

        while ($current <= $end) {
            $date = $current->format('Y-m-d');
            $chart[] = [
                'date' => $date,
                'count' => $by_date[$date] ?? 0,
            ];
            $current->modify('+1 day');
        }

        return $chart;
    }
}

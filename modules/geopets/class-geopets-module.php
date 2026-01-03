<?php
/**
 * GEOPETS Module
 *
 * Integrates GEOPETS waitlist and analytics data into Peanut Suite dashboard.
 * Connects to external Supabase API for data retrieval.
 *
 * @package PeanutSuite
 * @since 2.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Geopets_Module {

    /**
     * Supabase API configuration
     */
    private string $supabase_url = '';
    private string $supabase_key = '';

    /**
     * Initialize module
     */
    public function init(): void {
        $this->load_settings();

        add_action('peanut_register_routes', [$this, 'register_routes']);
        add_filter('peanut_dashboard_stats', [$this, 'add_dashboard_stats'], 10, 2);
        add_filter('peanut_dashboard_timeline', [$this, 'add_timeline_events'], 10, 2);
        add_filter('peanut_module_cards', [$this, 'add_module_card']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Load Supabase settings from WordPress options
     */
    private function load_settings(): void {
        $settings = get_option('peanut_geopets_settings', []);
        $this->supabase_url = $settings['supabase_url'] ?? '';
        $this->supabase_key = $settings['supabase_key'] ?? '';
    }

    /**
     * Register REST API routes
     */
    public function register_routes(): void {
        require_once __DIR__ . '/api/class-geopets-controller.php';
        $controller = new Geopets_Controller();
        $controller->register_routes();
    }

    /**
     * Register settings fields
     */
    public function register_settings(): void {
        register_setting('peanut_geopets', 'peanut_geopets_settings', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default' => [],
        ]);
    }

    /**
     * Sanitize settings input
     */
    public function sanitize_settings(array $input): array {
        return [
            'supabase_url' => sanitize_url($input['supabase_url'] ?? ''),
            'supabase_key' => sanitize_text_field($input['supabase_key'] ?? ''),
        ];
    }

    /**
     * Add GEOPETS stats to dashboard
     */
    public function add_dashboard_stats(array $stats, string $period): array {
        $waitlist_stats = $this->get_waitlist_stats($period);

        $stats['geopets'] = [
            'label' => 'GEOPETS Waitlist',
            'icon' => 'pets',
            'color' => '#10b981',
            'items' => [
                [
                    'label' => 'Total Signups',
                    'value' => $waitlist_stats['total'],
                    'change' => $waitlist_stats['change'],
                ],
                [
                    'label' => 'This ' . ucfirst($period),
                    'value' => $waitlist_stats['period_count'],
                ],
                [
                    'label' => 'Conversion Rate',
                    'value' => $waitlist_stats['conversion_rate'] . '%',
                ],
            ],
        ];

        return $stats;
    }

    /**
     * Add GEOPETS events to timeline
     */
    public function add_timeline_events(array $events, string $period): array {
        $waitlist_entries = $this->fetch_recent_signups(10);

        foreach ($waitlist_entries as $entry) {
            $events[] = [
                'type' => 'geopets_signup',
                'icon' => 'user-plus',
                'color' => '#10b981',
                'title' => 'New GEOPETS waitlist signup',
                'description' => $this->mask_email($entry['email']),
                'timestamp' => strtotime($entry['created_at']),
                'meta' => [
                    'source' => $entry['source'] ?? 'website',
                ],
            ];
        }

        return $events;
    }

    /**
     * Add module card for dashboard overview
     */
    public function add_module_card(array $cards): array {
        $stats = $this->get_waitlist_stats('week');

        $cards['geopets'] = [
            'title' => 'GEOPETS',
            'description' => 'AR pet collection game waitlist',
            'icon' => 'gamepad-2',
            'color' => '#10b981',
            'stats' => [
                ['label' => 'Waitlist', 'value' => $stats['total']],
                ['label' => 'This Week', 'value' => '+' . $stats['period_count']],
            ],
            'actions' => [
                ['label' => 'View Waitlist', 'url' => admin_url('admin.php?page=peanut-geopets')],
                ['label' => 'Settings', 'url' => admin_url('admin.php?page=peanut-geopets&tab=settings')],
            ],
        ];

        return $cards;
    }

    /**
     * Get waitlist statistics for a given period
     */
    private function get_waitlist_stats(string $period): array {
        $cached = get_transient('geopets_stats_' . $period);
        if ($cached !== false) {
            return $cached;
        }

        $stats = [
            'total' => 0,
            'period_count' => 0,
            'change' => 0,
            'conversion_rate' => 0,
        ];

        if (empty($this->supabase_url) || empty($this->supabase_key)) {
            return $stats;
        }

        // Fetch total count
        $response = $this->supabase_request('waitlist', [
            'select' => 'id',
            'head' => true,
        ], 'HEAD');

        if (!is_wp_error($response)) {
            $headers = wp_remote_retrieve_headers($response);
            $stats['total'] = (int) ($headers['content-range'] ?? 0);

            // Parse content-range header (format: "0-9/42" where 42 is total)
            if (isset($headers['content-range'])) {
                preg_match('/\/(\d+)$/', $headers['content-range'], $matches);
                $stats['total'] = (int) ($matches[1] ?? 0);
            }
        }

        // Fetch period count
        $date_filter = $this->get_date_filter($period);
        $period_response = $this->supabase_request('waitlist', [
            'select' => 'id,created_at',
            'created_at' => 'gte.' . $date_filter,
        ]);

        if (!is_wp_error($period_response)) {
            $body = json_decode(wp_remote_retrieve_body($period_response), true);
            $stats['period_count'] = is_array($body) ? count($body) : 0;
        }

        // Calculate change percentage
        $previous_period = $this->get_previous_period_count($period);
        if ($previous_period > 0) {
            $stats['change'] = round((($stats['period_count'] - $previous_period) / $previous_period) * 100);
        }

        // Cache for 5 minutes
        set_transient('geopets_stats_' . $period, $stats, 5 * MINUTE_IN_SECONDS);

        return $stats;
    }

    /**
     * Fetch recent signups from Supabase
     */
    private function fetch_recent_signups(int $limit = 10): array {
        if (empty($this->supabase_url) || empty($this->supabase_key)) {
            return [];
        }

        $response = $this->supabase_request('waitlist', [
            'select' => 'id,email,source,created_at',
            'order' => 'created_at.desc',
            'limit' => $limit,
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($body) ? $body : [];
    }

    /**
     * Make a request to Supabase REST API
     */
    private function supabase_request(string $table, array $params = [], string $method = 'GET'): array|\WP_Error {
        $url = rtrim($this->supabase_url, '/') . '/rest/v1/' . $table;

        // Build query string from params
        $query_params = [];
        foreach ($params as $key => $value) {
            if ($key === 'head') continue;
            $query_params[] = urlencode($key) . '=' . urlencode($value);
        }

        if (!empty($query_params)) {
            $url .= '?' . implode('&', $query_params);
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
     * Get date filter for period queries
     */
    private function get_date_filter(string $period): string {
        $now = new DateTime('now', new DateTimeZone('UTC'));

        switch ($period) {
            case 'day':
                $now->modify('-1 day');
                break;
            case 'week':
                $now->modify('-7 days');
                break;
            case 'month':
                $now->modify('-30 days');
                break;
            case 'year':
                $now->modify('-365 days');
                break;
            default:
                $now->modify('-7 days');
        }

        return $now->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Get count from previous period for comparison
     */
    private function get_previous_period_count(string $period): int {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $start = clone $now;
        $end = clone $now;

        switch ($period) {
            case 'day':
                $start->modify('-2 days');
                $end->modify('-1 day');
                break;
            case 'week':
                $start->modify('-14 days');
                $end->modify('-7 days');
                break;
            case 'month':
                $start->modify('-60 days');
                $end->modify('-30 days');
                break;
            default:
                $start->modify('-14 days');
                $end->modify('-7 days');
        }

        $response = $this->supabase_request('waitlist', [
            'select' => 'id',
            'created_at' => 'gte.' . $start->format('Y-m-d\TH:i:s\Z'),
            'and' => '(created_at.lt.' . $end->format('Y-m-d\TH:i:s\Z') . ')',
        ]);

        if (is_wp_error($response)) {
            return 0;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($body) ? count($body) : 0;
    }

    /**
     * Mask email for privacy in timeline
     */
    private function mask_email(string $email): string {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***.***';
        }

        $local = $parts[0];
        $domain = $parts[1];

        $masked_local = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 2));
        $domain_parts = explode('.', $domain);
        $masked_domain = substr($domain_parts[0], 0, 1) . '***';

        return $masked_local . '@' . $masked_domain . '.' . end($domain_parts);
    }
}

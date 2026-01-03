<?php
/**
 * Dashboard Module
 *
 * Unified analytics dashboard aggregating data from all modules.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard_Module {

    /**
     * Initialize module
     */
    public function init(): void {
        // Register API routes
        add_action('peanut_register_routes', [$this, 'register_routes']);
    }

    /**
     * Register REST routes
     */
    public function register_routes(): void {
        // Dashboard overview
        register_rest_route(PEANUT_API_NAMESPACE, '/dashboard', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_dashboard'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'period' => [
                    'type' => 'string',
                    'default' => '30d',
                    'enum' => ['7d', '30d', '90d', 'year', 'all'],
                ],
            ],
        ]);

        // Activity feed
        register_rest_route(PEANUT_API_NAMESPACE, '/dashboard/activity', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_activity'],
            'permission_callback' => [$this, 'permission_callback'],
        ]);

        // Timeline data
        register_rest_route(PEANUT_API_NAMESPACE, '/dashboard/timeline', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_timeline'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'period' => [
                    'type' => 'string',
                    'default' => '30d',
                    'enum' => ['7d', '30d', '90d', 'year', 'all'],
                ],
            ],
        ]);
    }

    /**
     * Permission callback
     */
    public function permission_callback(WP_REST_Request $request): bool|WP_Error {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_not_logged_in', __('Authentication required.', 'peanut-suite'), ['status' => 401]);
        }
        return true;
    }

    /**
     * Get dashboard data
     */
    public function get_dashboard(WP_REST_Request $request): WP_REST_Response {
        $period = $request->get_param('period');
        $user_id = get_current_user_id();

        // Collect stats from all modules
        $stats = apply_filters('peanut_dashboard_stats', [
            'period' => $period,
        ], $period);

        // Add overview metrics
        $stats['overview'] = $this->get_overview_stats($user_id, $period);

        // Add timeline data
        $stats['timeline'] = $this->get_timeline_data($user_id, $period);

        // Add recent items
        $stats['recent'] = $this->get_recent_items($user_id);

        return new WP_REST_Response([
            'success' => true,
            'data' => $stats,
        ], 200);
    }

    /**
     * Get timeline data endpoint
     */
    public function get_timeline(WP_REST_Request $request): WP_REST_Response {
        $period = $request->get_param('period') ?? '30d';
        $user_id = get_current_user_id();

        $timeline = $this->get_timeline_data($user_id, $period);

        // Transform data for frontend
        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            'year' => 365,
            default => 30,
        };

        // Create date-indexed arrays
        $clicks_by_date = [];
        foreach ($timeline['clicks'] ?? [] as $row) {
            $clicks_by_date[$row['date']] = (int) $row['count'];
        }

        $contacts_by_date = [];
        foreach ($timeline['contacts'] ?? [] as $row) {
            $contacts_by_date[$row['date']] = (int) $row['count'];
        }

        // Generate response with all dates
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = gmdate('Y-m-d', strtotime("-$i days"));
            $result[] = [
                'date' => $date,
                'utm_clicks' => 0, // Planned feature: UTM click aggregation
                'link_clicks' => $clicks_by_date[$date] ?? 0,
                'contacts' => $contacts_by_date[$date] ?? 0,
                'conversions' => 0, // Planned feature: Conversion tracking
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $result,
        ], 200);
    }

    /**
     * Get overview stats
     */
    private function get_overview_stats(int $user_id, string $period): array {
        global $wpdb;

        $date_clause = $this->get_date_clause($period);

        // UTMs created
        $utms_table = Peanut_Database::utms_table();
        $utms_total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $utms_table WHERE user_id = %d AND is_archived = 0",
            $user_id
        ));
        $utms_period = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $utms_table WHERE user_id = %d AND is_archived = 0 $date_clause",
            $user_id
        ));

        // Links
        $links_table = Peanut_Database::links_table();
        $links_total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $links_table WHERE user_id = %d",
            $user_id
        ));
        $links_clicks = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(click_count) FROM $links_table WHERE user_id = %d",
            $user_id
        ));

        // Contacts
        $contacts_table = Peanut_Database::contacts_table();
        $contacts_total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $contacts_table WHERE user_id = %d",
            $user_id
        ));
        $contacts_period = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $contacts_table WHERE user_id = %d $date_clause",
            $user_id
        ));

        return [
            'campaigns' => [
                'total' => $utms_total,
                'period' => $utms_period,
                'label' => __('Campaigns', 'peanut-suite'),
            ],
            'links' => [
                'total' => $links_total,
                'clicks' => $links_clicks,
                'label' => __('Links', 'peanut-suite'),
            ],
            'contacts' => [
                'total' => $contacts_total,
                'period' => $contacts_period,
                'label' => __('Contacts', 'peanut-suite'),
            ],
        ];
    }

    /**
     * Get timeline data for charts
     */
    private function get_timeline_data(int $user_id, string $period): array {
        global $wpdb;

        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            'year' => 365,
            default => 30,
        };

        $clicks_table = Peanut_Database::link_clicks_table();
        $links_table = Peanut_Database::links_table();
        $contacts_table = Peanut_Database::contacts_table();

        // Link clicks over time
        $clicks = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(c.clicked_at) as date, COUNT(*) as count
             FROM $clicks_table c
             JOIN $links_table l ON c.link_id = l.id
             WHERE l.user_id = %d AND c.clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(c.clicked_at)
             ORDER BY date",
            $user_id,
            $days
        ), ARRAY_A);

        // New contacts over time
        $contacts = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count
             FROM $contacts_table
             WHERE user_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(created_at)
             ORDER BY date",
            $user_id,
            $days
        ), ARRAY_A);

        return [
            'clicks' => $clicks,
            'contacts' => $contacts,
        ];
    }

    /**
     * Get recent items from all modules
     */
    private function get_recent_items(int $user_id): array {
        global $wpdb;

        // Recent UTMs
        $utms_table = Peanut_Database::utms_table();
        $recent_utms = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, utm_campaign, click_count, created_at
             FROM $utms_table
             WHERE user_id = %d AND is_archived = 0
             ORDER BY created_at DESC LIMIT 5",
            $user_id
        ), ARRAY_A);

        // Recent links
        $links_table = Peanut_Database::links_table();
        $recent_links = $wpdb->get_results($wpdb->prepare(
            "SELECT id, slug, title, click_count, created_at
             FROM $links_table
             WHERE user_id = %d
             ORDER BY created_at DESC LIMIT 5",
            $user_id
        ), ARRAY_A);

        // Recent contacts
        $contacts_table = Peanut_Database::contacts_table();
        $recent_contacts = $wpdb->get_results($wpdb->prepare(
            "SELECT id, email, first_name, last_name, status, source, created_at
             FROM $contacts_table
             WHERE user_id = %d
             ORDER BY created_at DESC LIMIT 5",
            $user_id
        ), ARRAY_A);

        return [
            'campaigns' => array_map(function ($item) {
                return [
                    'id' => (int) $item['id'],
                    'name' => $item['name'],
                    'campaign' => $item['utm_campaign'],
                    'clicks' => (int) $item['click_count'],
                    'created_at' => $item['created_at'],
                    'type' => 'campaign',
                ];
            }, $recent_utms),

            'links' => array_map(function ($item) {
                return [
                    'id' => (int) $item['id'],
                    'slug' => $item['slug'],
                    'title' => $item['title'],
                    'clicks' => (int) $item['click_count'],
                    'created_at' => $item['created_at'],
                    'type' => 'link',
                ];
            }, $recent_links),

            'contacts' => array_map(function ($item) {
                return [
                    'id' => (int) $item['id'],
                    'email' => $item['email'],
                    'name' => trim("{$item['first_name']} {$item['last_name']}"),
                    'status' => $item['status'],
                    'source' => $item['source'],
                    'created_at' => $item['created_at'],
                    'type' => 'contact',
                ];
            }, $recent_contacts),
        ];
    }

    /**
     * Get activity feed
     */
    public function get_activity(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $user_id = get_current_user_id();

        $activities = [];

        // Recent link clicks
        $clicks_table = Peanut_Database::link_clicks_table();
        $links_table = Peanut_Database::links_table();

        $clicks = $wpdb->get_results($wpdb->prepare(
            "SELECT c.clicked_at, l.slug, l.title
             FROM $clicks_table c
             JOIN $links_table l ON c.link_id = l.id
             WHERE l.user_id = %d
             ORDER BY c.clicked_at DESC LIMIT 20",
            $user_id
        ), ARRAY_A);

        foreach ($clicks as $click) {
            $activities[] = [
                'type' => 'link_click',
                'message' => sprintf(__('Link "%s" was clicked', 'peanut-suite'), $click['title'] ?: $click['slug']),
                'timestamp' => $click['clicked_at'],
            ];
        }

        // Recent contact activities
        $contacts_table = Peanut_Database::contacts_table();
        $contact_activities_table = Peanut_Database::contact_activities_table();

        $contact_acts = $wpdb->get_results($wpdb->prepare(
            "SELECT a.type, a.description, a.created_at, c.email
             FROM $contact_activities_table a
             JOIN $contacts_table c ON a.contact_id = c.id
             WHERE c.user_id = %d
             ORDER BY a.created_at DESC LIMIT 20",
            $user_id
        ), ARRAY_A);

        foreach ($contact_acts as $act) {
            $activities[] = [
                'type' => 'contact_' . $act['type'],
                'message' => $act['description'] . " ({$act['email']})",
                'timestamp' => $act['created_at'],
            ];
        }

        // Sort by timestamp
        usort($activities, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));

        return new WP_REST_Response([
            'success' => true,
            'data' => array_slice($activities, 0, 30),
        ], 200);
    }

    /**
     * Get date clause for period
     */
    private function get_date_clause(string $period): string {
        return match ($period) {
            '7d' => "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            '30d' => "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            '90d' => "AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
            'year' => "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)",
            default => '',
        };
    }
}

<?php
/**
 * Backlink Discovery Module
 *
 * Find and track sites linking to you.
 * Features:
 * - Discover backlinks via search and crawling
 * - Verify links are still live
 * - Alert on lost backlinks
 * - Track link metrics (anchor text, follow/nofollow)
 *
 * Pro tier feature.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Backlinks_Module {

    /**
     * Initialize module
     */
    public function init(): void {
        // Register API routes
        add_action('peanut_register_routes', [$this, 'register_routes']);

        // Schedule cron jobs
        add_action('init', [$this, 'schedule_cron']);
        add_action('peanut_backlinks_verify', [$this, 'verify_backlinks']);
        add_action('peanut_backlinks_discover', [$this, 'discover_backlinks']);

        // Dashboard stats
        add_filter('peanut_dashboard_stats', [$this, 'add_dashboard_stats'], 10, 2);
    }

    /**
     * Register REST routes
     */
    public function register_routes(): void {
        register_rest_route(PEANUT_API_NAMESPACE, '/backlinks', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_backlinks'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
        ]);

        register_rest_route(PEANUT_API_NAMESPACE, '/backlinks/discover', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'trigger_discovery'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route(PEANUT_API_NAMESPACE, '/backlinks/verify', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'trigger_verify'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route(PEANUT_API_NAMESPACE, '/backlinks/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_backlink'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route(PEANUT_API_NAMESPACE, '/backlinks/settings', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_settings'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'update_settings'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
        ]);
    }

    /**
     * Schedule cron events
     */
    public function schedule_cron(): void {
        // Verify existing backlinks weekly
        if (!wp_next_scheduled('peanut_backlinks_verify')) {
            wp_schedule_event(time(), 'weekly', 'peanut_backlinks_verify');
        }

        // Discover new backlinks weekly (offset by a few days)
        if (!wp_next_scheduled('peanut_backlinks_discover')) {
            wp_schedule_event(time() + (3 * DAY_IN_SECONDS), 'weekly', 'peanut_backlinks_discover');
        }
    }

    /**
     * Get backlinks table name
     */
    private function table_name(): string {
        global $wpdb;
        return $wpdb->prefix . PEANUT_TABLE_PREFIX . 'backlinks';
    }

    /**
     * Create database table
     */
    public static function create_tables(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . PEANUT_TABLE_PREFIX . 'backlinks';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_url varchar(500) NOT NULL,
            source_domain varchar(255) NOT NULL,
            target_url varchar(500) NOT NULL,
            anchor_text varchar(500) DEFAULT '',
            link_type enum('dofollow','nofollow','ugc','sponsored') DEFAULT 'dofollow',
            status enum('active','lost','broken','pending') DEFAULT 'pending',
            first_seen datetime DEFAULT CURRENT_TIMESTAMP,
            last_checked datetime DEFAULT NULL,
            last_seen datetime DEFAULT NULL,
            domain_authority int DEFAULT NULL,
            page_authority int DEFAULT NULL,
            discovery_source varchar(50) DEFAULT 'manual',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY source_target (source_url(191), target_url(191)),
            KEY source_domain (source_domain),
            KEY status (status),
            KEY link_type (link_type),
            KEY first_seen (first_seen)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Get backlinks via API
     */
    public function get_backlinks(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $table = $this->table_name();

        $status = $request->get_param('status');
        $domain = $request->get_param('domain');
        $page = max(1, (int) $request->get_param('page'));
        $per_page = min(100, max(10, (int) ($request->get_param('per_page') ?? 25)));
        $offset = ($page - 1) * $per_page;

        $where = ['1=1'];
        $params = [];

        if ($status) {
            $where[] = 'status = %s';
            $params[] = $status;
        }

        if ($domain) {
            $where[] = 'source_domain LIKE %s';
            $params[] = '%' . $wpdb->esc_like($domain) . '%';
        }

        $where_sql = implode(' AND ', $where);

        // Get total count
        $total_query = "SELECT COUNT(*) FROM $table WHERE $where_sql";
        $total = (int) $wpdb->get_var($params ? $wpdb->prepare($total_query, $params) : $total_query);

        // Get results
        $query = "SELECT * FROM $table WHERE $where_sql ORDER BY first_seen DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $backlinks = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

        // Get stats
        $stats = $this->get_stats();

        return new WP_REST_Response([
            'backlinks' => $backlinks,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page),
            'stats' => $stats,
        ], 200);
    }

    /**
     * Get backlink stats
     */
    private function get_stats(): array {
        global $wpdb;
        $table = $this->table_name();

        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost,
                SUM(CASE WHEN status = 'broken' THEN 1 ELSE 0 END) as broken,
                SUM(CASE WHEN link_type = 'dofollow' THEN 1 ELSE 0 END) as dofollow,
                SUM(CASE WHEN link_type = 'nofollow' THEN 1 ELSE 0 END) as nofollow,
                COUNT(DISTINCT source_domain) as unique_domains,
                SUM(CASE WHEN first_seen >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_30_days,
                SUM(CASE WHEN status = 'lost' AND last_checked >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as lost_7_days
            FROM $table",
            ARRAY_A
        );

        return [
            'total' => (int) ($stats['total'] ?? 0),
            'active' => (int) ($stats['active'] ?? 0),
            'lost' => (int) ($stats['lost'] ?? 0),
            'broken' => (int) ($stats['broken'] ?? 0),
            'dofollow' => (int) ($stats['dofollow'] ?? 0),
            'nofollow' => (int) ($stats['nofollow'] ?? 0),
            'unique_domains' => (int) ($stats['unique_domains'] ?? 0),
            'new_30_days' => (int) ($stats['new_30_days'] ?? 0),
            'lost_7_days' => (int) ($stats['lost_7_days'] ?? 0),
        ];
    }

    /**
     * Trigger discovery process
     */
    public function trigger_discovery(WP_REST_Request $request): WP_REST_Response {
        $discovered = $this->discover_backlinks();

        return new WP_REST_Response([
            'success' => true,
            'discovered' => $discovered,
            'message' => sprintf(
                __('Discovery complete. Found %d new backlinks.', 'peanut-suite'),
                $discovered
            ),
        ], 200);
    }

    /**
     * Trigger verification
     */
    public function trigger_verify(WP_REST_Request $request): WP_REST_Response {
        $results = $this->verify_backlinks();

        return new WP_REST_Response([
            'success' => true,
            'verified' => $results['verified'],
            'lost' => $results['lost'],
            'message' => sprintf(
                __('Verification complete. %d active, %d lost.', 'peanut-suite'),
                $results['verified'],
                $results['lost']
            ),
        ], 200);
    }

    /**
     * Delete a backlink record
     */
    public function delete_backlink(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $id = (int) $request->get_param('id');

        $deleted = $wpdb->delete($this->table_name(), ['id' => $id]);

        if ($deleted) {
            return new WP_REST_Response(['success' => true], 200);
        }

        return new WP_REST_Response(['error' => 'Failed to delete'], 500);
    }

    /**
     * Get settings
     */
    public function get_settings(WP_REST_Request $request): WP_REST_Response {
        $settings = get_option('peanut_backlinks_settings', $this->get_default_settings());

        return new WP_REST_Response($settings, 200);
    }

    /**
     * Update settings
     */
    public function update_settings(WP_REST_Request $request): WP_REST_Response {
        $data = $request->get_json_params();
        $settings = wp_parse_args($data, $this->get_default_settings());

        // Sanitize
        $settings['alert_on_lost'] = (bool) $settings['alert_on_lost'];
        $settings['alert_email'] = sanitize_email($settings['alert_email']);
        $settings['auto_verify_days'] = absint($settings['auto_verify_days']);
        $settings['target_domains'] = array_filter(array_map('sanitize_text_field', $settings['target_domains'] ?? []));

        update_option('peanut_backlinks_settings', $settings);

        return new WP_REST_Response(['success' => true, 'settings' => $settings], 200);
    }

    /**
     * Get default settings
     */
    private function get_default_settings(): array {
        return [
            'alert_on_lost' => true,
            'alert_email' => get_option('admin_email'),
            'auto_verify_days' => 7,
            'target_domains' => [wp_parse_url(home_url(), PHP_URL_HOST)],
        ];
    }

    /**
     * Discover new backlinks
     */
    public function discover_backlinks(): int {
        $settings = get_option('peanut_backlinks_settings', $this->get_default_settings());
        $domains = $settings['target_domains'] ?? [wp_parse_url(home_url(), PHP_URL_HOST)];
        $discovered = 0;

        foreach ($domains as $domain) {
            // Method 1: Check referrers from analytics if available
            $discovered += $this->discover_from_referrers($domain);

            // Method 2: Search for mentions using web search simulation
            $discovered += $this->discover_from_mentions($domain);
        }

        return $discovered;
    }

    /**
     * Discover backlinks from referrer data
     */
    private function discover_from_referrers(string $domain): int {
        global $wpdb;

        // Check if we have visitor tracking data
        $visitors_table = $wpdb->prefix . PEANUT_TABLE_PREFIX . 'pageviews';
        if ($wpdb->get_var("SHOW TABLES LIKE '$visitors_table'") !== $visitors_table) {
            return 0;
        }

        // Get unique external referrers
        $referrers = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT referrer_url
             FROM $visitors_table
             WHERE referrer_url != ''
             AND referrer_url NOT LIKE %s
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             LIMIT 100",
            '%' . $wpdb->esc_like($domain) . '%'
        ), ARRAY_A);

        $discovered = 0;

        foreach ($referrers as $row) {
            $referrer = $row['referrer_url'];
            if ($this->add_backlink($referrer, home_url(), 'referrer')) {
                $discovered++;
            }
        }

        return $discovered;
    }

    /**
     * Discover from mentions/search
     */
    private function discover_from_mentions(string $domain): int {
        // This would ideally integrate with a backlink API service
        // For now, we provide a framework for manual addition and referrer tracking
        return 0;
    }

    /**
     * Add a new backlink
     */
    public function add_backlink(string $source_url, string $target_url, string $source_type = 'manual'): bool {
        global $wpdb;
        $table = $this->table_name();

        // Validate URLs
        if (!filter_var($source_url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $source_domain = wp_parse_url($source_url, PHP_URL_HOST);
        if (!$source_domain) {
            return false;
        }

        // Check if already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE source_url = %s AND target_url = %s",
            $source_url,
            $target_url
        ));

        if ($exists) {
            return false;
        }

        // Insert new backlink
        $inserted = $wpdb->insert($table, [
            'source_url' => $source_url,
            'source_domain' => $source_domain,
            'target_url' => $target_url,
            'status' => 'pending',
            'discovery_source' => $source_type,
            'first_seen' => current_time('mysql'),
        ]);

        return (bool) $inserted;
    }

    /**
     * Verify existing backlinks
     */
    public function verify_backlinks(): array {
        global $wpdb;
        $table = $this->table_name();
        $settings = get_option('peanut_backlinks_settings', $this->get_default_settings());

        $results = ['verified' => 0, 'lost' => 0, 'broken' => 0];
        $lost_links = [];

        // Get backlinks that need verification
        $verify_days = $settings['auto_verify_days'] ?? 7;
        $backlinks = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
             WHERE (last_checked IS NULL OR last_checked < DATE_SUB(NOW(), INTERVAL %d DAY))
             AND status IN ('active', 'pending')
             ORDER BY last_checked ASC
             LIMIT 50",
            $verify_days
        ));

        foreach ($backlinks as $backlink) {
            $result = $this->verify_single_backlink($backlink);

            if ($result['status'] === 'active') {
                $results['verified']++;
            } elseif ($result['status'] === 'lost') {
                $results['lost']++;
                $lost_links[] = $backlink;
            } else {
                $results['broken']++;
            }

            // Update database
            $wpdb->update($table, [
                'status' => $result['status'],
                'anchor_text' => $result['anchor_text'] ?? $backlink->anchor_text,
                'link_type' => $result['link_type'] ?? $backlink->link_type,
                'last_checked' => current_time('mysql'),
                'last_seen' => $result['status'] === 'active' ? current_time('mysql') : $backlink->last_seen,
            ], ['id' => $backlink->id]);

            // Small delay to be respectful
            usleep(500000); // 0.5 seconds
        }

        // Send alert for lost links
        if (!empty($lost_links) && ($settings['alert_on_lost'] ?? true)) {
            $this->send_lost_alert($lost_links);
        }

        return $results;
    }

    /**
     * Verify a single backlink
     */
    private function verify_single_backlink(object $backlink): array {
        $response = wp_remote_get($backlink->source_url, [
            'timeout' => 15,
            'sslverify' => false,
            'user-agent' => 'Mozilla/5.0 (compatible; PeanutSuite/1.0; +' . home_url() . ')',
        ]);

        if (is_wp_error($response)) {
            return ['status' => 'broken'];
        }

        $status_code = wp_remote_retrieve_response_code($response);

        // Page not accessible
        if ($status_code >= 400) {
            return ['status' => 'broken'];
        }

        $body = wp_remote_retrieve_body($response);

        // Look for our link in the page
        $target_domain = wp_parse_url($backlink->target_url, PHP_URL_HOST);

        // Check if page contains link to our domain
        if (stripos($body, $target_domain) === false) {
            return ['status' => 'lost'];
        }

        // Try to extract link details using regex
        $result = [
            'status' => 'active',
            'anchor_text' => '',
            'link_type' => 'dofollow',
        ];

        // Find the actual link
        $pattern = '/<a[^>]*href=["\']([^"\']*' . preg_quote($target_domain, '/') . '[^"\']*)["\'][^>]*>(.*?)<\/a>/is';
        if (preg_match($pattern, $body, $matches)) {
            $link_html = $matches[0];
            $result['anchor_text'] = strip_tags($matches[2]);

            // Check for nofollow
            if (stripos($link_html, 'nofollow') !== false) {
                $result['link_type'] = 'nofollow';
            }
            if (stripos($link_html, 'ugc') !== false) {
                $result['link_type'] = 'ugc';
            }
            if (stripos($link_html, 'sponsored') !== false) {
                $result['link_type'] = 'sponsored';
            }
        }

        return $result;
    }

    /**
     * Send alert for lost backlinks
     */
    private function send_lost_alert(array $lost_links): void {
        $settings = get_option('peanut_backlinks_settings', $this->get_default_settings());
        $email = $settings['alert_email'] ?? get_option('admin_email');

        $subject = sprintf(
            __('[Peanut Suite] %d Lost Backlinks Detected', 'peanut-suite'),
            count($lost_links)
        );

        $message = __("The following backlinks are no longer active:\n\n", 'peanut-suite');

        foreach ($lost_links as $link) {
            $message .= sprintf(
                "- %s\n  From: %s\n  First seen: %s\n\n",
                $link->target_url,
                $link->source_url,
                $link->first_seen
            );
        }

        $message .= sprintf(
            __("\nView all backlinks: %s", 'peanut-suite'),
            admin_url('admin.php?page=peanut-backlinks')
        );

        wp_mail($email, $subject, $message);
    }

    /**
     * Add stats to dashboard
     */
    public function add_dashboard_stats(array $stats, string $period): array {
        $backlink_stats = $this->get_stats();

        $stats['backlinks_total'] = $backlink_stats['total'];
        $stats['backlinks_active'] = $backlink_stats['active'];
        $stats['backlinks_lost'] = $backlink_stats['lost'];
        $stats['backlinks_domains'] = $backlink_stats['unique_domains'];
        $stats['backlinks_new'] = $backlink_stats['new_30_days'];

        return $stats;
    }

    /**
     * Manual add backlink (via API)
     */
    public function add_manual_backlink(string $source_url, string $target_url = '', array $meta = []): int|WP_Error {
        global $wpdb;
        $table = $this->table_name();

        if (empty($target_url)) {
            $target_url = home_url();
        }

        // Validate source URL
        if (!filter_var($source_url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', __('Invalid source URL', 'peanut-suite'));
        }

        $source_domain = wp_parse_url($source_url, PHP_URL_HOST);

        // Check for duplicate
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE source_url = %s",
            $source_url
        ));

        if ($exists) {
            return new WP_Error('duplicate', __('This backlink already exists', 'peanut-suite'));
        }

        $wpdb->insert($table, [
            'source_url' => $source_url,
            'source_domain' => $source_domain,
            'target_url' => $target_url,
            'anchor_text' => sanitize_text_field($meta['anchor_text'] ?? ''),
            'link_type' => in_array($meta['link_type'] ?? '', ['dofollow', 'nofollow', 'ugc', 'sponsored'])
                ? $meta['link_type']
                : 'dofollow',
            'status' => 'pending',
            'discovery_source' => 'manual',
            'notes' => sanitize_textarea_field($meta['notes'] ?? ''),
            'first_seen' => current_time('mysql'),
        ]);

        return $wpdb->insert_id;
    }

    /**
     * Deactivate - clean up cron
     */
    public function deactivate(): void {
        wp_clear_scheduled_hook('peanut_backlinks_verify');
        wp_clear_scheduled_hook('peanut_backlinks_discover');
    }
}

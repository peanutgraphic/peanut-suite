<?php
/**
 * Monitor Sites Manager
 *
 * Handles connected site registration, verification, and management.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-monitor-database.php';

class Monitor_Sites {

    /**
     * Get all active sites for current user's account
     */
    public function get_all_active(?int $user_id = null): array {
        global $wpdb;
        $table = Monitor_Database::sites_table();
        $current_user_id = $user_id ?? get_current_user_id();

        // Get all user IDs in the same account
        // Use get_or_create_for_user to ensure member entry exists
        $account_user_ids = [$current_user_id];
        if (class_exists('Peanut_Account_Service')) {
            $account = Peanut_Account_Service::get_or_create_for_user($current_user_id);
            if ($account) {
                $members = Peanut_Account_Service::get_members($account['id']);
                $account_user_ids = array_map(fn($m) => $m['user_id'], $members);
            }
        }

        $placeholders = implode(',', array_fill(0, count($account_user_ids), '%d'));
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id IN ($placeholders) AND status = 'active' ORDER BY site_name ASC",
            ...$account_user_ids
        ));
    }

    /**
     * Get all sites for current user's account (with optional filters)
     *
     * Shows all sites created by any member of the same account.
     */
    public function get_all(array $args = []): array {
        global $wpdb;
        $table = Monitor_Database::sites_table();
        $current_user_id = $args['user_id'] ?? get_current_user_id();

        // Get all user IDs in the same account for team-based access
        // Use get_or_create_for_user to ensure member entry exists
        $account_user_ids = [$current_user_id];
        if (class_exists('Peanut_Account_Service')) {
            $account = Peanut_Account_Service::get_or_create_for_user($current_user_id);
            if ($account) {
                $members = Peanut_Account_Service::get_members($account['id']);
                $account_user_ids = array_map(fn($m) => $m['user_id'], $members);
            }
        }

        // Build IN clause for all account members
        $placeholders = implode(',', array_fill(0, count($account_user_ids), '%d'));
        $where = ["user_id IN ($placeholders)"];
        $values = $account_user_ids;

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['search'])) {
            $where[] = '(site_url LIKE %s OR site_name LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $search;
            $values[] = $search;
        }

        $where_clause = implode(' AND ', $where);
        $order_by = $args['order_by'] ?? 'site_name';
        $order = $args['order'] ?? 'ASC';

        // Pagination
        $per_page = (int) ($args['per_page'] ?? 20);
        $page = (int) ($args['page'] ?? 1);
        $offset = ($page - 1) * $per_page;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE $where_clause ORDER BY $order_by $order LIMIT %d OFFSET %d",
            array_merge($values, [$per_page, $offset])
        ));

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE $where_clause",
            $values
        ));

        return [
            'items' => $results,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'page' => $page,
        ];
    }

    /**
     * Get single site by ID
     *
     * Checks access via account membership - all team members can access sites
     * created by anyone in the same account.
     *
     * Uses the same access control logic as get_all() for consistency:
     * builds a list of allowed user IDs based on account membership.
     */
    public function get(int $id): ?object {
        global $wpdb;
        $table = Monitor_Database::sites_table();
        $current_user_id = get_current_user_id();

        // Build list of allowed user IDs (same logic as get_all)
        $account_user_ids = [$current_user_id];
        if (class_exists('Peanut_Account_Service')) {
            $account = Peanut_Account_Service::get_or_create_for_user($current_user_id);
            if ($account) {
                $members = Peanut_Account_Service::get_members($account['id']);
                if (!empty($members)) {
                    $account_user_ids = array_map(fn($m) => (int) $m['user_id'], $members);
                }
            }
        }

        // Build IN clause for allowed user IDs
        $placeholders = implode(',', array_fill(0, count($account_user_ids), '%d'));

        // Query with access control
        $site = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND user_id IN ($placeholders)",
            array_merge([$id], $account_user_ids)
        ));

        return $site ?: null;
    }

    /**
     * Get site by URL
     */
    public function get_by_url(string $url): ?object {
        global $wpdb;
        $table = Monitor_Database::sites_table();
        $user_id = get_current_user_id();
        $url = $this->normalize_url($url);

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE site_url = %s AND user_id = %d",
            $url,
            $user_id
        ));
    }

    /**
     * Add a new site connection
     */
    public function add(array $data): int|WP_Error {
        global $wpdb;
        $table = Monitor_Database::sites_table();
        $user_id = get_current_user_id();

        // Validate required fields
        if (empty($data['site_url'])) {
            return new WP_Error('missing_url', __('Site URL is required.', 'peanut-suite'));
        }

        if (empty($data['site_key'])) {
            return new WP_Error('missing_key', __('Site key is required.', 'peanut-suite'));
        }

        $site_url = $this->normalize_url($data['site_url']);

        // Check if site already exists
        $existing = $this->get_by_url($site_url);
        if ($existing) {
            return new WP_Error('site_exists', __('This site is already connected.', 'peanut-suite'));
        }

        // Verify connection to child site
        $verify = $this->verify_connection($site_url, $data['site_key']);
        if (is_wp_error($verify)) {
            return $verify;
        }

        // Hash the site key for storage
        $site_key_hash = hash('sha256', $data['site_key']);

        $insert_data = [
            'user_id' => $user_id,
            'site_url' => $site_url,
            'site_name' => sanitize_text_field($data['site_name'] ?? $this->extract_site_name($site_url)),
            'site_key_hash' => $site_key_hash,
            'status' => 'active',
            'peanut_suite_active' => $verify['peanut_suite']['installed'] ?? false,
            'peanut_suite_version' => $verify['peanut_suite']['version'] ?? null,
            'permissions' => wp_json_encode($verify['permissions'] ?? []),
            'last_health' => wp_json_encode($verify['health'] ?? []),
            'last_check' => current_time('mysql'),
        ];

        $result = $wpdb->insert($table, $insert_data);

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to save site connection.', 'peanut-suite'));
        }

        $site_id = $wpdb->insert_id;

        // Store encrypted site key for future authenticated requests
        $this->store_site_key($site_id, $data['site_key']);

        do_action('peanut_monitor_site_connected', $site_id, $insert_data);

        return $site_id;
    }

    /**
     * Update site
     */
    public function update(int $id, array $data): bool|WP_Error {
        global $wpdb;
        $table = Monitor_Database::sites_table();
        $user_id = get_current_user_id();

        // Verify ownership
        $site = $this->get($id);
        if (!$site) {
            return new WP_Error('not_found', __('Site not found.', 'peanut-suite'));
        }

        $update_data = [];

        if (isset($data['site_name'])) {
            $update_data['site_name'] = sanitize_text_field($data['site_name']);
        }

        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }

        if (empty($update_data)) {
            return true;
        }

        $result = $wpdb->update(
            $table,
            $update_data,
            ['id' => $id, 'user_id' => $user_id]
        );

        return $result !== false;
    }

    /**
     * Disconnect (delete) a site
     */
    public function disconnect(int $id): bool|WP_Error {
        global $wpdb;
        $table = Monitor_Database::sites_table();
        $user_id = get_current_user_id();

        $site = $this->get($id);
        if (!$site) {
            return new WP_Error('not_found', __('Site not found.', 'peanut-suite'));
        }

        // Notify child site of disconnection (optional, may fail)
        $this->notify_disconnect($site);

        // Delete associated records
        $wpdb->delete(Monitor_Database::health_log_table(), ['site_id' => $id]);
        $wpdb->delete(Monitor_Database::uptime_table(), ['site_id' => $id]);
        $wpdb->delete(Monitor_Database::analytics_table(), ['site_id' => $id]);

        // Delete site
        $result = $wpdb->delete($table, ['id' => $id, 'user_id' => $user_id]);

        if ($result) {
            do_action('peanut_monitor_site_disconnected', $id, $site);
        }

        return $result !== false;
    }

    /**
     * Verify connection to child site
     */
    public function verify_connection(string $site_url, string $site_key): array|WP_Error {
        $endpoint = trailingslashit($site_url) . 'wp-json/peanut-connect/v1/verify';

        $response = wp_remote_get($endpoint, [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $site_key,
                'X-Peanut-Manager' => home_url(),
            ],
        ]);

        if (is_wp_error($response)) {
            return new WP_Error(
                'connection_failed',
                sprintf(__('Could not connect to site: %s', 'peanut-suite'), $response->get_error_message())
            );
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 401) {
            return new WP_Error('invalid_key', __('Invalid site key.', 'peanut-suite'));
        }

        if ($code !== 200) {
            return new WP_Error(
                'verification_failed',
                $body['message'] ?? __('Site verification failed.', 'peanut-suite')
            );
        }

        return $body;
    }

    /**
     * Make authenticated request to child site
     */
    public function remote_request(object $site, string $endpoint, string $method = 'GET', array $body = []): array|WP_Error {
        // We need to retrieve the actual site key - but we only store the hash
        // The site key should be stored encrypted, not just hashed
        // For now, we'll need to refactor this to store the key encrypted

        $url = trailingslashit($site->site_url) . 'wp-json/peanut-connect/v1/' . ltrim($endpoint, '/');

        $args = [
            'method' => $method,
            'timeout' => 30,
            'headers' => [
                'X-Peanut-Manager' => home_url(),
                'Content-Type' => 'application/json',
            ],
        ];

        // Site key needs to be retrieved from secure storage
        $site_key = $this->get_site_key($site->id);
        if ($site_key) {
            $args['headers']['Authorization'] = 'Bearer ' . $site_key;
        }

        if (!empty($body) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $this->mark_site_error($site->id, $response->get_error_message());
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 400) {
            return new WP_Error(
                'remote_error',
                $body['message'] ?? sprintf(__('Remote request failed with code %d', 'peanut-suite'), $code)
            );
        }

        return $body ?? [];
    }

    /**
     * Get decrypted site key
     */
    private function get_site_key(int $site_id): ?string {
        $encrypted = get_option("peanut_monitor_site_key_{$site_id}");
        if (!$encrypted) {
            return null;
        }

        // Use Peanut encryption service
        if (class_exists('Peanut_Encryption')) {
            $encryption = new Peanut_Encryption();
            return $encryption->decrypt($encrypted);
        }

        return null;
    }

    /**
     * Store encrypted site key
     */
    public function store_site_key(int $site_id, string $site_key): bool {
        if (class_exists('Peanut_Encryption')) {
            $encryption = new Peanut_Encryption();
            $encrypted = $encryption->encrypt($site_key);
            return update_option("peanut_monitor_site_key_{$site_id}", $encrypted);
        }

        return false;
    }

    /**
     * Delete stored site key
     */
    public function delete_site_key(int $site_id): bool {
        return delete_option("peanut_monitor_site_key_{$site_id}");
    }

    /**
     * Mark site as having an error
     */
    private function mark_site_error(int $id, string $error): void {
        global $wpdb;
        $table = Monitor_Database::sites_table();

        $wpdb->update(
            $table,
            [
                'status' => 'error',
                'last_health' => wp_json_encode(['error' => $error]),
            ],
            ['id' => $id]
        );
    }

    /**
     * Notify child site of disconnection
     */
    private function notify_disconnect(object $site): void {
        $this->remote_request($site, 'disconnect', 'POST');
    }

    /**
     * Normalize URL for storage
     */
    private function normalize_url(string $url): string {
        $url = strtolower(trim($url));
        $url = preg_replace('#^https?://#', '', $url);
        $url = rtrim($url, '/');
        return 'https://' . $url;
    }

    /**
     * Extract site name from URL
     */
    private function extract_site_name(string $url): string {
        $parsed = wp_parse_url($url);
        $host = $parsed['host'] ?? $url;
        $host = preg_replace('#^www\.#', '', $host);
        return ucfirst(explode('.', $host)[0]);
    }

    /**
     * Get site count for user's account
     */
    public function get_count(?int $user_id = null): int {
        global $wpdb;
        $table = Monitor_Database::sites_table();
        $current_user_id = $user_id ?? get_current_user_id();

        // Get all user IDs in the same account
        // Use get_or_create_for_user to ensure member entry exists
        $account_user_ids = [$current_user_id];
        if (class_exists('Peanut_Account_Service')) {
            $account = Peanut_Account_Service::get_or_create_for_user($current_user_id);
            if ($account) {
                $members = Peanut_Account_Service::get_members($account['id']);
                $account_user_ids = array_map(fn($m) => $m['user_id'], $members);
            }
        }

        $placeholders = implode(',', array_fill(0, count($account_user_ids), '%d'));
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id IN ($placeholders) AND status = 'active'",
            ...$account_user_ids
        ));
    }

    /**
     * Check if user can add more sites (license limit)
     */
    public function can_add_site(?int $user_id = null): bool {
        $current_count = $this->get_count($user_id);

        if (class_exists('Peanut_License')) {
            $license = new Peanut_License();
            $limit = $license->get_limit('monitor_sites');

            // -1 means unlimited
            if ($limit === -1) {
                return true;
            }

            return $current_count < $limit;
        }

        // Default limit if license system not available
        return $current_count < 25;
    }

    /**
     * Update last health data for site
     */
    public function update_health(int $id, array $health_data): void {
        global $wpdb;
        $table = Monitor_Database::sites_table();

        $wpdb->update(
            $table,
            [
                'last_health' => wp_json_encode($health_data),
                'last_check' => current_time('mysql'),
                'status' => $health_data['status'] === 'offline' ? 'error' : 'active',
            ],
            ['id' => $id]
        );
    }
}

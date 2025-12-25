<?php
/**
 * Peanut Connect REST Controller
 *
 * Exposes API endpoints for the Monitor module to connect to this site.
 * When Peanut Suite is installed on a site, this allows a central "manager"
 * site to monitor and manage this site remotely.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Connect_Controller extends WP_REST_Controller {

    /**
     * API namespace
     */
    protected $namespace = 'peanut-connect/v1';

    /**
     * Register routes
     */
    public function register_routes(): void {
        // Verify connection endpoint
        register_rest_route($this->namespace, '/verify', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'verify_connection'],
                'permission_callback' => [$this, 'check_site_key'],
            ],
        ]);

        // Get site health data
        register_rest_route($this->namespace, '/health', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_health'],
                'permission_callback' => [$this, 'check_site_key'],
            ],
        ]);

        // Get Peanut Suite stats
        register_rest_route($this->namespace, '/stats', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_stats'],
                'permission_callback' => [$this, 'check_site_key'],
            ],
        ]);

        // Disconnect notification
        register_rest_route($this->namespace, '/disconnect', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'handle_disconnect'],
                'permission_callback' => [$this, 'check_site_key'],
            ],
        ]);

        // Generate/get site key (admin only, no auth needed - used for initial setup)
        register_rest_route($this->namespace, '/generate-key', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'generate_site_key'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);
    }

    /**
     * Check site key authentication
     */
    public function check_site_key(WP_REST_Request $request): bool {
        $auth_header = $request->get_header('Authorization');

        if (empty($auth_header)) {
            return false;
        }

        // Extract Bearer token
        if (preg_match('/Bearer\s+(.+)$/i', $auth_header, $matches)) {
            $provided_key = $matches[1];
        } else {
            return false;
        }

        // Get stored site key
        $stored_key = get_option('peanut_connect_site_key');

        if (empty($stored_key)) {
            return false;
        }

        // Compare keys (timing-safe comparison)
        return hash_equals($stored_key, $provided_key);
    }

    /**
     * Check admin permission
     */
    public function check_admin_permission(): bool {
        return current_user_can('manage_options');
    }

    /**
     * Verify connection - returns site info
     */
    public function verify_connection(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $response = [
            'success' => true,
            'site_url' => home_url(),
            'site_name' => get_bloginfo('name'),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'peanut_suite' => [
                'installed' => defined('PEANUT_VERSION'),
                'version' => defined('PEANUT_VERSION') ? PEANUT_VERSION : null,
            ],
            'permissions' => $this->get_permissions(),
            'health' => $this->get_basic_health(),
            'timestamp' => current_time('mysql'),
        ];

        return new WP_REST_Response($response, 200);
    }

    /**
     * Get detailed health data
     */
    public function get_health(WP_REST_Request $request): WP_REST_Response {
        $health = [
            'status' => 'healthy',
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->get_mysql_version(),
            'ssl_enabled' => is_ssl(),
            'debug_mode' => WP_DEBUG,
            'memory_limit' => WP_MEMORY_LIMIT,
            'max_upload_size' => size_format(wp_max_upload_size()),
            'updates' => $this->get_update_counts(),
            'plugins' => $this->get_plugin_info(),
            'theme' => $this->get_theme_info(),
            'disk_space' => $this->get_disk_space(),
            'timestamp' => current_time('mysql'),
        ];

        // Determine overall status
        $updates = $health['updates'];
        if ($updates['core'] > 0 || $updates['plugins'] > 5) {
            $health['status'] = 'warning';
        }
        if ($updates['plugins'] > 10 || !$health['ssl_enabled']) {
            $health['status'] = 'critical';
        }

        return new WP_REST_Response($health, 200);
    }

    /**
     * Get Peanut Suite stats
     */
    public function get_stats(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $stats = [
            'contacts' => 0,
            'utm_clicks' => 0,
            'link_clicks' => 0,
            'visitors' => 0,
            'conversions' => 0,
        ];

        // Get contact count
        $contacts_table = $wpdb->prefix . 'peanut_contacts';
        if ($wpdb->get_var("SHOW TABLES LIKE '$contacts_table'") === $contacts_table) {
            $stats['contacts'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $contacts_table");
        }

        // Get UTM clicks
        $utm_table = $wpdb->prefix . 'peanut_utms';
        if ($wpdb->get_var("SHOW TABLES LIKE '$utm_table'") === $utm_table) {
            $stats['utm_clicks'] = (int) $wpdb->get_var("SELECT SUM(clicks) FROM $utm_table");
        }

        // Get link clicks
        $links_table = $wpdb->prefix . 'peanut_links';
        if ($wpdb->get_var("SHOW TABLES LIKE '$links_table'") === $links_table) {
            $stats['link_clicks'] = (int) $wpdb->get_var("SELECT SUM(clicks) FROM $links_table");
        }

        // Get visitor count
        $visitors_table = $wpdb->prefix . 'peanut_visitors';
        if ($wpdb->get_var("SHOW TABLES LIKE '$visitors_table'") === $visitors_table) {
            $stats['visitors'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $visitors_table");
        }

        return new WP_REST_Response($stats, 200);
    }

    /**
     * Handle disconnect notification
     */
    public function handle_disconnect(WP_REST_Request $request): WP_REST_Response {
        $manager_url = $request->get_header('X-Peanut-Manager');

        // Log the disconnection
        peanut_log_error(
            sprintf('Manager site disconnected: %s', $manager_url ?? 'Unknown'),
            'info',
            'peanut-connect'
        );

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * Generate a new site key
     */
    public function generate_site_key(WP_REST_Request $request): WP_REST_Response {
        // Generate a secure random key
        $key = 'psk_' . bin2hex(random_bytes(32));

        // Store it
        update_option('peanut_connect_site_key', $key);

        return new WP_REST_Response([
            'success' => true,
            'site_key' => $key,
            'message' => __('Site key generated successfully. Copy this key to use in your manager site.', 'peanut-suite'),
        ], 200);
    }

    /**
     * Get basic health info
     */
    private function get_basic_health(): array {
        $updates = $this->get_update_counts();

        $status = 'healthy';
        if ($updates['total'] > 5) {
            $status = 'warning';
        }
        if ($updates['core'] > 0 || $updates['total'] > 10) {
            $status = 'critical';
        }

        return [
            'status' => $status,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'updates_available' => $updates['total'],
            'uptime' => 100, // Placeholder - would need actual uptime monitoring
        ];
    }

    /**
     * Get permissions this site grants
     */
    private function get_permissions(): array {
        return [
            'view_health' => true,
            'view_stats' => true,
            'trigger_updates' => false, // Could be enabled in settings
            'manage_plugins' => false,
        ];
    }

    /**
     * Get update counts
     */
    private function get_update_counts(): array {
        $updates = [
            'core' => 0,
            'plugins' => 0,
            'themes' => 0,
            'total' => 0,
        ];

        // Check for updates
        $update_data = wp_get_update_data();

        if (!empty($update_data)) {
            $updates['core'] = !empty(get_core_updates()) && get_core_updates()[0]->response === 'upgrade' ? 1 : 0;
            $updates['plugins'] = $update_data['counts']['plugins'] ?? 0;
            $updates['themes'] = $update_data['counts']['themes'] ?? 0;
            $updates['total'] = $update_data['counts']['total'] ?? 0;
        }

        return $updates;
    }

    /**
     * Get MySQL version
     */
    private function get_mysql_version(): string {
        global $wpdb;
        return $wpdb->db_version();
    }

    /**
     * Get plugin info
     */
    private function get_plugin_info(): array {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);

        return [
            'total' => count($all_plugins),
            'active' => count($active_plugins),
            'inactive' => count($all_plugins) - count($active_plugins),
        ];
    }

    /**
     * Get theme info
     */
    private function get_theme_info(): array {
        $theme = wp_get_theme();

        return [
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'parent' => $theme->parent() ? $theme->parent()->get('Name') : null,
        ];
    }

    /**
     * Get disk space info
     */
    private function get_disk_space(): array {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];

        return [
            'uploads_path' => $base_dir,
            'free_space' => function_exists('disk_free_space') ? size_format(disk_free_space($base_dir)) : 'N/A',
        ];
    }
}

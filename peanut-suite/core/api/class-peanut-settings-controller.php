<?php
/**
 * Settings REST Controller
 *
 * Handles license, modules, and general settings.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Settings_Controller extends Peanut_REST_Controller {

    protected string $rest_base = 'settings';

    public function register_routes(): void {
        // Get settings
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_settings'],
            'permission_callback' => [$this, 'permission_callback'],
        ]);

        // Update settings
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_settings'],
            'permission_callback' => [$this, 'admin_permission_callback'],
        ]);

        // License endpoints
        register_rest_route($this->namespace, '/license', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_license'],
            'permission_callback' => [$this, 'permission_callback'],
        ]);

        // License activation (GET+POST for WAF compatibility)
        register_rest_route($this->namespace, '/license/activate', [
            'methods' => WP_REST_Server::READABLE . ', ' . WP_REST_Server::CREATABLE,
            'callback' => [$this, 'activate_license'],
            'permission_callback' => [$this, 'admin_permission_callback'],
            'args' => [
                'license_key' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);

        register_rest_route($this->namespace, '/license/deactivate', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'deactivate_license'],
            'permission_callback' => [$this, 'admin_permission_callback'],
        ]);

        // Modules endpoints
        register_rest_route($this->namespace, '/modules', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_modules'],
            'permission_callback' => [$this, 'permission_callback'],
        ]);

        // Module activation (GET+POST for WAF compatibility)
        register_rest_route($this->namespace, '/modules/(?P<id>[a-z_]+)/activate', [
            'methods' => WP_REST_Server::READABLE . ', ' . WP_REST_Server::CREATABLE,
            'callback' => [$this, 'activate_module'],
            'permission_callback' => [$this, 'admin_permission_callback'],
        ]);

        // Module deactivation (GET+POST for WAF compatibility)
        register_rest_route($this->namespace, '/modules/(?P<id>[a-z_]+)/deactivate', [
            'methods' => WP_REST_Server::READABLE . ', ' . WP_REST_Server::CREATABLE,
            'callback' => [$this, 'deactivate_module'],
            'permission_callback' => [$this, 'admin_permission_callback'],
        ]);

        // Test integration connection
        register_rest_route($this->namespace, '/integrations/(?P<id>[a-z0-9_]+)/test', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'test_integration'],
            'permission_callback' => [$this, 'admin_permission_callback'],
        ]);

        // Export all settings
        register_rest_route($this->namespace, '/' . $this->rest_base . '/export', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'export_settings'],
            'permission_callback' => [$this, 'admin_permission_callback'],
        ]);

        // Import settings
        register_rest_route($this->namespace, '/' . $this->rest_base . '/import', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'import_settings'],
            'permission_callback' => [$this, 'admin_permission_callback'],
        ]);

        // Clear cache
        register_rest_route($this->namespace, '/' . $this->rest_base . '/clear-cache', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'clear_cache'],
            'permission_callback' => [$this, 'admin_permission_callback'],
        ]);

        // Delete all data
        register_rest_route($this->namespace, '/' . $this->rest_base . '/delete-all', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_all_data'],
            'permission_callback' => [$this, 'admin_permission_callback'],
        ]);
    }

    /**
     * Get all settings
     */
    public function get_settings(WP_REST_Request $request): WP_REST_Response {
        return $this->success([
            'general' => get_option('peanut_settings', []),
            'modules' => peanut_get_active_modules(),
        ]);
    }

    /**
     * Update settings
     */
    public function update_settings(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $settings = $request->get_param('settings');

        if (!is_array($settings)) {
            return $this->error(__('Invalid settings format', 'peanut-suite'));
        }

        $current = get_option('peanut_settings', []);
        $updated = wp_parse_args(
            Peanut_Security::sanitize_fields($settings),
            $current
        );

        update_option('peanut_settings', $updated);

        return $this->success([
            'message' => __('Settings saved', 'peanut-suite'),
            'settings' => $updated,
        ]);
    }

    /**
     * Get license status
     */
    public function get_license(WP_REST_Request $request): WP_REST_Response {
        $license = new Peanut_License();
        $data = $license->get_license_data();

        return $this->success([
            'status' => $data['status'],
            'tier' => $data['tier'],
            'expires_at' => $data['expires_at'] ?? null,
            'features' => $data['features'] ?? [],
            'is_pro' => peanut_is_pro(),
        ]);
    }

    /**
     * Activate license
     */
    public function activate_license(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $key = sanitize_text_field($request->get_param('license_key'));

        if (empty($key)) {
            return $this->error(__('License key is required', 'peanut-suite'));
        }

        $license = new Peanut_License();
        $result = $license->activate($key);

        if ($result['status'] !== 'active') {
            return $this->error(
                $result['message'] ?? __('License activation failed', 'peanut-suite'),
                'license_invalid'
            );
        }

        return $this->success([
            'message' => __('License activated successfully', 'peanut-suite'),
            'license' => $result,
        ]);
    }

    /**
     * Deactivate license
     */
    public function deactivate_license(WP_REST_Request $request): WP_REST_Response {
        $license = new Peanut_License();
        $license->deactivate();

        return $this->success([
            'message' => __('License deactivated', 'peanut-suite'),
        ]);
    }

    /**
     * Get modules
     */
    public function get_modules(WP_REST_Request $request): WP_REST_Response {
        global $peanut_module_manager;

        // Get module manager from global or create display data
        $active = peanut_get_active_modules();

        $modules = [
            [
                'id' => 'utm',
                'name' => __('Campaigns', 'peanut-suite'),
                'description' => __('Create and manage UTM tracking codes', 'peanut-suite'),
                'icon' => 'target',
                'active' => $active['utm'] ?? true,
                'pro' => false,
            ],
            [
                'id' => 'links',
                'name' => __('Links', 'peanut-suite'),
                'description' => __('Shorten URLs and generate QR codes', 'peanut-suite'),
                'icon' => 'link',
                'active' => $active['links'] ?? true,
                'pro' => false,
            ],
            [
                'id' => 'contacts',
                'name' => __('Contacts', 'peanut-suite'),
                'description' => __('Manage leads and contacts', 'peanut-suite'),
                'icon' => 'users',
                'active' => $active['contacts'] ?? true,
                'pro' => false,
            ],
            [
                'id' => 'dashboard',
                'name' => __('Dashboard', 'peanut-suite'),
                'description' => __('Unified analytics dashboard', 'peanut-suite'),
                'icon' => 'layout-dashboard',
                'active' => $active['dashboard'] ?? true,
                'pro' => false,
            ],
            [
                'id' => 'popups',
                'name' => __('Popups', 'peanut-suite'),
                'description' => __('Create lead capture popups', 'peanut-suite'),
                'icon' => 'message-square',
                'active' => $active['popups'] ?? false,
                'pro' => true,
                'locked' => !peanut_is_pro(),
            ],
        ];

        return $this->success(['modules' => $modules]);
    }

    /**
     * Activate module
     */
    public function activate_module(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $id = $request->get_param('id');
        $active = peanut_get_active_modules();

        // Check if pro module
        $pro_modules = ['popups'];
        if (in_array($id, $pro_modules, true) && !peanut_is_pro()) {
            return $this->error(
                __('This module requires a Pro license', 'peanut-suite'),
                'pro_required',
                403
            );
        }

        $active[$id] = true;
        update_option('peanut_active_modules', $active);

        return $this->success([
            'message' => __('Module activated', 'peanut-suite'),
            'modules' => $active,
        ]);
    }

    /**
     * Deactivate module
     */
    public function deactivate_module(WP_REST_Request $request): WP_REST_Response {
        $id = $request->get_param('id');
        $active = peanut_get_active_modules();

        $active[$id] = false;
        update_option('peanut_active_modules', $active);

        return $this->success([
            'message' => __('Module deactivated', 'peanut-suite'),
            'modules' => $active,
        ]);
    }

    /**
     * Test integration connection
     */
    public function test_integration(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $id = $request->get_param('id');

        // Load integration classes
        $integrations_dir = PEANUT_PLUGIN_DIR . 'core/services/integrations/';

        switch ($id) {
            case 'ga4':
                require_once $integrations_dir . 'class-integration-ga4.php';
                $integration = new Peanut_Integration_GA4();
                break;

            case 'gtm':
                require_once $integrations_dir . 'class-integration-gtm.php';
                $integration = new Peanut_Integration_GTM();
                break;

            case 'mailchimp':
                require_once $integrations_dir . 'class-integration-mailchimp.php';
                $integration = new Peanut_Integration_Mailchimp();
                break;

            case 'convertkit':
                require_once $integrations_dir . 'class-integration-convertkit.php';
                $integration = new Peanut_Integration_ConvertKit();
                break;

            case 'stripe':
                require_once PEANUT_PLUGIN_DIR . 'modules/invoicing/class-invoicing-stripe.php';
                $integration = new Invoicing_Stripe();
                break;

            default:
                return $this->error(
                    __('Unknown integration', 'peanut-suite'),
                    'unknown_integration',
                    404
                );
        }

        $result = $integration->test_connection();

        if ($result['success']) {
            return $this->success([
                'message' => $result['message'],
            ]);
        } else {
            return $this->error($result['message'], 'connection_failed');
        }
    }

    /**
     * Export all settings and data
     */
    public function export_settings(WP_REST_Request $request): WP_REST_Response {
        $export = [
            'version' => PEANUT_VERSION,
            'exported_at' => current_time('mysql'),
            'settings' => [
                'general' => get_option('peanut_settings', []),
                'modules' => peanut_get_active_modules(),
                'integrations' => get_option('peanut_integrations', []),
            ],
        ];

        // Include license info (without key)
        $license = new Peanut_License();
        $license_data = $license->get_license_data();
        $export['license'] = [
            'tier' => $license_data['tier'],
            'status' => $license_data['status'],
        ];

        return $this->success([
            'filename' => 'peanut-suite-export-' . date('Y-m-d') . '.json',
            'data' => $export,
        ]);
    }

    /**
     * Import settings from export
     */
    public function import_settings(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $data = $request->get_param('data');

        if (empty($data) || !is_array($data)) {
            return $this->error(__('Invalid import data', 'peanut-suite'));
        }

        // Validate version
        if (empty($data['version'])) {
            return $this->error(__('Import file is missing version info', 'peanut-suite'));
        }

        $imported = [];

        // Import general settings
        if (!empty($data['settings']['general']) && is_array($data['settings']['general'])) {
            $sanitized = Peanut_Security::sanitize_fields($data['settings']['general']);
            update_option('peanut_settings', $sanitized);
            $imported[] = 'general';
        }

        // Import module settings
        if (!empty($data['settings']['modules']) && is_array($data['settings']['modules'])) {
            update_option('peanut_active_modules', array_map('boolval', $data['settings']['modules']));
            $imported[] = 'modules';
        }

        // Import integration settings
        if (!empty($data['settings']['integrations']) && is_array($data['settings']['integrations'])) {
            $sanitized = Peanut_Security::sanitize_fields($data['settings']['integrations']);
            update_option('peanut_integrations', $sanitized);
            $imported[] = 'integrations';
        }

        return $this->success([
            'message' => sprintf(__('Imported: %s', 'peanut-suite'), implode(', ', $imported)),
            'imported' => $imported,
        ]);
    }

    /**
     * Clear all cached data
     */
    public function clear_cache(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        // Clear transients with our prefix
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_peanut_%'
             OR option_name LIKE '_transient_timeout_peanut_%'"
        );

        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        // Clear any dashboard stats cache
        delete_transient('peanut_dashboard_stats');

        return $this->success([
            'message' => __('Cache cleared successfully', 'peanut-suite'),
        ]);
    }

    /**
     * Delete all plugin data
     */
    public function delete_all_data(WP_REST_Request $request): WP_REST_Response|WP_Error {
        // Require explicit confirmation
        $confirm = $request->get_param('confirm');
        if ($confirm !== 'DELETE_ALL_DATA') {
            return $this->error(
                __('You must confirm this action by passing confirm=DELETE_ALL_DATA', 'peanut-suite'),
                'confirmation_required'
            );
        }

        global $wpdb;

        // Delete all custom tables
        $tables = [
            Peanut_Database::utms_table(),
            Peanut_Database::links_table(),
            Peanut_Database::link_clicks_table(),
            Peanut_Database::contacts_table(),
            Peanut_Database::contact_activities_table(),
            Peanut_Database::popups_table(),
            Peanut_Database::popup_views_table(),
        ];

        // Add conditional tables if methods exist
        if (method_exists('Peanut_Database', 'invoices_table')) {
            $tables[] = Peanut_Database::invoices_table();
        }
        if (method_exists('Peanut_Database', 'accounts_table')) {
            $tables[] = Peanut_Database::accounts_table();
            $tables[] = Peanut_Database::account_members_table();
            $tables[] = Peanut_Database::api_keys_table();
            $tables[] = Peanut_Database::audit_log_table();
        }

        foreach ($tables as $table) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from Peanut_Database class
            $wpdb->query("TRUNCATE TABLE " . esc_sql($table));
        }

        // Delete options
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE 'peanut_%'"
        );

        // Clear transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_peanut_%'
             OR option_name LIKE '_transient_timeout_peanut_%'"
        );

        return $this->success([
            'message' => __('All plugin data has been deleted', 'peanut-suite'),
        ]);
    }
}

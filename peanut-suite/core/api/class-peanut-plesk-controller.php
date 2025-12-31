<?php
/**
 * Plesk Server Monitoring REST Controller
 *
 * Handles REST API endpoints for Plesk server monitoring.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Plesk_Controller extends Peanut_REST_Controller {

    protected string $rest_base = 'monitor/servers';

    /**
     * Register routes
     */
    public function register_routes(): void {
        // List servers
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_servers'],
                'permission_callback' => [$this, 'agency_permission_callback'],
                'args'                => $this->get_collection_params(),
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'add_server'],
                'permission_callback' => [$this, 'agency_permission_callback'],
                'args'                => [
                    'server_name' => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'server_host' => [
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'server_port' => [
                        'type'              => 'integer',
                        'default'           => 8443,
                        'sanitize_callback' => 'absint',
                    ],
                    'api_key' => [
                        'type'     => 'string',
                        'required' => true,
                    ],
                ],
            ],
        ]);

        // Overview / stats
        register_rest_route($this->namespace, '/' . $this->rest_base . '/overview', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_overview'],
                'permission_callback' => [$this, 'agency_permission_callback'],
            ],
        ]);

        // Single server
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_server'],
                'permission_callback' => [$this, 'agency_permission_callback'],
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_server'],
                'permission_callback' => [$this, 'agency_permission_callback'],
                'args'                => [
                    'server_name' => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'server_port' => [
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'delete_server'],
                'permission_callback' => [$this, 'agency_permission_callback'],
            ],
        ]);

        // Force health check
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/check', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'check_server_health'],
                'permission_callback' => [$this, 'agency_permission_callback'],
            ],
        ]);

        // Health history
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/health', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_health_history'],
                'permission_callback' => [$this, 'agency_permission_callback'],
                'args'                => [
                    'days' => [
                        'type'              => 'integer',
                        'default'           => 30,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ]);

        // Domains
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/domains', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_domains'],
                'permission_callback' => [$this, 'agency_permission_callback'],
            ],
        ]);

        // Services
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/services', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_services'],
                'permission_callback' => [$this, 'agency_permission_callback'],
            ],
        ]);
    }

    /**
     * Agency tier permission check
     */
    public function agency_permission_callback(WP_REST_Request $request): bool {
        if (!$this->permission_callback($request)) {
            return false;
        }

        // Require Agency tier
        return peanut_is_agency();
    }

    /**
     * Get collection parameters
     */
    protected function get_collection_params(): array {
        return [
            'page' => [
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
                'minimum'           => 1,
            ],
            'per_page' => [
                'type'              => 'integer',
                'default'           => 20,
                'sanitize_callback' => 'absint',
                'minimum'           => 1,
                'maximum'           => 100,
            ],
            'search' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'status' => [
                'type'              => 'string',
                'enum'              => ['active', 'disconnected', 'error'],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'orderby' => [
                'type'              => 'string',
                'default'           => 'server_name',
                'enum'              => ['server_name', 'server_host', 'status', 'last_check', 'created_at'],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'order' => [
                'type'              => 'string',
                'default'           => 'ASC',
                'enum'              => ['ASC', 'DESC'],
                'sanitize_callback' => 'strtoupper',
            ],
        ];
    }

    // =========================================
    // Endpoints
    // =========================================

    /**
     * Get all servers
     */
    public function get_servers(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();

        $result = Monitor_Plesk::get_servers($user_id, [
            'page'     => $request->get_param('page'),
            'per_page' => $request->get_param('per_page'),
            'search'   => $request->get_param('search'),
            'status'   => $request->get_param('status'),
            'orderby'  => $request->get_param('orderby'),
            'order'    => $request->get_param('order'),
        ]);

        return $this->success_response($result);
    }

    /**
     * Get servers overview/stats
     */
    public function get_overview(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $user_id = get_current_user_id();
        $table = Peanut_Database::monitor_servers_table();

        // Get counts by status
        $counts = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count
             FROM {$table}
             WHERE user_id = %d
             GROUP BY status",
            $user_id
        ), OBJECT_K);

        $total = 0;
        $active = 0;
        $error = 0;

        foreach ($counts as $status => $row) {
            $total += $row->count;
            if ($status === 'active') $active = $row->count;
            if ($status === 'error' || $status === 'disconnected') $error += $row->count;
        }

        // Get servers with warnings/critical health
        $servers_needing_attention = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$table}
             WHERE user_id = %d
               AND status = 'active'
               AND JSON_EXTRACT(last_health, '$.score') < 80",
            $user_id
        ));

        return $this->success_response([
            'total_servers'             => $total,
            'active_servers'            => $active,
            'servers_with_errors'       => $error,
            'servers_needing_attention' => (int) $servers_needing_attention,
        ]);
    }

    /**
     * Add a new server
     */
    public function add_server(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();

        $result = Monitor_Plesk::add_server($user_id, [
            'server_name' => $request->get_param('server_name'),
            'server_host' => $request->get_param('server_host'),
            'server_port' => $request->get_param('server_port'),
            'api_key'     => $request->get_param('api_key'),
        ]);

        if (is_wp_error($result)) {
            return $this->error_response($result->get_error_message(), $result->get_error_code());
        }

        return $this->success_response($result, 'Server added successfully');
    }

    /**
     * Get single server
     */
    public function get_server(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $server_id = $request->get_param('id');

        $server = Monitor_Plesk::get_server($server_id, $user_id);

        if (!$server) {
            return $this->error_response('Server not found', 'not_found', 404);
        }

        return $this->success_response($server);
    }

    /**
     * Update server
     */
    public function update_server(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $server_id = $request->get_param('id');

        $data = [];
        if ($request->has_param('server_name')) {
            $data['server_name'] = $request->get_param('server_name');
        }
        if ($request->has_param('server_port')) {
            $data['server_port'] = $request->get_param('server_port');
        }

        $result = Monitor_Plesk::update_server($server_id, $user_id, $data);

        if (!$result) {
            return $this->error_response('Failed to update server', 'update_failed');
        }

        $server = Monitor_Plesk::get_server($server_id, $user_id);

        return $this->success_response($server, 'Server updated successfully');
    }

    /**
     * Delete server
     */
    public function delete_server(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $server_id = $request->get_param('id');

        $result = Monitor_Plesk::remove_server($server_id, $user_id);

        if (!$result) {
            return $this->error_response('Failed to delete server', 'delete_failed');
        }

        return $this->success_response(null, 'Server removed successfully');
    }

    /**
     * Force health check
     */
    public function check_server_health(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $server_id = $request->get_param('id');

        // Verify ownership
        $server = Monitor_Plesk::get_server($server_id, $user_id);
        if (!$server) {
            return $this->error_response('Server not found', 'not_found', 404);
        }

        $result = Monitor_Plesk::check_health($server_id);

        if (is_wp_error($result)) {
            return $this->error_response($result->get_error_message(), $result->get_error_code());
        }

        // Get updated server data
        $server = Monitor_Plesk::get_server($server_id, $user_id);

        return $this->success_response([
            'server' => $server,
            'health' => $result,
        ], 'Health check completed');
    }

    /**
     * Get health history
     */
    public function get_health_history(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $server_id = $request->get_param('id');
        $days = $request->get_param('days');

        // Verify ownership
        $server = Monitor_Plesk::get_server($server_id, $user_id);
        if (!$server) {
            return $this->error_response('Server not found', 'not_found', 404);
        }

        $history = Monitor_Plesk::get_health_history($server_id, $days);

        return $this->success_response([
            'server_id' => $server_id,
            'days'      => $days,
            'history'   => $history,
        ]);
    }

    /**
     * Get domains for server
     */
    public function get_domains(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $server_id = $request->get_param('id');

        // Verify ownership
        $server = Monitor_Plesk::get_server($server_id, $user_id);
        if (!$server) {
            return $this->error_response('Server not found', 'not_found', 404);
        }

        $domains = Monitor_Plesk::get_domains($server_id);

        if (is_wp_error($domains)) {
            return $this->error_response($domains->get_error_message(), $domains->get_error_code());
        }

        return $this->success_response([
            'server_id' => $server_id,
            'domains'   => $domains,
        ]);
    }

    /**
     * Get services for server
     */
    public function get_services(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $server_id = $request->get_param('id');

        // Verify ownership
        $server = Monitor_Plesk::get_server($server_id, $user_id);
        if (!$server) {
            return $this->error_response('Server not found', 'not_found', 404);
        }

        $services = Monitor_Plesk::get_services($server_id);

        if (is_wp_error($services)) {
            return $this->error_response($services->get_error_message(), $services->get_error_code());
        }

        return $this->success_response([
            'server_id' => $server_id,
            'services'  => $services,
        ]);
    }
}

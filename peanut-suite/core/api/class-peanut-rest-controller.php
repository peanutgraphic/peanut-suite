<?php
/**
 * Base REST Controller
 *
 * Shared functionality for all API endpoints.
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class Peanut_REST_Controller {

    protected string $namespace = PEANUT_API_NAMESPACE;
    protected string $rest_base = '';

    /**
     * Register routes (implemented by child classes)
     */
    abstract public function register_routes(): void;

    /**
     * Standard permission callback
     */
    public function permission_callback(WP_REST_Request $request): bool|WP_Error {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                __('Authentication required.', 'peanut-suite'),
                ['status' => 401]
            );
        }

        if (!wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
            return new WP_Error(
                'rest_invalid_nonce',
                __('Invalid security token.', 'peanut-suite'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Admin permission callback
     */
    public function admin_permission_callback(WP_REST_Request $request): bool|WP_Error {
        $base = $this->permission_callback($request);
        if (is_wp_error($base)) {
            return $base;
        }

        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('Administrator access required.', 'peanut-suite'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * API key permission callback with scope validation
     *
     * Allows authentication via Bearer token (API key) as an alternative to nonce.
     * If API key is provided, validates it and checks for required scope.
     * Falls back to standard permission_callback if no API key.
     *
     * @param WP_REST_Request $request The request object
     * @param string|null $required_scope The scope required for this endpoint (e.g., 'links:read')
     * @return bool|WP_Error
     */
    public function api_key_permission_callback(WP_REST_Request $request, ?string $required_scope = null): bool|WP_Error {
        $auth_header = $request->get_header('Authorization');

        // Check for Bearer token (API key)
        if (!empty($auth_header) && preg_match('/Bearer\s+(.+)$/i', $auth_header, $matches)) {
            $api_key = $matches[1];

            // Validate the API key
            $key_data = Peanut_Api_Keys_Service::validate($api_key);

            if (!$key_data) {
                return new WP_Error(
                    'rest_invalid_api_key',
                    __('Invalid or expired API key.', 'peanut-suite'),
                    ['status' => 401]
                );
            }

            // Check scope if required
            if ($required_scope && !Peanut_Api_Keys_Service::has_scope($key_data, $required_scope)) {
                return new WP_Error(
                    'rest_insufficient_scope',
                    sprintf(
                        __('API key does not have required scope: %s', 'peanut-suite'),
                        $required_scope
                    ),
                    ['status' => 403]
                );
            }

            // Update last used timestamp
            Peanut_Api_Keys_Service::update_last_used(
                $key_data['id'],
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            // Store key data on request for use in callback
            $request->set_param('_api_key_data', $key_data);

            return true;
        }

        // Fall back to standard authentication
        return $this->permission_callback($request);
    }

    /**
     * Create a permission callback closure for a specific scope
     *
     * Usage in register_routes():
     * 'permission_callback' => $this->with_scope('links:read'),
     *
     * @param string $scope The required scope
     * @return callable
     */
    protected function with_scope(string $scope): callable {
        return fn(WP_REST_Request $request) => $this->api_key_permission_callback($request, $scope);
    }

    /**
     * Get API key data from request (if authenticated via API key)
     *
     * @param WP_REST_Request $request
     * @return array|null
     */
    protected function get_api_key_data(WP_REST_Request $request): ?array {
        return $request->get_param('_api_key_data');
    }

    /**
     * Get account ID from API key or current user
     *
     * @param WP_REST_Request $request
     * @return int|null
     */
    protected function get_account_id_from_request(WP_REST_Request $request): ?int {
        $key_data = $this->get_api_key_data($request);
        if ($key_data) {
            return $key_data['account_id'];
        }
        return null;
    }

    /**
     * Success response
     */
    protected function success(array $data = [], int $status = 200): WP_REST_Response {
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    /**
     * Paginated response
     */
    protected function paginated(array $items, int $total, int $page, int $per_page): WP_REST_Response {
        return new WP_REST_Response([
            'success' => true,
            'data' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => (int) ceil($total / $per_page),
            ],
        ], 200);
    }

    /**
     * Error response
     */
    protected function error(string $code, string $message = 'An error occurred', int $status = 400): WP_Error {
        return new WP_Error($code, $message, ['status' => $status]);
    }

    /**
     * Not found response
     */
    protected function not_found(string $message = 'Resource not found'): WP_Error {
        return $this->error('not_found', $message, 404);
    }

    /**
     * Get pagination params
     */
    protected function get_pagination(WP_REST_Request $request): array {
        return [
            'page' => max(1, (int) $request->get_param('page') ?: 1),
            'per_page' => min(100, max(1, (int) $request->get_param('per_page') ?: 20)),
        ];
    }

    /**
     * Get sort params
     */
    protected function get_sort(WP_REST_Request $request, array $allowed = []): array {
        $orderby = $request->get_param('sort_by') ?: 'created_at';
        $order = strtoupper($request->get_param('sort_order') ?: 'DESC');

        if (!empty($allowed) && !in_array($orderby, $allowed, true)) {
            $orderby = 'created_at';
        }

        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        return ['orderby' => $orderby, 'order' => $order];
    }

    /**
     * Check feature limit
     */
    protected function check_limit(string $feature, int $current_count): bool {
        $license = new Peanut_License();
        $limit = $license->get_limit($feature);

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        return $current_count < $limit;
    }
}

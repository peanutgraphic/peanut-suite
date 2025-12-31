<?php
/**
 * Auth REST Controller
 *
 * Handles authentication endpoints for team login.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Auth_Controller extends Peanut_REST_Controller {

    protected string $rest_base = 'auth';

    /**
     * Rate limit settings for login attempts
     */
    private const LOGIN_RATE_LIMIT = 5; // Max attempts per window
    private const LOGIN_RATE_WINDOW = 300; // 5 minutes in seconds

    /**
     * Register routes
     */
    public function register_routes(): void {
        // Login endpoint (public)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/login', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'login'],
                'permission_callback' => '__return_true', // Public endpoint
                'args' => [
                    'username' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'password' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'remember' => [
                        'required' => false,
                        'type' => 'boolean',
                        'default' => false,
                    ],
                ],
            ],
        ]);

        // Get current user info (authenticated)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/me', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_current_user'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Logout endpoint
        register_rest_route($this->namespace, '/' . $this->rest_base . '/logout', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'logout'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);
    }

    /**
     * Handle login request
     */
    public function login(WP_REST_Request $request): WP_REST_Response {
        $username = $request->get_param('username');
        $password = $request->get_param('password');
        $remember = $request->get_param('remember');

        // Check rate limit
        $ip = $this->get_client_ip();
        if ($this->is_rate_limited($ip)) {
            return new WP_REST_Response([
                'success' => false,
                'code' => 'rate_limited',
                'message' => __('Too many login attempts. Please try again later.', 'peanut-suite'),
            ], 429);
        }

        // Try to find user by email or username
        $user = null;
        if (is_email($username)) {
            $user = get_user_by('email', $username);
        }
        if (!$user) {
            $user = get_user_by('login', $username);
        }

        if (!$user) {
            $this->record_failed_attempt($ip);
            return new WP_REST_Response([
                'success' => false,
                'code' => 'invalid_credentials',
                'message' => __('Invalid username or password.', 'peanut-suite'),
            ], 401);
        }

        // Attempt to sign in
        $creds = [
            'user_login' => $user->user_login,
            'user_password' => $password,
            'remember' => $remember,
        ];

        $signed_in = wp_signon($creds, is_ssl());

        if (is_wp_error($signed_in)) {
            $this->record_failed_attempt($ip);
            return new WP_REST_Response([
                'success' => false,
                'code' => 'invalid_credentials',
                'message' => __('Invalid username or password.', 'peanut-suite'),
            ], 401);
        }

        // Set the current user
        wp_set_current_user($signed_in->ID);

        // Get user's account info
        $account = Peanut_Account_Service::get_user_account($signed_in->ID);
        $member = null;
        $permissions = null;

        if ($account) {
            $member = Peanut_Account_Service::get_member($account['id'], $signed_in->ID);
            if ($member) {
                $permissions = Peanut_Account_Service::get_member_permissions($account['id'], $signed_in->ID);
            }
        }

        // Log the login
        if ($account) {
            Peanut_Audit_Log_Service::log(
                $account['id'],
                'user_login',
                'auth',
                null,
                ['ip' => $ip],
                $signed_in->ID
            );
        }

        // Clear failed attempts on success
        $this->clear_failed_attempts($ip);

        return new WP_REST_Response([
            'success' => true,
            'redirect_url' => admin_url('admin.php?page=peanut-app'),
            'user' => [
                'id' => $signed_in->ID,
                'name' => $signed_in->display_name,
                'email' => $signed_in->user_email,
            ],
            'account' => $account ? [
                'id' => $account['id'],
                'name' => $account['name'],
                'role' => $member['role'] ?? 'viewer',
                'permissions' => $permissions,
            ] : null,
        ]);
    }

    /**
     * Get current user info with account context
     */
    public function get_current_user(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $user = wp_get_current_user();

        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'code' => 'not_logged_in',
                'message' => __('Not logged in.', 'peanut-suite'),
            ], 401);
        }

        // Get user's account info
        $account = Peanut_Account_Service::get_user_account($user_id);
        $member = null;
        $permissions = null;
        $available_features = [];

        if ($account) {
            $member = Peanut_Account_Service::get_member($account['id'], $user_id);
            if ($member) {
                $permissions = Peanut_Account_Service::get_member_permissions($account['id'], $user_id);
            }
            $available_features = Peanut_Account_Service::get_available_features($account['tier'] ?? 'free');
        }

        return new WP_REST_Response([
            'success' => true,
            'user' => [
                'id' => $user_id,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'avatar' => get_avatar_url($user_id, ['size' => 96]),
            ],
            'account' => $account ? [
                'id' => $account['id'],
                'name' => $account['name'],
                'slug' => $account['slug'],
                'tier' => $account['tier'],
                'role' => $member['role'] ?? 'viewer',
                'permissions' => $permissions,
                'available_features' => $available_features,
            ] : null,
        ]);
    }

    /**
     * Handle logout request
     */
    public function logout(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $account = Peanut_Account_Service::get_user_account($user_id);

        // Log the logout
        if ($account) {
            Peanut_Audit_Log_Service::log(
                $account['id'],
                'user_logout',
                'auth',
                null,
                ['ip' => $this->get_client_ip()],
                $user_id
            );
        }

        wp_logout();

        return new WP_REST_Response([
            'success' => true,
            'redirect_url' => home_url('/team-login'),
        ]);
    }

    /**
     * Check if IP is rate limited
     */
    private function is_rate_limited(string $ip): bool {
        $transient_key = 'peanut_login_attempts_' . md5($ip);
        $attempts = get_transient($transient_key);

        return $attempts && $attempts >= self::LOGIN_RATE_LIMIT;
    }

    /**
     * Record a failed login attempt
     */
    private function record_failed_attempt(string $ip): void {
        $transient_key = 'peanut_login_attempts_' . md5($ip);
        $attempts = get_transient($transient_key) ?: 0;
        set_transient($transient_key, $attempts + 1, self::LOGIN_RATE_WINDOW);
    }

    /**
     * Clear failed attempts for IP
     */
    private function clear_failed_attempts(string $ip): void {
        $transient_key = 'peanut_login_attempts_' . md5($ip);
        delete_transient($transient_key);
    }

    /**
     * Get client IP address
     */
    private function get_client_ip(): string {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }

        return $ip;
    }
}

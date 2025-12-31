<?php
/**
 * Accounts REST Controller
 *
 * Handles account, team members, API keys, and audit log endpoints.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Accounts_Controller extends Peanut_REST_Controller {

    protected string $rest_base = 'accounts';

    /**
     * Rate limit settings
     */
    private const INVITE_RATE_LIMIT = 10; // Max invites per window
    private const INVITE_RATE_WINDOW = 3600; // 1 hour in seconds

    /**
     * Register routes
     */
    public function register_routes(): void {
        // Account routes
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_accounts'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/current', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_current_account'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_account'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_account'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/stats', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_account_stats'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Members routes
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<account_id>\d+)/members', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_members'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'add_member'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<account_id>\d+)/members/(?P<user_id>\d+)', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_member'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'remove_member'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<account_id>\d+)/members/(?P<user_id>\d+)/reset-password', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'reset_member_password'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<account_id>\d+)/members/(?P<user_id>\d+)/set-password', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'set_member_password'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<account_id>\d+)/transfer-ownership', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'transfer_ownership'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // API Keys routes
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<account_id>\d+)/api-keys', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_api_keys'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_api_key'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<account_id>\d+)/api-keys/(?P<key_id>\d+)', [
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'revoke_api_key'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<account_id>\d+)/api-keys/(?P<key_id>\d+)/regenerate', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'regenerate_api_key'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/api-keys/scopes', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_api_key_scopes'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Audit Log routes
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<account_id>\d+)/audit-log', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_audit_logs'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<account_id>\d+)/audit-log/export', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'export_audit_logs'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/audit-log/filters', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_audit_log_filters'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Features routes
        register_rest_route($this->namespace, '/' . $this->rest_base . '/features', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_available_features'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Team Login Settings routes
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<account_id>\d+)/login-settings', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_login_settings'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_login_settings'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<account_id>\d+)/my-permissions', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_my_permissions'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);
    }

    // ===============================
    // Account Methods
    // ===============================

    /**
     * Get all accounts for current user
     */
    public function get_accounts(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = get_current_user_id();
        $accounts = Peanut_Account_Service::get_accounts_for_user($user_id);

        return $this->success($accounts);
    }

    /**
     * Get or create current account
     */
    public function get_current_account(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = get_current_user_id();
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);

        if (!$account) {
            return $this->error('account_error', 'Failed to get or create account', 500);
        }

        return $this->success($account);
    }

    /**
     * Get single account
     */
    public function get_account(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        if (!$this->user_can_access_account($account_id, $user_id)) {
            return $this->error('forbidden', 'Access denied', 403);
        }

        $account = Peanut_Account_Service::get_by_id($account_id);
        if (!$account) {
            return $this->not_found('Account not found');
        }

        return $this->success($account);
    }

    /**
     * Update account
     */
    public function update_account(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        if (!$this->user_has_account_role($account_id, $user_id, 'admin')) {
            $this->log_access_denied($account_id, 'update_account', Peanut_Audit_Log_Service::RESOURCE_ACCOUNT, $account_id);
            return $this->error('forbidden', 'Admin access required', 403);
        }

        $data = [
            'name' => $request->get_param('name'),
            'settings' => $request->get_param('settings'),
        ];

        $result = Peanut_Account_Service::update($account_id, $data);

        if ($result) {
            Peanut_Audit_Log_Service::log(
                $account_id,
                Peanut_Audit_Log_Service::ACTION_UPDATE,
                Peanut_Audit_Log_Service::RESOURCE_ACCOUNT,
                $account_id,
                ['fields' => array_keys(array_filter($data))]
            );
        }

        $account = Peanut_Account_Service::get_by_id($account_id);
        return $this->success($account);
    }

    /**
     * Get account stats
     */
    public function get_account_stats(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        if (!$this->user_can_access_account($account_id, $user_id)) {
            return $this->error('forbidden', 'Access denied', 403);
        }

        $stats = Peanut_Account_Service::get_stats($account_id);
        return $this->success($stats);
    }

    // ===============================
    // Members Methods
    // ===============================

    /**
     * Get account members
     */
    public function get_members(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $user_id = get_current_user_id();

        if (!$this->user_can_access_account($account_id, $user_id)) {
            return $this->error('forbidden', 'Access denied', 403);
        }

        $members = Peanut_Account_Service::get_members($account_id);
        return $this->success($members);
    }

    /**
     * Add member to account
     */
    public function add_member(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $current_user_id = get_current_user_id();

        if (!$this->user_has_account_role($account_id, $current_user_id, 'admin')) {
            // Log failed permission check
            Peanut_Audit_Log_Service::log(
                $account_id,
                Peanut_Audit_Log_Service::ACTION_ACCESS_DENIED,
                Peanut_Audit_Log_Service::RESOURCE_MEMBER,
                null,
                ['action' => 'add_member', 'reason' => 'insufficient_role']
            );
            return $this->error('forbidden', 'Admin access required', 403);
        }

        // Rate limiting check
        if (!$this->check_invite_rate_limit($account_id, $current_user_id)) {
            Peanut_Audit_Log_Service::log(
                $account_id,
                Peanut_Audit_Log_Service::ACTION_RATE_LIMITED,
                Peanut_Audit_Log_Service::RESOURCE_MEMBER,
                null,
                ['action' => 'add_member', 'limit' => self::INVITE_RATE_LIMIT]
            );
            return $this->error('rate_limited', 'Too many invitation attempts. Please try again later.', 429);
        }

        // Increment rate limit counter on every attempt (prevents email enumeration)
        $this->increment_invite_counter($account_id, $current_user_id);

        $email = sanitize_email($request->get_param('email'));
        $role = sanitize_key($request->get_param('role'));
        $permissions = $request->get_param('permissions');

        if (!is_email($email)) {
            return $this->error('invalid_email', 'Invalid email address');
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            // Generic error message to prevent user enumeration
            return $this->error('add_failed', 'Unable to add member. Please verify the email address and try again.', 400);
        }

        // Validate permissions if provided
        $feature_permissions = null;
        if (!empty($permissions) && is_array($permissions)) {
            $feature_permissions = $this->sanitize_permissions($permissions);
        }

        $result = Peanut_Account_Service::add_member(
            $account_id,
            $user->ID,
            $role,
            $current_user_id,
            $feature_permissions
        );

        if (!$result) {
            return $this->error('add_failed', 'Unable to add member. Please verify the email address and try again.');
        }

        Peanut_Audit_Log_Service::log(
            $account_id,
            Peanut_Audit_Log_Service::ACTION_INVITE,
            Peanut_Audit_Log_Service::RESOURCE_MEMBER,
            $user->ID,
            ['email' => $email, 'role' => $role, 'permissions' => $feature_permissions]
        );

        $members = Peanut_Account_Service::get_members($account_id);
        return $this->success($members, 201);
    }

    /**
     * Update member role and permissions
     */
    public function update_member(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $target_user_id = (int) $request->get_param('user_id');
        $current_user_id = get_current_user_id();

        if (!$this->user_has_account_role($account_id, $current_user_id, 'admin')) {
            $this->log_access_denied($account_id, 'update_member', Peanut_Audit_Log_Service::RESOURCE_MEMBER, $target_user_id);
            return $this->error('forbidden', 'Admin access required', 403);
        }

        $new_role = sanitize_key($request->get_param('role'));
        $permissions = $request->get_param('permissions');

        // Update role if provided
        if ($new_role) {
            $result = Peanut_Account_Service::update_member_role($account_id, $target_user_id, $new_role);
            if (!$result) {
                return $this->error('update_failed', 'Failed to update member role');
            }
        }

        // Update permissions if provided
        $changes = ['role' => $new_role];
        if (!empty($permissions) && is_array($permissions)) {
            $feature_permissions = $this->sanitize_permissions($permissions);
            Peanut_Account_Service::update_member_permissions($account_id, $target_user_id, $feature_permissions);
            $changes['permissions'] = $feature_permissions;
        }

        Peanut_Audit_Log_Service::log(
            $account_id,
            Peanut_Audit_Log_Service::ACTION_UPDATE,
            Peanut_Audit_Log_Service::RESOURCE_MEMBER,
            $target_user_id,
            $changes
        );

        $members = Peanut_Account_Service::get_members($account_id);
        return $this->success($members);
    }

    /**
     * Remove member from account
     */
    public function remove_member(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $target_user_id = (int) $request->get_param('user_id');
        $current_user_id = get_current_user_id();

        // User can remove themselves or admins can remove others
        $can_remove = $target_user_id === $current_user_id ||
                      $this->user_has_account_role($account_id, $current_user_id, 'admin');

        if (!$can_remove) {
            $this->log_access_denied($account_id, 'remove_member', Peanut_Audit_Log_Service::RESOURCE_MEMBER, $target_user_id);
            return $this->error('forbidden', 'Permission denied', 403);
        }

        $result = Peanut_Account_Service::remove_member($account_id, $target_user_id);

        if (!$result) {
            return $this->error('remove_failed', 'Failed to remove member. Cannot remove account owner.');
        }

        Peanut_Audit_Log_Service::log(
            $account_id,
            Peanut_Audit_Log_Service::ACTION_DELETE,
            Peanut_Audit_Log_Service::RESOURCE_MEMBER,
            $target_user_id
        );

        $members = Peanut_Account_Service::get_members($account_id);
        return $this->success($members);
    }

    /**
     * Send password reset email to team member
     */
    public function reset_member_password(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $target_user_id = (int) $request->get_param('user_id');
        $current_user_id = get_current_user_id();

        // Only admins can reset passwords for other users
        if (!$this->user_has_account_role($account_id, $current_user_id, 'admin')) {
            $this->log_access_denied($account_id, 'reset_member_password', Peanut_Audit_Log_Service::RESOURCE_MEMBER, $target_user_id);
            return $this->error('forbidden', 'Admin access required', 403);
        }

        // Verify target user is a member of this account
        $member_role = Peanut_Account_Service::get_user_role($account_id, $target_user_id);
        if (!$member_role) {
            return $this->not_found('Member not found in this account');
        }

        // Cannot reset owner's password unless you are the owner
        if ($member_role === 'owner' && !$this->user_has_account_role($account_id, $current_user_id, 'owner')) {
            return $this->error('forbidden', 'Cannot reset owner password', 403);
        }

        // Get user data
        $user = get_user_by('ID', $target_user_id);
        if (!$user) {
            return $this->not_found('User not found');
        }

        // Use WordPress built-in password reset
        $result = retrieve_password($user->user_login);

        if (is_wp_error($result)) {
            return $this->error('reset_failed', $result->get_error_message(), 500);
        }

        // Log the action
        Peanut_Audit_Log_Service::log(
            $account_id,
            Peanut_Audit_Log_Service::ACTION_UPDATE,
            Peanut_Audit_Log_Service::RESOURCE_MEMBER,
            $target_user_id,
            ['action' => 'password_reset_sent', 'email' => $user->user_email]
        );

        return $this->success([
            'message' => sprintf('Password reset email sent to %s', $user->user_email),
        ]);
    }

    /**
     * Set password directly for team member
     */
    public function set_member_password(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $target_user_id = (int) $request->get_param('user_id');
        $new_password = $request->get_param('password');
        $current_user_id = get_current_user_id();

        // Validate password
        if (empty($new_password) || strlen($new_password) < 8) {
            return $this->error('invalid_password', 'Password must be at least 8 characters', 400);
        }

        // Only admins can set passwords for other users
        if (!$this->user_has_account_role($account_id, $current_user_id, 'admin')) {
            $this->log_access_denied($account_id, 'set_member_password', Peanut_Audit_Log_Service::RESOURCE_MEMBER, $target_user_id);
            return $this->error('forbidden', 'Admin access required', 403);
        }

        // Verify target user is a member of this account
        $member_role = Peanut_Account_Service::get_user_role($account_id, $target_user_id);
        if (!$member_role) {
            return $this->not_found('Member not found in this account');
        }

        // Cannot set owner's password unless you are the owner
        if ($member_role === 'owner' && !$this->user_has_account_role($account_id, $current_user_id, 'owner')) {
            return $this->error('forbidden', 'Cannot set owner password', 403);
        }

        // Get user data
        $user = get_user_by('ID', $target_user_id);
        if (!$user) {
            return $this->not_found('User not found');
        }

        // Set the new password
        wp_set_password($new_password, $target_user_id);

        // Log the action
        Peanut_Audit_Log_Service::log(
            $account_id,
            Peanut_Audit_Log_Service::ACTION_UPDATE,
            Peanut_Audit_Log_Service::RESOURCE_MEMBER,
            $target_user_id,
            ['action' => 'password_set', 'email' => $user->user_email]
        );

        return $this->success([
            'message' => sprintf('Password updated for %s', $user->user_email),
        ]);
    }

    /**
     * Transfer account ownership
     */
    public function transfer_ownership(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $new_owner_id = (int) $request->get_param('new_owner_id');
        $current_user_id = get_current_user_id();

        if (!$this->user_has_account_role($account_id, $current_user_id, 'owner')) {
            $this->log_access_denied($account_id, 'transfer_ownership', Peanut_Audit_Log_Service::RESOURCE_ACCOUNT, $account_id);
            return $this->error('forbidden', 'Only the owner can transfer ownership', 403);
        }

        $result = Peanut_Account_Service::transfer_ownership($account_id, $current_user_id, $new_owner_id);

        if (!$result) {
            return $this->error('transfer_failed', 'Failed to transfer ownership');
        }

        Peanut_Audit_Log_Service::log(
            $account_id,
            Peanut_Audit_Log_Service::ACTION_UPDATE,
            Peanut_Audit_Log_Service::RESOURCE_ACCOUNT,
            $account_id,
            ['action' => 'ownership_transfer', 'new_owner_id' => $new_owner_id]
        );

        $account = Peanut_Account_Service::get_by_id($account_id);
        return $this->success($account);
    }

    // ===============================
    // API Keys Methods
    // ===============================

    /**
     * Get API keys for account
     */
    public function get_api_keys(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $user_id = get_current_user_id();

        if (!$this->user_has_account_role($account_id, $user_id, 'admin')) {
            $this->log_access_denied($account_id, 'get_api_keys', Peanut_Audit_Log_Service::RESOURCE_API_KEY);
            return $this->error('forbidden', 'Admin access required', 403);
        }

        $include_revoked = (bool) $request->get_param('include_revoked');
        $keys = Peanut_Api_Keys_Service::get_by_account($account_id, $include_revoked);

        return $this->success($keys);
    }

    /**
     * Create new API key
     */
    public function create_api_key(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $user_id = get_current_user_id();

        if (!$this->user_has_account_role($account_id, $user_id, 'admin')) {
            $this->log_access_denied($account_id, 'create_api_key', Peanut_Audit_Log_Service::RESOURCE_API_KEY);
            return $this->error('forbidden', 'Admin access required', 403);
        }

        $name = sanitize_text_field($request->get_param('name'));
        $scopes = $request->get_param('scopes') ?: [];
        $expires_at = $request->get_param('expires_at');

        if (empty($name)) {
            return $this->error('missing_name', 'Name is required');
        }

        if (empty($scopes)) {
            return $this->error('missing_scopes', 'At least one scope is required');
        }

        $key = Peanut_Api_Keys_Service::create($account_id, $user_id, $name, $scopes, $expires_at);

        if (!$key) {
            return $this->error('create_failed', 'Failed to create API key');
        }

        Peanut_Audit_Log_Service::log(
            $account_id,
            Peanut_Audit_Log_Service::ACTION_CREATE,
            Peanut_Audit_Log_Service::RESOURCE_API_KEY,
            $key['id'],
            ['name' => $name, 'scopes' => $scopes]
        );

        return $this->success($key, 201);
    }

    /**
     * Revoke API key
     */
    public function revoke_api_key(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $key_id = (int) $request->get_param('key_id');
        $user_id = get_current_user_id();

        if (!$this->user_has_account_role($account_id, $user_id, 'admin')) {
            $this->log_access_denied($account_id, 'revoke_api_key', Peanut_Audit_Log_Service::RESOURCE_API_KEY, $key_id);
            return $this->error('forbidden', 'Admin access required', 403);
        }

        // Verify key belongs to account
        $key = Peanut_Api_Keys_Service::get_by_id($key_id);
        if (!$key || $key['account_id'] !== $account_id) {
            return $this->not_found('API key not found');
        }

        $result = Peanut_Api_Keys_Service::revoke($key_id, $user_id);

        if (!$result) {
            return $this->error('revoke_failed', 'Failed to revoke API key');
        }

        Peanut_Audit_Log_Service::log(
            $account_id,
            Peanut_Audit_Log_Service::ACTION_REVOKE,
            Peanut_Audit_Log_Service::RESOURCE_API_KEY,
            $key_id,
            ['name' => $key['name']]
        );

        return $this->success(['revoked' => true]);
    }

    /**
     * Regenerate API key
     */
    public function regenerate_api_key(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $key_id = (int) $request->get_param('key_id');
        $user_id = get_current_user_id();

        if (!$this->user_has_account_role($account_id, $user_id, 'admin')) {
            $this->log_access_denied($account_id, 'regenerate_api_key', Peanut_Audit_Log_Service::RESOURCE_API_KEY, $key_id);
            return $this->error('forbidden', 'Admin access required', 403);
        }

        // Verify key belongs to account
        $old_key = Peanut_Api_Keys_Service::get_by_id($key_id);
        if (!$old_key || $old_key['account_id'] !== $account_id) {
            return $this->not_found('API key not found');
        }

        $new_key = Peanut_Api_Keys_Service::regenerate($key_id, $user_id);

        if (!$new_key) {
            return $this->error('regenerate_failed', 'Failed to regenerate API key');
        }

        Peanut_Audit_Log_Service::log(
            $account_id,
            Peanut_Audit_Log_Service::ACTION_CREATE,
            Peanut_Audit_Log_Service::RESOURCE_API_KEY,
            $new_key['id'],
            ['action' => 'regenerate', 'old_key_id' => $key_id]
        );

        return $this->success($new_key, 201);
    }

    /**
     * Get available API key scopes
     */
    public function get_api_key_scopes(WP_REST_Request $request): WP_REST_Response {
        return $this->success(Peanut_Api_Keys_Service::SCOPES);
    }

    // ===============================
    // Audit Log Methods
    // ===============================

    /**
     * Get audit logs
     */
    public function get_audit_logs(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $user_id = get_current_user_id();

        if (!$this->user_has_account_role($account_id, $user_id, 'admin')) {
            $this->log_access_denied($account_id, 'get_audit_logs', 'audit_log');
            return $this->error('forbidden', 'Admin access required', 403);
        }

        $pagination = $this->get_pagination($request);

        $args = [
            'action' => $request->get_param('action'),
            'resource_type' => $request->get_param('resource_type'),
            'user_id' => $request->get_param('user_id') ? (int) $request->get_param('user_id') : null,
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
            'page' => $pagination['page'],
            'per_page' => $pagination['per_page'],
        ];

        $result = Peanut_Audit_Log_Service::get_logs($account_id, $args);

        return $this->paginated(
            $result['items'],
            $result['total'],
            $result['page'],
            $result['per_page']
        );
    }

    /**
     * Export audit logs
     */
    public function export_audit_logs(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $user_id = get_current_user_id();

        if (!$this->user_has_account_role($account_id, $user_id, 'admin')) {
            $this->log_access_denied($account_id, 'export_audit_logs', 'audit_log');
            return $this->error('forbidden', 'Admin access required', 403);
        }

        $args = [
            'action' => $request->get_param('action'),
            'resource_type' => $request->get_param('resource_type'),
            'user_id' => $request->get_param('user_id') ? (int) $request->get_param('user_id') : null,
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
        ];

        $logs = Peanut_Audit_Log_Service::export($account_id, $args);

        return $this->success($logs);
    }

    /**
     * Get audit log filter options
     */
    public function get_audit_log_filters(WP_REST_Request $request): WP_REST_Response {
        return $this->success([
            'actions' => Peanut_Audit_Log_Service::get_available_actions(),
            'resource_types' => Peanut_Audit_Log_Service::get_available_resource_types(),
        ]);
    }

    // ===============================
    // Feature & Permission Methods
    // ===============================

    /**
     * Get available features for the current account
     */
    public function get_available_features(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = get_current_user_id();
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);

        if (!$account) {
            return $this->error('account_error', 'Failed to get account', 500);
        }

        $features = Peanut_Account_Service::get_available_features($account['tier']);
        return $this->success($features);
    }

    /**
     * Get current user's permissions for an account
     */
    public function get_my_permissions(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $user_id = get_current_user_id();

        if (!$this->user_can_access_account($account_id, $user_id)) {
            return $this->error('forbidden', 'Access denied', 403);
        }

        $role = Peanut_Account_Service::get_user_role($account_id, $user_id);
        $permissions = Peanut_Account_Service::get_member_permissions($account_id, $user_id);
        $account = Peanut_Account_Service::get_by_id($account_id);
        $available_features = Peanut_Account_Service::get_available_features($account['tier'] ?? 'free');

        return $this->success([
            'role' => $role,
            'permissions' => $permissions,
            'available_features' => $available_features,
        ]);
    }

    // ===============================
    // Team Login Settings Methods
    // ===============================

    /**
     * Get team login page settings
     */
    public function get_login_settings(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $user_id = get_current_user_id();

        if (!$this->user_has_account_role($account_id, $user_id, 'admin')) {
            return $this->error('forbidden', 'Admin access required', 403);
        }

        $account = Peanut_Account_Service::get_by_id($account_id);
        if (!$account) {
            return $this->not_found('Account not found');
        }

        $settings = $account['settings'] ?? [];
        $login_settings = $settings['team_login'] ?? [];

        // Get default shortcode
        $shortcode = '[peanut_team_login';
        if (!empty($login_settings['logo_url'])) {
            $shortcode .= ' logo="' . esc_attr($login_settings['logo_url']) . '"';
        }
        if (!empty($login_settings['title'])) {
            $shortcode .= ' title="' . esc_attr($login_settings['title']) . '"';
        }
        if (!empty($login_settings['redirect_url'])) {
            $shortcode .= ' redirect="' . esc_attr($login_settings['redirect_url']) . '"';
        }
        $shortcode .= ']';

        return $this->success([
            'login_page_id' => $login_settings['page_id'] ?? null,
            'login_page_url' => $login_settings['page_url'] ?? null,
            'logo_url' => $login_settings['logo_url'] ?? '',
            'title' => $login_settings['title'] ?? __('Team Login', 'peanut-suite'),
            'redirect_url' => $login_settings['redirect_url'] ?? admin_url('admin.php?page=peanut-app'),
            'shortcode' => $shortcode,
        ]);
    }

    /**
     * Update team login page settings
     */
    public function update_login_settings(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = (int) $request->get_param('account_id');
        $user_id = get_current_user_id();

        if (!$this->user_has_account_role($account_id, $user_id, 'admin')) {
            $this->log_access_denied($account_id, 'update_login_settings', Peanut_Audit_Log_Service::RESOURCE_ACCOUNT, $account_id);
            return $this->error('forbidden', 'Admin access required', 403);
        }

        $account = Peanut_Account_Service::get_by_id($account_id);
        if (!$account) {
            return $this->not_found('Account not found');
        }

        // Get existing settings
        $settings = $account['settings'] ?? [];
        $login_settings = $settings['team_login'] ?? [];

        // Update login settings
        $new_login_settings = [
            'page_id' => $request->has_param('login_page_id') ? absint($request->get_param('login_page_id')) : ($login_settings['page_id'] ?? null),
            'page_url' => $request->has_param('login_page_url') ? esc_url_raw($request->get_param('login_page_url')) : ($login_settings['page_url'] ?? null),
            'logo_url' => $request->has_param('logo_url') ? esc_url_raw($request->get_param('logo_url')) : ($login_settings['logo_url'] ?? ''),
            'title' => $request->has_param('title') ? sanitize_text_field($request->get_param('title')) : ($login_settings['title'] ?? __('Team Login', 'peanut-suite')),
            'redirect_url' => $request->has_param('redirect_url') ? esc_url_raw($request->get_param('redirect_url')) : ($login_settings['redirect_url'] ?? admin_url('admin.php?page=peanut-app')),
        ];

        $settings['team_login'] = $new_login_settings;

        // Update account settings
        $result = Peanut_Account_Service::update($account_id, ['settings' => $settings]);

        if ($result) {
            Peanut_Audit_Log_Service::log(
                $account_id,
                Peanut_Audit_Log_Service::ACTION_UPDATE,
                Peanut_Audit_Log_Service::RESOURCE_ACCOUNT,
                $account_id,
                ['fields' => ['team_login_settings']]
            );
        }

        // Generate updated shortcode
        $shortcode = '[peanut_team_login';
        if (!empty($new_login_settings['logo_url'])) {
            $shortcode .= ' logo="' . esc_attr($new_login_settings['logo_url']) . '"';
        }
        if (!empty($new_login_settings['title'])) {
            $shortcode .= ' title="' . esc_attr($new_login_settings['title']) . '"';
        }
        if (!empty($new_login_settings['redirect_url'])) {
            $shortcode .= ' redirect="' . esc_attr($new_login_settings['redirect_url']) . '"';
        }
        $shortcode .= ']';

        return $this->success([
            'login_page_id' => $new_login_settings['page_id'],
            'login_page_url' => $new_login_settings['page_url'],
            'logo_url' => $new_login_settings['logo_url'],
            'title' => $new_login_settings['title'],
            'redirect_url' => $new_login_settings['redirect_url'],
            'shortcode' => $shortcode,
        ]);
    }

    // ===============================
    // Helper Methods
    // ===============================

    /**
     * Check if user can access account
     */
    private function user_can_access_account(int $account_id, int $user_id): bool {
        return Peanut_Account_Service::get_user_role($account_id, $user_id) !== null;
    }

    /**
     * Check if user has minimum role in account
     */
    private function user_has_account_role(int $account_id, int $user_id, string $minimum_role): bool {
        return Peanut_Account_Service::user_has_role($account_id, $user_id, $minimum_role);
    }

    /**
     * Sanitize permissions array
     */
    private function sanitize_permissions(array $permissions): array {
        $valid_features = array_keys(Peanut_Account_Service::FEATURES);
        $sanitized = [];

        foreach ($permissions as $feature => $config) {
            $feature = sanitize_key($feature);
            if (!in_array($feature, $valid_features, true)) {
                continue;
            }

            $sanitized[$feature] = [
                'access' => !empty($config['access']),
            ];
        }

        return $sanitized;
    }

    /**
     * Check if user is within invite rate limit
     */
    private function check_invite_rate_limit(int $account_id, int $user_id): bool {
        $transient_key = "peanut_invite_limit_{$account_id}_{$user_id}";
        $count = (int) get_transient($transient_key);

        return $count < self::INVITE_RATE_LIMIT;
    }

    /**
     * Increment invite counter for rate limiting
     */
    private function increment_invite_counter(int $account_id, int $user_id): void {
        $transient_key = "peanut_invite_limit_{$account_id}_{$user_id}";
        $count = (int) get_transient($transient_key);

        set_transient($transient_key, $count + 1, self::INVITE_RATE_WINDOW);
    }

    /**
     * Log access denied for sensitive operations
     */
    private function log_access_denied(int $account_id, string $action, string $resource_type, ?int $resource_id = null): void {
        Peanut_Audit_Log_Service::log(
            $account_id,
            Peanut_Audit_Log_Service::ACTION_ACCESS_DENIED,
            $resource_type,
            $resource_id,
            ['action' => $action, 'reason' => 'insufficient_permissions']
        );
    }
}

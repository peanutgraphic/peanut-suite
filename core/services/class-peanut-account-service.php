<?php
/**
 * Account Service
 *
 * Handles account and team member management for multi-tenancy.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Account_Service {

    /**
     * Account statuses
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Member roles
     */
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';
    public const ROLE_VIEWER = 'viewer';

    /**
     * Role hierarchy (higher number = more permissions)
     */
    private const ROLE_LEVELS = [
        self::ROLE_VIEWER => 1,
        self::ROLE_MEMBER => 2,
        self::ROLE_ADMIN => 3,
        self::ROLE_OWNER => 4,
    ];

    /**
     * Get or create account for user
     */
    public static function get_or_create_for_user(int $user_id): ?array {
        global $wpdb;
        $table = Peanut_Database::accounts_table();
        $members_table = Peanut_Database::account_members_table();

        // First, check if user has any account membership
        $account_id = $wpdb->get_var($wpdb->prepare(
            "SELECT account_id FROM $members_table WHERE user_id = %d LIMIT 1",
            $user_id
        ));

        if ($account_id) {
            return self::get_by_id((int) $account_id);
        }

        // Check if user owns any account
        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE owner_user_id = %d LIMIT 1",
            $user_id
        ), ARRAY_A);

        if ($account) {
            return self::prepare_account($account);
        }

        // Create a default account for the user
        $user = get_userdata($user_id);
        if (!$user) {
            return null;
        }

        $name = $user->display_name ?: $user->user_login;
        $slug = sanitize_title($name . '-' . $user_id);

        $wpdb->insert($table, [
            'name' => $name . "'s Account",
            'slug' => $slug,
            'status' => self::STATUS_ACTIVE,
            'tier' => 'free',
            'max_users' => 1,
            'owner_user_id' => $user_id,
        ]);

        $account_id = $wpdb->insert_id;
        if (!$account_id) {
            return null;
        }

        // Add owner as member
        $wpdb->insert($members_table, [
            'account_id' => $account_id,
            'user_id' => $user_id,
            'role' => self::ROLE_OWNER,
            'accepted_at' => current_time('mysql'),
        ]);

        return self::get_by_id($account_id);
    }

    /**
     * Get account by ID
     */
    public static function get_by_id(int $id): ?array {
        global $wpdb;
        $table = Peanut_Database::accounts_table();

        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ), ARRAY_A);

        return $account ? self::prepare_account($account) : null;
    }

    /**
     * Get all accounts for user
     */
    public static function get_accounts_for_user(int $user_id): array {
        global $wpdb;
        $table = Peanut_Database::accounts_table();
        $members_table = Peanut_Database::account_members_table();

        $accounts = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, m.role
             FROM $table a
             JOIN $members_table m ON a.id = m.account_id
             WHERE m.user_id = %d AND a.status = %s
             ORDER BY a.name ASC",
            $user_id,
            self::STATUS_ACTIVE
        ), ARRAY_A);

        return array_map([self::class, 'prepare_account'], $accounts);
    }

    /**
     * Update account
     */
    public static function update(int $id, array $data): bool {
        global $wpdb;
        $table = Peanut_Database::accounts_table();

        $allowed = ['name', 'settings'];
        $update_data = [];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                if ($field === 'settings') {
                    $update_data[$field] = wp_json_encode($data[$field]);
                } else {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                }
            }
        }

        if (empty($update_data)) {
            return false;
        }

        return (bool) $wpdb->update($table, $update_data, ['id' => $id]);
    }

    /**
     * Get account stats
     */
    public static function get_stats(int $account_id): array {
        global $wpdb;
        $members_table = Peanut_Database::account_members_table();
        $api_keys_table = Peanut_Database::api_keys_table();

        $member_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE account_id = %d",
            $account_id
        ));

        $active_keys = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $api_keys_table
             WHERE account_id = %d AND revoked_at IS NULL",
            $account_id
        ));

        return [
            'member_count' => $member_count,
            'active_api_keys' => $active_keys,
        ];
    }

    /**
     * Check if user has role in account
     */
    public static function user_has_role(int $account_id, int $user_id, string $minimum_role): bool {
        global $wpdb;
        $members_table = Peanut_Database::account_members_table();

        $role = $wpdb->get_var($wpdb->prepare(
            "SELECT role FROM $members_table
             WHERE account_id = %d AND user_id = %d",
            $account_id,
            $user_id
        ));

        if (!$role) {
            return false;
        }

        $user_level = self::ROLE_LEVELS[$role] ?? 0;
        $required_level = self::ROLE_LEVELS[$minimum_role] ?? 0;

        return $user_level >= $required_level;
    }

    /**
     * Get user role in account
     */
    public static function get_user_role(int $account_id, int $user_id): ?string {
        global $wpdb;
        $members_table = Peanut_Database::account_members_table();

        return $wpdb->get_var($wpdb->prepare(
            "SELECT role FROM $members_table
             WHERE account_id = %d AND user_id = %d",
            $account_id,
            $user_id
        ));
    }

    /**
     * Get account members
     */
    public static function get_members(int $account_id): array {
        global $wpdb;
        $members_table = Peanut_Database::account_members_table();

        $members = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.user_login, u.user_email, u.display_name
             FROM $members_table m
             JOIN {$wpdb->users} u ON m.user_id = u.ID
             WHERE m.account_id = %d
             ORDER BY
                CASE m.role
                    WHEN 'owner' THEN 1
                    WHEN 'admin' THEN 2
                    WHEN 'member' THEN 3
                    ELSE 4
                END,
                m.created_at ASC",
            $account_id
        ), ARRAY_A);

        return array_map(function ($member) {
            return [
                'user_id' => (int) $member['user_id'],
                'user_login' => $member['user_login'],
                'user_email' => $member['user_email'],
                'display_name' => $member['display_name'],
                'role' => $member['role'],
                'invited_at' => $member['invited_at'],
                'accepted_at' => $member['accepted_at'],
                'created_at' => $member['created_at'],
            ];
        }, $members);
    }

    /**
     * Add member to account
     */
    public static function add_member(int $account_id, int $user_id, string $role, int $invited_by): bool {
        global $wpdb;
        $members_table = Peanut_Database::account_members_table();

        // Validate role
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_MEMBER, self::ROLE_VIEWER], true)) {
            return false;
        }

        // Check if already a member
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $members_table WHERE account_id = %d AND user_id = %d",
            $account_id,
            $user_id
        ));

        if ($exists) {
            return false;
        }

        // Check max users limit
        $account = self::get_by_id($account_id);
        if (!$account) {
            return false;
        }

        $current_count = count(self::get_members($account_id));
        if ($current_count >= $account['max_users']) {
            return false;
        }

        $result = $wpdb->insert($members_table, [
            'account_id' => $account_id,
            'user_id' => $user_id,
            'role' => $role,
            'invited_by' => $invited_by,
            'invited_at' => current_time('mysql'),
            'accepted_at' => current_time('mysql'), // Auto-accept for now
        ]);

        return (bool) $result;
    }

    /**
     * Update member role
     */
    public static function update_member_role(int $account_id, int $user_id, string $new_role): bool {
        global $wpdb;
        $members_table = Peanut_Database::account_members_table();

        // Cannot change owner role directly
        $current_role = self::get_user_role($account_id, $user_id);
        if ($current_role === self::ROLE_OWNER) {
            return false;
        }

        // Validate new role
        if (!in_array($new_role, [self::ROLE_ADMIN, self::ROLE_MEMBER, self::ROLE_VIEWER], true)) {
            return false;
        }

        return (bool) $wpdb->update(
            $members_table,
            ['role' => $new_role],
            ['account_id' => $account_id, 'user_id' => $user_id]
        );
    }

    /**
     * Remove member from account
     */
    public static function remove_member(int $account_id, int $user_id): bool {
        global $wpdb;
        $members_table = Peanut_Database::account_members_table();

        // Cannot remove owner
        $role = self::get_user_role($account_id, $user_id);
        if ($role === self::ROLE_OWNER) {
            return false;
        }

        return (bool) $wpdb->delete($members_table, [
            'account_id' => $account_id,
            'user_id' => $user_id,
        ]);
    }

    /**
     * Transfer ownership
     */
    public static function transfer_ownership(int $account_id, int $current_owner_id, int $new_owner_id): bool {
        global $wpdb;
        $table = Peanut_Database::accounts_table();
        $members_table = Peanut_Database::account_members_table();

        // Verify current owner
        $account = self::get_by_id($account_id);
        if (!$account || (int) $account['owner_user_id'] !== $current_owner_id) {
            return false;
        }

        // Verify new owner is a member
        $new_owner_role = self::get_user_role($account_id, $new_owner_id);
        if (!$new_owner_role) {
            return false;
        }

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Update account owner
            $wpdb->update($table, ['owner_user_id' => $new_owner_id], ['id' => $account_id]);

            // Update roles
            $wpdb->update(
                $members_table,
                ['role' => self::ROLE_ADMIN],
                ['account_id' => $account_id, 'user_id' => $current_owner_id]
            );

            $wpdb->update(
                $members_table,
                ['role' => self::ROLE_OWNER],
                ['account_id' => $account_id, 'user_id' => $new_owner_id]
            );

            $wpdb->query('COMMIT');
            return true;
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Prepare account for response
     */
    private static function prepare_account(array $account): array {
        return [
            'id' => (int) $account['id'],
            'name' => $account['name'],
            'slug' => $account['slug'],
            'status' => $account['status'],
            'tier' => $account['tier'],
            'max_users' => (int) $account['max_users'],
            'owner_user_id' => (int) $account['owner_user_id'],
            'role' => $account['role'] ?? null,
            'settings' => $account['settings'] ? json_decode($account['settings'], true) : [],
            'created_at' => $account['created_at'],
            'updated_at' => $account['updated_at'],
        ];
    }
}

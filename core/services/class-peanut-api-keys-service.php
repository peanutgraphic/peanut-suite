<?php
/**
 * API Keys Service
 *
 * Handles API key generation, validation, and management.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Api_Keys_Service {

    /**
     * Available API scopes
     */
    public const SCOPES = [
        'links:read',
        'links:write',
        'utms:read',
        'utms:write',
        'contacts:read',
        'contacts:write',
        'analytics:read',
    ];

    /**
     * Key prefix for identification
     */
    private const KEY_PREFIX = 'pnut_';

    /**
     * Create new API key
     */
    public static function create(int $account_id, int $user_id, string $name, array $scopes, ?string $expires_at = null): ?array {
        global $wpdb;
        $table = Peanut_Database::api_keys_table();

        // Validate scopes
        $valid_scopes = array_intersect($scopes, self::SCOPES);
        if (empty($valid_scopes)) {
            return null;
        }

        // Generate key components
        $key_id = self::generate_key_id();
        $secret = self::generate_secret();
        $key_hash = password_hash($secret, PASSWORD_DEFAULT);

        $result = $wpdb->insert($table, [
            'account_id' => $account_id,
            'key_id' => $key_id,
            'key_hash' => $key_hash,
            'name' => sanitize_text_field($name),
            'scopes' => wp_json_encode($valid_scopes),
            'created_by' => $user_id,
            'expires_at' => $expires_at,
        ]);

        if (!$result) {
            return null;
        }

        $id = $wpdb->insert_id;

        // Return with unhashed key (only shown once)
        return [
            'id' => $id,
            'key_id' => $key_id,
            'key' => self::KEY_PREFIX . $key_id . '_' . $secret,
            'name' => $name,
            'scopes' => $valid_scopes,
            'expires_at' => $expires_at,
            'created_at' => current_time('mysql'),
        ];
    }

    /**
     * Get API keys for account
     */
    public static function get_by_account(int $account_id, bool $include_revoked = false): array {
        global $wpdb;
        $table = Peanut_Database::api_keys_table();

        $where = $include_revoked ? '' : 'AND revoked_at IS NULL';

        $keys = $wpdb->get_results($wpdb->prepare(
            "SELECT k.*, u.display_name as created_by_name
             FROM $table k
             LEFT JOIN {$wpdb->users} u ON k.created_by = u.ID
             WHERE k.account_id = %d $where
             ORDER BY k.created_at DESC",
            $account_id
        ), ARRAY_A);

        return array_map([self::class, 'prepare_key'], $keys);
    }

    /**
     * Get single API key by ID
     */
    public static function get_by_id(int $id): ?array {
        global $wpdb;
        $table = Peanut_Database::api_keys_table();

        $key = $wpdb->get_row($wpdb->prepare(
            "SELECT k.*, u.display_name as created_by_name
             FROM $table k
             LEFT JOIN {$wpdb->users} u ON k.created_by = u.ID
             WHERE k.id = %d",
            $id
        ), ARRAY_A);

        return $key ? self::prepare_key($key) : null;
    }

    /**
     * Validate API key and return key data if valid
     */
    public static function validate(string $full_key): ?array {
        global $wpdb;
        $table = Peanut_Database::api_keys_table();

        // Parse key format: pnut_{key_id}_{secret}
        if (!str_starts_with($full_key, self::KEY_PREFIX)) {
            return null;
        }

        $key_parts = explode('_', substr($full_key, strlen(self::KEY_PREFIX)));
        if (count($key_parts) !== 2) {
            return null;
        }

        [$key_id, $secret] = $key_parts;

        $key = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE key_id = %s AND revoked_at IS NULL",
            $key_id
        ), ARRAY_A);

        if (!$key) {
            return null;
        }

        // Check expiration
        if ($key['expires_at'] && strtotime($key['expires_at']) < time()) {
            return null;
        }

        // Verify secret
        if (!password_verify($secret, $key['key_hash'])) {
            return null;
        }

        return [
            'id' => (int) $key['id'],
            'account_id' => (int) $key['account_id'],
            'key_id' => $key['key_id'],
            'name' => $key['name'],
            'scopes' => json_decode($key['scopes'], true),
            'created_by' => (int) $key['created_by'],
        ];
    }

    /**
     * Update last used timestamp
     */
    public static function update_last_used(int $id, ?string $ip_address = null): void {
        global $wpdb;
        $table = Peanut_Database::api_keys_table();

        $wpdb->update($table, [
            'last_used_at' => current_time('mysql'),
            'last_used_ip' => $ip_address,
        ], ['id' => $id]);
    }

    /**
     * Revoke API key
     */
    public static function revoke(int $id, int $user_id): bool {
        global $wpdb;
        $table = Peanut_Database::api_keys_table();

        return (bool) $wpdb->update($table, [
            'revoked_at' => current_time('mysql'),
            'revoked_by' => $user_id,
        ], ['id' => $id]);
    }

    /**
     * Regenerate API key (revoke old and create new with same settings)
     */
    public static function regenerate(int $id, int $user_id): ?array {
        $old_key = self::get_by_id($id);
        if (!$old_key || $old_key['revoked_at']) {
            return null;
        }

        // Revoke old key
        self::revoke($id, $user_id);

        // Create new key with same settings
        return self::create(
            $old_key['account_id'],
            $user_id,
            $old_key['name'],
            $old_key['scopes'],
            $old_key['expires_at']
        );
    }

    /**
     * Check if key has specific scope
     */
    public static function has_scope(array $key_data, string $scope): bool {
        return in_array($scope, $key_data['scopes'], true);
    }

    /**
     * Delete all keys for account (for account deletion)
     */
    public static function delete_by_account(int $account_id): int {
        global $wpdb;
        $table = Peanut_Database::api_keys_table();

        return (int) $wpdb->delete($table, ['account_id' => $account_id]);
    }

    /**
     * Generate unique key ID
     */
    private static function generate_key_id(): string {
        return bin2hex(random_bytes(12));
    }

    /**
     * Generate secret
     */
    private static function generate_secret(): string {
        return bin2hex(random_bytes(24));
    }

    /**
     * Prepare key for response (hide sensitive data)
     */
    private static function prepare_key(array $key): array {
        return [
            'id' => (int) $key['id'],
            'account_id' => (int) $key['account_id'],
            'key_id' => $key['key_id'],
            'key_preview' => self::KEY_PREFIX . $key['key_id'] . '_****',
            'name' => $key['name'],
            'scopes' => json_decode($key['scopes'], true),
            'created_by' => (int) $key['created_by'],
            'created_by_name' => $key['created_by_name'] ?? null,
            'last_used_at' => $key['last_used_at'],
            'last_used_ip' => $key['last_used_ip'],
            'expires_at' => $key['expires_at'],
            'revoked_at' => $key['revoked_at'],
            'revoked_by' => $key['revoked_by'] ? (int) $key['revoked_by'] : null,
            'created_at' => $key['created_at'],
        ];
    }
}

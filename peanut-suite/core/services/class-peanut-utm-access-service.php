<?php
/**
 * UTM Access Service
 *
 * Handles UTM data segmentation and access control.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_UTM_Access_Service {

    /**
     * Access levels
     */
    public const ACCESS_VIEW = 'view';
    public const ACCESS_EDIT = 'edit';
    public const ACCESS_FULL = 'full';

    /**
     * Access level hierarchy
     */
    private const ACCESS_LEVELS = [
        self::ACCESS_VIEW => 1,
        self::ACCESS_EDIT => 2,
        self::ACCESS_FULL => 3,
    ];

    /**
     * Grant user access to UTM
     */
    public static function grant_access(
        int $utm_id,
        int $user_id,
        int $account_id,
        string $access_level,
        int $assigned_by
    ): bool {
        global $wpdb;
        $table = Peanut_Database::utm_access_table();

        // Validate access level
        if (!isset(self::ACCESS_LEVELS[$access_level])) {
            $access_level = self::ACCESS_VIEW;
        }

        // Use INSERT ... ON DUPLICATE KEY UPDATE
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (utm_id, user_id, account_id, access_level, assigned_by, assigned_at)
             VALUES (%d, %d, %d, %s, %d, %s)
             ON DUPLICATE KEY UPDATE
             access_level = VALUES(access_level),
             assigned_by = VALUES(assigned_by),
             assigned_at = VALUES(assigned_at)",
            $utm_id,
            $user_id,
            $account_id,
            $access_level,
            $assigned_by,
            current_time('mysql')
        ));

        return $result !== false;
    }

    /**
     * Revoke user access to UTM
     */
    public static function revoke_access(int $utm_id, int $user_id): bool {
        global $wpdb;
        $table = Peanut_Database::utm_access_table();

        return (bool) $wpdb->delete($table, [
            'utm_id' => $utm_id,
            'user_id' => $user_id,
        ]);
    }

    /**
     * Get all UTM IDs user has access to
     */
    public static function get_accessible_utm_ids(int $user_id, int $account_id): array {
        global $wpdb;
        $table = Peanut_Database::utm_access_table();

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT utm_id FROM $table
             WHERE user_id = %d AND account_id = %d",
            $user_id,
            $account_id
        ));

        return array_map('intval', $ids);
    }

    /**
     * Get all users with access to UTM
     */
    public static function get_utm_access_users(int $utm_id): array {
        global $wpdb;
        $table = Peanut_Database::utm_access_table();

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT ua.*, u.user_email, u.display_name
             FROM $table ua
             JOIN {$wpdb->users} u ON ua.user_id = u.ID
             WHERE ua.utm_id = %d
             ORDER BY ua.assigned_at DESC",
            $utm_id
        ), ARRAY_A);

        return array_map(function ($row) {
            return [
                'utm_id' => (int) $row['utm_id'],
                'user_id' => (int) $row['user_id'],
                'user_email' => $row['user_email'],
                'user_name' => $row['display_name'],
                'access_level' => $row['access_level'],
                'assigned_by' => (int) $row['assigned_by'],
                'assigned_at' => $row['assigned_at'],
            ];
        }, $results);
    }

    /**
     * Check if user has access to specific UTM
     */
    public static function user_has_access(int $utm_id, int $user_id, string $required_level = 'view'): bool {
        global $wpdb;
        $table = Peanut_Database::utm_access_table();

        $access_level = $wpdb->get_var($wpdb->prepare(
            "SELECT access_level FROM $table
             WHERE utm_id = %d AND user_id = %d",
            $utm_id,
            $user_id
        ));

        if (!$access_level) {
            return false;
        }

        $user_level = self::ACCESS_LEVELS[$access_level] ?? 0;
        $required = self::ACCESS_LEVELS[$required_level] ?? 0;

        return $user_level >= $required;
    }

    /**
     * Bulk assign UTMs to users
     */
    public static function bulk_assign(
        array $utm_ids,
        array $user_ids,
        int $account_id,
        string $access_level,
        int $assigned_by
    ): bool {
        global $wpdb;
        $table = Peanut_Database::utm_access_table();

        if (empty($utm_ids) || empty($user_ids)) {
            return false;
        }

        // Validate access level
        if (!isset(self::ACCESS_LEVELS[$access_level])) {
            $access_level = self::ACCESS_VIEW;
        }

        $values = [];
        $now = current_time('mysql');

        foreach ($utm_ids as $utm_id) {
            foreach ($user_ids as $user_id) {
                $values[] = $wpdb->prepare(
                    "(%d, %d, %d, %s, %d, %s)",
                    $utm_id,
                    $user_id,
                    $account_id,
                    $access_level,
                    $assigned_by,
                    $now
                );
            }
        }

        if (empty($values)) {
            return false;
        }

        $values_sql = implode(',', $values);

        $result = $wpdb->query(
            "INSERT INTO $table (utm_id, user_id, account_id, access_level, assigned_by, assigned_at)
             VALUES $values_sql
             ON DUPLICATE KEY UPDATE
             access_level = VALUES(access_level),
             assigned_by = VALUES(assigned_by),
             assigned_at = VALUES(assigned_at)"
        );

        return $result !== false;
    }

    /**
     * Remove all access for a UTM
     */
    public static function remove_all_access(int $utm_id): bool {
        global $wpdb;
        $table = Peanut_Database::utm_access_table();

        return (bool) $wpdb->delete($table, ['utm_id' => $utm_id]);
    }

    /**
     * Get UTMs accessible to user with pagination
     */
    public static function get_accessible_utms(
        int $user_id,
        int $account_id,
        int $page = 1,
        int $per_page = 20
    ): array {
        global $wpdb;
        $access_table = Peanut_Database::utm_access_table();
        $utms_table = Peanut_Database::utms_table();

        $offset = ($page - 1) * $per_page;

        $utms = $wpdb->get_results($wpdb->prepare(
            "SELECT u.*, ua.access_level
             FROM $utms_table u
             JOIN $access_table ua ON u.id = ua.utm_id
             WHERE ua.user_id = %d AND ua.account_id = %d
             ORDER BY u.created_at DESC
             LIMIT %d OFFSET %d",
            $user_id,
            $account_id,
            $per_page,
            $offset
        ), ARRAY_A);

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM $access_table
             WHERE user_id = %d AND account_id = %d",
            $user_id,
            $account_id
        ));

        return [
            'items' => $utms,
            'total' => $total,
            'pages' => ceil($total / $per_page),
        ];
    }
}

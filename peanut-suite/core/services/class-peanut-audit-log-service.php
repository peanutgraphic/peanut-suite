<?php
/**
 * Audit Log Service
 *
 * Handles audit logging for account activities.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Audit_Log_Service {

    /**
     * Action types
     */
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_INVITE = 'invite';
    public const ACTION_REVOKE = 'revoke';
    public const ACTION_EXPORT = 'export';

    /**
     * Resource types
     */
    public const RESOURCE_ACCOUNT = 'account';
    public const RESOURCE_MEMBER = 'member';
    public const RESOURCE_API_KEY = 'api_key';
    public const RESOURCE_LINK = 'link';
    public const RESOURCE_UTM = 'utm';
    public const RESOURCE_CONTACT = 'contact';
    public const RESOURCE_SETTINGS = 'settings';

    /**
     * Log an action
     */
    public static function log(
        int $account_id,
        string $action,
        string $resource_type,
        ?int $resource_id = null,
        ?array $details = null,
        ?int $user_id = null,
        ?int $api_key_id = null
    ): bool {
        global $wpdb;
        $table = Peanut_Database::audit_log_table();

        // Use current user if not specified
        if ($user_id === null && !$api_key_id) {
            $user_id = get_current_user_id();
        }

        $result = $wpdb->insert($table, [
            'account_id' => $account_id,
            'user_id' => $user_id,
            'api_key_id' => $api_key_id,
            'action' => sanitize_key($action),
            'resource_type' => sanitize_key($resource_type),
            'resource_id' => $resource_id,
            'details' => $details ? wp_json_encode($details) : null,
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])), 0, 500) : null,
        ]);

        return (bool) $result;
    }

    /**
     * Get audit logs for account with filtering
     */
    public static function get_logs(int $account_id, array $args = []): array {
        global $wpdb;
        $table = Peanut_Database::audit_log_table();

        $defaults = [
            'action' => null,
            'resource_type' => null,
            'user_id' => null,
            'date_from' => null,
            'date_to' => null,
            'per_page' => 20,
            'page' => 1,
        ];

        $args = wp_parse_args($args, $defaults);
        $where = ['l.account_id = %d'];
        $params = [$account_id];

        if ($args['action']) {
            $where[] = 'l.action = %s';
            $params[] = $args['action'];
        }

        if ($args['resource_type']) {
            $where[] = 'l.resource_type = %s';
            $params[] = $args['resource_type'];
        }

        if ($args['user_id']) {
            $where[] = 'l.user_id = %d';
            $params[] = $args['user_id'];
        }

        if ($args['date_from']) {
            $where[] = 'l.created_at >= %s';
            $params[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where[] = 'l.created_at <= %s';
            $params[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        // Get total count
        $count_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table l WHERE $where_clause",
            ...$params
        );
        $total = (int) $wpdb->get_var($count_sql);

        // Get paginated results
        $params[] = $args['per_page'];
        $params[] = $offset;

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name as user_name, u.user_email
             FROM $table l
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
             WHERE $where_clause
             ORDER BY l.created_at DESC
             LIMIT %d OFFSET %d",
            ...$params
        ), ARRAY_A);

        return [
            'items' => array_map([self::class, 'prepare_log'], $logs),
            'total' => $total,
            'page' => $args['page'],
            'per_page' => $args['per_page'],
            'total_pages' => ceil($total / $args['per_page']),
        ];
    }

    /**
     * Get single log entry
     */
    public static function get_by_id(int $id): ?array {
        global $wpdb;
        $table = Peanut_Database::audit_log_table();

        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT l.*, u.display_name as user_name, u.user_email
             FROM $table l
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
             WHERE l.id = %d",
            $id
        ), ARRAY_A);

        return $log ? self::prepare_log($log) : null;
    }

    /**
     * Export logs for account
     */
    public static function export(int $account_id, array $args = []): array {
        $args['per_page'] = 10000; // Max export limit
        $args['page'] = 1;

        $result = self::get_logs($account_id, $args);

        // Log the export action
        self::log($account_id, self::ACTION_EXPORT, 'audit_log', null, [
            'exported_count' => count($result['items']),
            'filters' => $args,
        ]);

        return $result['items'];
    }

    /**
     * Get available actions for filtering
     */
    public static function get_available_actions(): array {
        return [
            self::ACTION_CREATE,
            self::ACTION_UPDATE,
            self::ACTION_DELETE,
            self::ACTION_LOGIN,
            self::ACTION_LOGOUT,
            self::ACTION_INVITE,
            self::ACTION_REVOKE,
            self::ACTION_EXPORT,
        ];
    }

    /**
     * Get available resource types for filtering
     */
    public static function get_available_resource_types(): array {
        return [
            self::RESOURCE_ACCOUNT,
            self::RESOURCE_MEMBER,
            self::RESOURCE_API_KEY,
            self::RESOURCE_LINK,
            self::RESOURCE_UTM,
            self::RESOURCE_CONTACT,
            self::RESOURCE_SETTINGS,
        ];
    }

    /**
     * Delete old logs (for cleanup)
     */
    public static function cleanup(int $days = 90): int {
        global $wpdb;
        $table = Peanut_Database::audit_log_table();

        $cutoff = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));

        return (int) $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE created_at < %s",
            $cutoff
        ));
    }

    /**
     * Delete all logs for account (for account deletion)
     */
    public static function delete_by_account(int $account_id): int {
        global $wpdb;
        $table = Peanut_Database::audit_log_table();

        return (int) $wpdb->delete($table, ['account_id' => $account_id]);
    }

    /**
     * Get client IP address
     */
    private static function get_client_ip(): ?string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return null;
    }

    /**
     * Prepare log entry for response
     */
    private static function prepare_log(array $log): array {
        return [
            'id' => (int) $log['id'],
            'account_id' => (int) $log['account_id'],
            'user_id' => $log['user_id'] ? (int) $log['user_id'] : null,
            'user_name' => $log['user_name'] ?? null,
            'user_email' => $log['user_email'] ?? null,
            'api_key_id' => $log['api_key_id'] ? (int) $log['api_key_id'] : null,
            'action' => $log['action'],
            'resource_type' => $log['resource_type'],
            'resource_id' => $log['resource_id'] ? (int) $log['resource_id'] : null,
            'details' => $log['details'] ? json_decode($log['details'], true) : null,
            'ip_address' => $log['ip_address'],
            'user_agent' => $log['user_agent'],
            'created_at' => $log['created_at'],
        ];
    }
}

<?php
/**
 * Plesk Server Monitoring
 *
 * Handles Plesk server API integration and health monitoring.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Monitor_Plesk {

    /**
     * Health score weights
     */
    private const WEIGHTS = [
        'cpu_usage'      => 15,
        'ram_usage'      => 15,
        'disk_usage'     => 20,
        'services'       => 20,
        'ssl_certs'      => 15,
        'load_average'   => 10,
        'plesk_updates'  => 5,
    ];

    /**
     * Grade thresholds
     */
    private const GRADES = [
        'A' => 90,
        'B' => 80,
        'C' => 70,
        'D' => 60,
        'F' => 0,
    ];

    /**
     * Critical services to monitor
     */
    private const CRITICAL_SERVICES = [
        'apache', 'httpd', 'nginx', 'mysql', 'mariadb',
        'postgresql', 'php-fpm', 'named', 'bind', 'dovecot',
        'postfix', 'fail2ban', 'ssh', 'sshd'
    ];

    // =========================================
    // Server Management
    // =========================================

    /**
     * Add a new Plesk server
     */
    public static function add_server(int $user_id, array $data): array|WP_Error {
        global $wpdb;

        // Validate required fields
        if (empty($data['server_host'])) {
            return new WP_Error('missing_host', 'Server host is required');
        }
        if (empty($data['api_key'])) {
            return new WP_Error('missing_api_key', 'API key is required');
        }

        $host = sanitize_text_field($data['server_host']);
        $port = intval($data['server_port'] ?? 8443);
        $api_key = $data['api_key'];

        // Verify connection first
        $connection = self::verify_connection($host, $port, $api_key);
        if (is_wp_error($connection)) {
            return $connection;
        }

        // Check for existing server
        $table = Peanut_Database::monitor_servers_table();
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND server_host = %s",
            $user_id,
            $host
        ));

        if ($existing) {
            return new WP_Error('server_exists', 'This server is already being monitored');
        }

        // Encrypt the API key
        $encrypted_key = Peanut_Encryption::encrypt($api_key);

        // Insert server
        $result = $wpdb->insert($table, [
            'user_id'           => $user_id,
            'server_name'       => sanitize_text_field($data['server_name'] ?? $host),
            'server_host'       => $host,
            'server_port'       => $port,
            'api_key_encrypted' => $encrypted_key,
            'status'            => 'active',
            'plesk_version'     => $connection['plesk_version'] ?? null,
            'os_info'           => $connection['os_info'] ?? null,
            'last_health'       => wp_json_encode($connection['health'] ?? []),
            'last_check'        => current_time('mysql'),
        ], ['%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s']);

        if (!$result) {
            return new WP_Error('db_error', 'Failed to save server');
        }

        $server_id = $wpdb->insert_id;

        // Log initial health
        if (!empty($connection['health'])) {
            self::log_health($server_id, $connection['health']);
        }

        return [
            'id'          => $server_id,
            'server_name' => $data['server_name'] ?? $host,
            'server_host' => $host,
            'status'      => 'active',
            'health'      => $connection['health'] ?? null,
        ];
    }

    /**
     * Get all servers for a user
     */
    public static function get_servers(int $user_id, array $args = []): array {
        global $wpdb;

        $table = Peanut_Database::monitor_servers_table();

        $defaults = [
            'status'   => null,
            'search'   => null,
            'page'     => 1,
            'per_page' => 20,
            'orderby'  => 'server_name',
            'order'    => 'ASC',
        ];

        $args = wp_parse_args($args, $defaults);
        $where = ['user_id = %d'];
        $params = [$user_id];

        if ($args['status']) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }

        if ($args['search']) {
            $where[] = '(server_name LIKE %s OR server_host LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $where_sql = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']) ?: 'server_name ASC';
        $offset = ($args['page'] - 1) * $args['per_page'];

        // Get total
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}",
            ...$params
        ));

        // Get servers
        $servers = $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_id, server_name, server_host, server_port, status,
                    last_check, last_health, plesk_version, os_info, created_at
             FROM {$table}
             WHERE {$where_sql}
             ORDER BY {$orderby}
             LIMIT %d OFFSET %d",
            array_merge($params, [$args['per_page'], $offset])
        ), ARRAY_A);

        // Decode JSON fields
        foreach ($servers as &$server) {
            $server['last_health'] = json_decode($server['last_health'], true);
        }

        return [
            'data'       => $servers,
            'total'      => (int) $total,
            'page'       => $args['page'],
            'per_page'   => $args['per_page'],
            'total_pages' => ceil($total / $args['per_page']),
        ];
    }

    /**
     * Get a single server
     */
    public static function get_server(int $server_id, int $user_id): ?array {
        global $wpdb;

        $table = Peanut_Database::monitor_servers_table();

        $server = $wpdb->get_row($wpdb->prepare(
            "SELECT id, user_id, server_name, server_host, server_port, status,
                    last_check, last_health, plesk_version, os_info, created_at
             FROM {$table}
             WHERE id = %d AND user_id = %d",
            $server_id,
            $user_id
        ), ARRAY_A);

        if (!$server) {
            return null;
        }

        $server['last_health'] = json_decode($server['last_health'], true);

        return $server;
    }

    /**
     * Remove a server
     */
    public static function remove_server(int $server_id, int $user_id): bool {
        global $wpdb;

        $table = Peanut_Database::monitor_servers_table();
        $health_table = Peanut_Database::monitor_server_health_table();

        // Delete health history
        $wpdb->delete($health_table, ['server_id' => $server_id], ['%d']);

        // Delete server
        $result = $wpdb->delete($table, [
            'id'      => $server_id,
            'user_id' => $user_id,
        ], ['%d', '%d']);

        return $result > 0;
    }

    /**
     * Update server settings
     */
    public static function update_server(int $server_id, int $user_id, array $data): bool {
        global $wpdb;

        $table = Peanut_Database::monitor_servers_table();

        $update = [];
        $format = [];

        if (isset($data['server_name'])) {
            $update['server_name'] = sanitize_text_field($data['server_name']);
            $format[] = '%s';
        }

        if (isset($data['server_port'])) {
            $update['server_port'] = intval($data['server_port']);
            $format[] = '%d';
        }

        if (empty($update)) {
            return true;
        }

        return $wpdb->update($table, $update, [
            'id'      => $server_id,
            'user_id' => $user_id,
        ], $format, ['%d', '%d']) !== false;
    }

    // =========================================
    // Plesk API Communication
    // =========================================

    /**
     * Verify connection to a Plesk server
     */
    public static function verify_connection(string $host, int $port, string $api_key): array|WP_Error {
        $endpoint = "https://{$host}:{$port}/api/v2/server";

        $response = self::api_request($endpoint, $api_key);
        if (is_wp_error($response)) {
            return $response;
        }

        // Get additional server info
        $stats = self::api_request("https://{$host}:{$port}/api/v2/server/statistics", $api_key);

        return [
            'success'        => true,
            'plesk_version'  => $response['version'] ?? null,
            'os_info'        => $response['platform'] ?? null,
            'hostname'       => $response['hostname'] ?? $host,
            'health'         => is_wp_error($stats) ? null : self::calculate_health_from_stats($stats),
        ];
    }

    /**
     * Make an API request to Plesk
     */
    private static function api_request(string $url, string $api_key, string $method = 'GET', array $body = []): array|WP_Error {
        $args = [
            'method'    => $method,
            'timeout'   => 30,
            'sslverify' => true,
            'headers'   => [
                'Authorization' => 'Basic ' . base64_encode("admin:{$api_key}"),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
        ];

        if (!empty($body) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return new WP_Error('connection_failed', 'Failed to connect to Plesk server: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code === 401) {
            return new WP_Error('auth_failed', 'Invalid API key');
        }

        if ($code === 403) {
            return new WP_Error('forbidden', 'API access denied. Check API key permissions.');
        }

        if ($code >= 400) {
            return new WP_Error('api_error', "Plesk API error (HTTP {$code})");
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_response', 'Invalid JSON response from Plesk');
        }

        return $data;
    }

    /**
     * Get decrypted API key for a server
     */
    private static function get_api_key(int $server_id): ?string {
        global $wpdb;

        $table = Peanut_Database::monitor_servers_table();

        $encrypted = $wpdb->get_var($wpdb->prepare(
            "SELECT api_key_encrypted FROM {$table} WHERE id = %d",
            $server_id
        ));

        if (!$encrypted) {
            return null;
        }

        return Peanut_Encryption::decrypt($encrypted);
    }

    /**
     * Get server connection details
     */
    private static function get_server_connection(int $server_id): ?array {
        global $wpdb;

        $table = Peanut_Database::monitor_servers_table();

        $server = $wpdb->get_row($wpdb->prepare(
            "SELECT server_host, server_port, api_key_encrypted FROM {$table} WHERE id = %d",
            $server_id
        ), ARRAY_A);

        if (!$server) {
            return null;
        }

        return [
            'host'    => $server['server_host'],
            'port'    => $server['server_port'],
            'api_key' => Peanut_Encryption::decrypt($server['api_key_encrypted']),
        ];
    }

    // =========================================
    // Health Checks
    // =========================================

    /**
     * Run health check on a server
     */
    public static function check_health(int $server_id): array|WP_Error {
        $conn = self::get_server_connection($server_id);
        if (!$conn) {
            return new WP_Error('not_found', 'Server not found');
        }

        $base_url = "https://{$conn['host']}:{$conn['port']}/api/v2";

        // Fetch all data
        $server_info = self::api_request("{$base_url}/server", $conn['api_key']);
        $stats = self::api_request("{$base_url}/server/statistics", $conn['api_key']);
        $services = self::api_request("{$base_url}/server/services", $conn['api_key']);
        $domains = self::api_request("{$base_url}/domains", $conn['api_key']);

        // Handle connection failure
        if (is_wp_error($stats)) {
            self::update_server_status($server_id, 'error');
            return $stats;
        }

        // Calculate health
        $health = self::calculate_health($stats, $services, $domains, $server_info);

        // Update server
        self::update_server_health($server_id, $health);

        // Log health
        self::log_health($server_id, $health);

        return $health;
    }

    /**
     * Calculate comprehensive health score
     */
    private static function calculate_health(
        array $stats,
        array|WP_Error $services,
        array|WP_Error $domains,
        array|WP_Error $server_info
    ): array {
        $checks = [];
        $deductions = 0;

        // CPU Usage
        $cpu = $stats['cpu']['usage'] ?? 0;
        $checks['cpu_usage'] = [
            'value'   => $cpu,
            'status'  => $cpu > 90 ? 'critical' : ($cpu > 80 ? 'warning' : 'ok'),
            'message' => "CPU usage: {$cpu}%",
        ];
        if ($cpu > 90) {
            $deductions += self::WEIGHTS['cpu_usage'];
        } elseif ($cpu > 80) {
            $deductions += self::WEIGHTS['cpu_usage'] * 0.5;
        }

        // RAM Usage
        $ram_total = $stats['memory']['total'] ?? 1;
        $ram_used = $stats['memory']['used'] ?? 0;
        $ram_percent = $ram_total > 0 ? round(($ram_used / $ram_total) * 100) : 0;
        $checks['ram_usage'] = [
            'value'   => $ram_percent,
            'total'   => $ram_total,
            'used'    => $ram_used,
            'status'  => $ram_percent > 90 ? 'critical' : ($ram_percent > 80 ? 'warning' : 'ok'),
            'message' => "RAM usage: {$ram_percent}%",
        ];
        if ($ram_percent > 90) {
            $deductions += self::WEIGHTS['ram_usage'];
        } elseif ($ram_percent > 80) {
            $deductions += self::WEIGHTS['ram_usage'] * 0.5;
        }

        // Disk Usage
        $disk_total = $stats['disk']['total'] ?? 1;
        $disk_used = $stats['disk']['used'] ?? 0;
        $disk_percent = $disk_total > 0 ? round(($disk_used / $disk_total) * 100) : 0;
        $checks['disk_usage'] = [
            'value'   => $disk_percent,
            'total'   => $disk_total,
            'used'    => $disk_used,
            'free'    => $disk_total - $disk_used,
            'status'  => $disk_percent > 90 ? 'critical' : ($disk_percent > 80 ? 'warning' : 'ok'),
            'message' => "Disk usage: {$disk_percent}%",
        ];
        if ($disk_percent > 90) {
            $deductions += self::WEIGHTS['disk_usage'];
        } elseif ($disk_percent > 80) {
            $deductions += self::WEIGHTS['disk_usage'] * 0.5;
        }

        // Load Average
        $load = $stats['cpu']['loadAverage1min'] ?? 0;
        $checks['load_average'] = [
            'value'   => $load,
            'status'  => $load > 10 ? 'critical' : ($load > 5 ? 'warning' : 'ok'),
            'message' => "Load average: {$load}",
        ];
        if ($load > 10) {
            $deductions += self::WEIGHTS['load_average'];
        } elseif ($load > 5) {
            $deductions += self::WEIGHTS['load_average'] * 0.5;
        }

        // Services
        $stopped_services = [];
        if (!is_wp_error($services) && is_array($services)) {
            foreach ($services as $service) {
                $name = strtolower($service['id'] ?? $service['name'] ?? '');
                $running = $service['status'] === 'running' || $service['running'] === true;

                // Check if it's a critical service
                foreach (self::CRITICAL_SERVICES as $critical) {
                    if (strpos($name, $critical) !== false && !$running) {
                        $stopped_services[] = $service['name'] ?? $name;
                    }
                }
            }
        }
        $stopped_count = count($stopped_services);
        $checks['services'] = [
            'stopped'       => $stopped_services,
            'stopped_count' => $stopped_count,
            'status'        => $stopped_count > 0 ? 'critical' : 'ok',
            'message'       => $stopped_count > 0
                ? "{$stopped_count} critical service(s) stopped"
                : 'All critical services running',
        ];
        $deductions += min($stopped_count * 5, self::WEIGHTS['services']);

        // SSL Certificates
        $ssl_issues = [];
        $domains_data = [];
        if (!is_wp_error($domains) && is_array($domains)) {
            foreach ($domains as $domain) {
                $domain_name = $domain['name'] ?? '';
                $ssl_status = $domain['hosting']['sslCertificate'] ?? null;

                $domains_data[] = [
                    'name'       => $domain_name,
                    'status'     => $domain['status'] ?? 'unknown',
                    'ssl'        => $ssl_status,
                    'created_at' => $domain['createdAt'] ?? null,
                ];

                // Check SSL expiry if available
                if (isset($ssl_status['validTo'])) {
                    $expiry = strtotime($ssl_status['validTo']);
                    $days_until = ($expiry - time()) / DAY_IN_SECONDS;

                    if ($days_until < 0) {
                        $ssl_issues[] = "{$domain_name}: SSL expired";
                    } elseif ($days_until < 14) {
                        $ssl_issues[] = "{$domain_name}: SSL expires in " . round($days_until) . " days";
                    }
                }
            }
        }
        $ssl_issue_count = count($ssl_issues);
        $checks['ssl_certs'] = [
            'issues'      => $ssl_issues,
            'issue_count' => $ssl_issue_count,
            'status'      => $ssl_issue_count > 0 ? 'warning' : 'ok',
            'message'     => $ssl_issue_count > 0
                ? "{$ssl_issue_count} SSL certificate issue(s)"
                : 'All SSL certificates valid',
        ];
        if ($ssl_issue_count > 0) {
            // Expired = full deduction, expiring soon = half
            $expired = count(array_filter($ssl_issues, fn($i) => strpos($i, 'expired') !== false));
            $deductions += $expired > 0 ? self::WEIGHTS['ssl_certs'] : (self::WEIGHTS['ssl_certs'] * 0.5);
        }

        // Plesk Updates
        $plesk_version = $server_info['version'] ?? null;
        $updates_available = $server_info['updates']['available'] ?? false;
        $checks['plesk_updates'] = [
            'version'   => $plesk_version,
            'available' => $updates_available,
            'status'    => $updates_available ? 'warning' : 'ok',
            'message'   => $updates_available ? 'Plesk updates available' : 'Plesk is up to date',
        ];
        if ($updates_available) {
            $deductions += self::WEIGHTS['plesk_updates'];
        }

        // Calculate final score and grade
        $score = max(0, 100 - $deductions);
        $grade = self::score_to_grade($score);
        $status = self::score_to_status($score);

        return [
            'score'    => $score,
            'grade'    => $grade,
            'status'   => $status,
            'checks'   => $checks,
            'domains'  => $domains_data,
            'uptime'   => $stats['uptime'] ?? null,
        ];
    }

    /**
     * Calculate health from stats only (for initial connection)
     */
    private static function calculate_health_from_stats(array $stats): array {
        $deductions = 0;

        // Simple CPU/RAM/Disk check
        $cpu = $stats['cpu']['usage'] ?? 0;
        $ram_total = $stats['memory']['total'] ?? 1;
        $ram_used = $stats['memory']['used'] ?? 0;
        $ram_percent = $ram_total > 0 ? round(($ram_used / $ram_total) * 100) : 0;
        $disk_total = $stats['disk']['total'] ?? 1;
        $disk_used = $stats['disk']['used'] ?? 0;
        $disk_percent = $disk_total > 0 ? round(($disk_used / $disk_total) * 100) : 0;

        if ($cpu > 90) $deductions += 15;
        elseif ($cpu > 80) $deductions += 7;

        if ($ram_percent > 90) $deductions += 15;
        elseif ($ram_percent > 80) $deductions += 7;

        if ($disk_percent > 90) $deductions += 20;
        elseif ($disk_percent > 80) $deductions += 10;

        $score = max(0, 100 - $deductions);

        return [
            'score'  => $score,
            'grade'  => self::score_to_grade($score),
            'status' => self::score_to_status($score),
            'checks' => [
                'cpu_usage'  => ['value' => $cpu],
                'ram_usage'  => ['value' => $ram_percent],
                'disk_usage' => ['value' => $disk_percent],
            ],
        ];
    }

    /**
     * Convert score to letter grade
     */
    public static function score_to_grade(int $score): string {
        foreach (self::GRADES as $grade => $threshold) {
            if ($score >= $threshold) {
                return $grade;
            }
        }
        return 'F';
    }

    /**
     * Convert score to status
     */
    private static function score_to_status(int $score): string {
        if ($score >= 80) return 'healthy';
        if ($score >= 50) return 'warning';
        return 'critical';
    }

    /**
     * Update server status
     */
    private static function update_server_status(int $server_id, string $status): void {
        global $wpdb;

        $wpdb->update(
            Peanut_Database::monitor_servers_table(),
            ['status' => $status],
            ['id' => $server_id],
            ['%s'],
            ['%d']
        );
    }

    /**
     * Update server with latest health data
     */
    private static function update_server_health(int $server_id, array $health): void {
        global $wpdb;

        $status = $health['status'] ?? 'healthy';

        $wpdb->update(
            Peanut_Database::monitor_servers_table(),
            [
                'status'      => $status === 'offline' ? 'error' : 'active',
                'last_check'  => current_time('mysql'),
                'last_health' => wp_json_encode($health),
            ],
            ['id' => $server_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }

    /**
     * Log health check to history
     */
    private static function log_health(int $server_id, array $health): void {
        global $wpdb;

        $wpdb->insert(
            Peanut_Database::monitor_server_health_table(),
            [
                'server_id'  => $server_id,
                'status'     => $health['status'] ?? 'unknown',
                'score'      => $health['score'] ?? null,
                'grade'      => $health['grade'] ?? null,
                'checks'     => wp_json_encode($health['checks'] ?? []),
                'checked_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%d', '%s', '%s', '%s']
        );
    }

    /**
     * Get health history for a server
     */
    public static function get_health_history(int $server_id, int $days = 30): array {
        global $wpdb;

        $table = Peanut_Database::monitor_server_health_table();

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, status, score, grade, checks, checked_at
             FROM {$table}
             WHERE server_id = %d
               AND checked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             ORDER BY checked_at DESC",
            $server_id,
            $days
        ), ARRAY_A);

        foreach ($results as &$row) {
            $row['checks'] = json_decode($row['checks'], true);
        }

        return $results;
    }

    // =========================================
    // Domain & Service Info
    // =========================================

    /**
     * Get domains for a server
     */
    public static function get_domains(int $server_id): array|WP_Error {
        $conn = self::get_server_connection($server_id);
        if (!$conn) {
            return new WP_Error('not_found', 'Server not found');
        }

        $url = "https://{$conn['host']}:{$conn['port']}/api/v2/domains";
        $response = self::api_request($url, $conn['api_key']);

        if (is_wp_error($response)) {
            return $response;
        }

        $domains = [];
        foreach ($response as $domain) {
            $domains[] = [
                'id'          => $domain['id'] ?? null,
                'name'        => $domain['name'] ?? '',
                'status'      => $domain['status'] ?? 'unknown',
                'hosting'     => $domain['hosting']['type'] ?? 'unknown',
                'ssl'         => isset($domain['hosting']['sslCertificate']),
                'ssl_expiry'  => $domain['hosting']['sslCertificate']['validTo'] ?? null,
                'created_at'  => $domain['createdAt'] ?? null,
            ];
        }

        return $domains;
    }

    /**
     * Get services status for a server
     */
    public static function get_services(int $server_id): array|WP_Error {
        $conn = self::get_server_connection($server_id);
        if (!$conn) {
            return new WP_Error('not_found', 'Server not found');
        }

        $url = "https://{$conn['host']}:{$conn['port']}/api/v2/server/services";
        $response = self::api_request($url, $conn['api_key']);

        if (is_wp_error($response)) {
            return $response;
        }

        $services = [];
        foreach ($response as $service) {
            $services[] = [
                'id'      => $service['id'] ?? $service['name'] ?? '',
                'name'    => $service['name'] ?? $service['id'] ?? '',
                'running' => ($service['status'] ?? '') === 'running' || ($service['running'] ?? false),
                'status'  => $service['status'] ?? 'unknown',
            ];
        }

        return $services;
    }

    // =========================================
    // Cleanup
    // =========================================

    /**
     * Cleanup old health history
     */
    public static function cleanup_old_history(int $days = 90): int {
        global $wpdb;

        $table = Peanut_Database::monitor_server_health_table();

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE checked_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
}

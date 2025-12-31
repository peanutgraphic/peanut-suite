<?php
/**
 * Health Report Generator
 *
 * Generates health reports by aggregating data from WP sites and Plesk servers.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Health_Report_Generator {

    /**
     * Grade thresholds
     */
    private const GRADE_THRESHOLDS = [
        'A' => 90,
        'B' => 80,
        'C' => 70,
        'D' => 60,
        'F' => 0,
    ];

    /**
     * Generate a health report
     */
    public function generate(int $user_id, string $period_start, string $period_end, array $settings): array|WP_Error {
        global $wpdb;

        // Get selected IDs (empty array means all)
        $selected_site_ids = $settings['selected_site_ids'] ?? [];
        $selected_server_ids = $settings['selected_server_ids'] ?? [];

        // Get sites data if included
        $sites_data = [];
        if ($settings['include_sites']) {
            $sites_data = $this->aggregate_site_health($user_id, $period_start, $period_end, $selected_site_ids);
        }

        // Get servers data if included
        $servers_data = [];
        if ($settings['include_servers']) {
            $servers_data = $this->aggregate_server_health($user_id, $period_start, $period_end, $selected_server_ids);
        }

        // Calculate overall score
        $overall = $this->calculate_overall_grade($sites_data, $servers_data);

        // Generate recommendations if included
        $recommendations = [];
        if ($settings['include_recommendations']) {
            $recommendations_engine = new Health_Recommendations();
            $recommendations = $recommendations_engine->generate($sites_data, $servers_data);
        }

        // Get previous report for trends
        $trends = $this->calculate_trends($user_id, $overall['score']);

        // Build report data
        $report_data = [
            'period' => [
                'start' => $period_start,
                'end' => $period_end,
                'type' => $settings['frequency'],
            ],
            'overall' => $overall,
            'sites' => $sites_data,
            'servers' => $servers_data,
            'recommendations' => $recommendations,
            'trends' => $trends,
            'generated_at' => current_time('mysql'),
        ];

        // Save report to database
        $report_id = $this->save_report($user_id, $report_data, $settings['frequency']);

        if ($report_id) {
            $report_data['id'] = $report_id;
        }

        return $report_data;
    }

    /**
     * Aggregate health data from WordPress sites
     *
     * @param int $user_id User ID
     * @param string $period_start Period start date
     * @param string $period_end Period end date
     * @param array $selected_ids Optional array of site IDs to include (empty = all)
     */
    private function aggregate_site_health(int $user_id, string $period_start, string $period_end, array $selected_ids = []): array {
        global $wpdb;

        $sites_table = Monitor_Database::sites_table();

        // Build query with optional ID filter
        $query = "SELECT id, site_name, site_url, status, last_health, last_checked
             FROM {$sites_table}
             WHERE user_id = %d AND status = 'active'";
        $params = [$user_id];

        // Filter by selected IDs if provided
        if (!empty($selected_ids)) {
            $placeholders = implode(',', array_fill(0, count($selected_ids), '%d'));
            $query .= " AND id IN ({$placeholders})";
            $params = array_merge($params, array_map('absint', $selected_ids));
        }

        $sites = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);

        $items = [];
        $summary = [
            'total' => 0,
            'healthy' => 0,
            'warning' => 0,
            'critical' => 0,
        ];

        foreach ($sites as $site) {
            $summary['total']++;

            $health = json_decode($site['last_health'] ?? '{}', true);
            $score = $health['score'] ?? 0;
            $status = $health['status'] ?? 'unknown';

            // Count by status
            if ($status === 'healthy' || $score >= 80) {
                $summary['healthy']++;
            } elseif ($status === 'warning' || $score >= 60) {
                $summary['warning']++;
            } else {
                $summary['critical']++;
            }

            // Build issues list
            $issues = [];
            if (!empty($health['checks'])) {
                foreach ($health['checks'] as $check_name => $check_data) {
                    if (isset($check_data['status']) && $check_data['status'] !== 'ok') {
                        $issues[] = $this->format_site_issue($check_name, $check_data);
                    }
                }
            }

            $items[] = [
                'id' => (int) $site['id'],
                'name' => $site['site_name'] ?: parse_url($site['site_url'], PHP_URL_HOST),
                'url' => $site['site_url'],
                'score' => $score,
                'grade' => $this->score_to_grade($score),
                'status' => $status,
                'issues' => $issues,
                'last_checked' => $site['last_checked'],
            ];
        }

        // Sort by score ascending (worst first)
        usort($items, fn($a, $b) => $a['score'] - $b['score']);

        return [
            'summary' => $summary,
            'items' => $items,
        ];
    }

    /**
     * Aggregate health data from Plesk servers
     *
     * @param int $user_id User ID
     * @param string $period_start Period start date
     * @param string $period_end Period end date
     * @param array $selected_ids Optional array of server IDs to include (empty = all)
     */
    private function aggregate_server_health(int $user_id, string $period_start, string $period_end, array $selected_ids = []): array {
        global $wpdb;

        $servers_table = Peanut_Database::monitor_servers_table();

        // Build query with optional ID filter
        $query = "SELECT id, server_name, server_host, status, last_health, last_check
             FROM {$servers_table}
             WHERE user_id = %d AND status = 'active'";
        $params = [$user_id];

        // Filter by selected IDs if provided
        if (!empty($selected_ids)) {
            $placeholders = implode(',', array_fill(0, count($selected_ids), '%d'));
            $query .= " AND id IN ({$placeholders})";
            $params = array_merge($params, array_map('absint', $selected_ids));
        }

        $servers = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);

        $items = [];
        $summary = [
            'total' => 0,
            'healthy' => 0,
            'warning' => 0,
            'critical' => 0,
        ];

        foreach ($servers as $server) {
            $summary['total']++;

            $health = json_decode($server['last_health'] ?? '{}', true);
            $score = $health['score'] ?? 0;
            $status = $health['status'] ?? 'unknown';

            // Count by status
            if ($status === 'healthy' || $score >= 80) {
                $summary['healthy']++;
            } elseif ($status === 'warning' || $score >= 60) {
                $summary['warning']++;
            } else {
                $summary['critical']++;
            }

            // Build issues list
            $issues = [];
            if (!empty($health['checks'])) {
                foreach ($health['checks'] as $check_name => $check_data) {
                    if (isset($check_data['status']) && $check_data['status'] !== 'ok') {
                        $issues[] = $this->format_server_issue($check_name, $check_data);
                    }
                }
            }

            $items[] = [
                'id' => (int) $server['id'],
                'name' => $server['server_name'] ?: $server['server_host'],
                'host' => $server['server_host'],
                'score' => $score,
                'grade' => $this->score_to_grade($score),
                'status' => $status,
                'issues' => $issues,
                'last_checked' => $server['last_check'],
            ];
        }

        // Sort by score ascending (worst first)
        usort($items, fn($a, $b) => $a['score'] - $b['score']);

        return [
            'summary' => $summary,
            'items' => $items,
        ];
    }

    /**
     * Calculate overall grade from sites and servers
     */
    private function calculate_overall_grade(array $sites_data, array $servers_data): array {
        $scores = [];

        // Collect all scores
        foreach ($sites_data['items'] ?? [] as $site) {
            $scores[] = $site['score'];
        }
        foreach ($servers_data['items'] ?? [] as $server) {
            $scores[] = $server['score'];
        }

        if (empty($scores)) {
            return [
                'score' => 0,
                'grade' => 'N/A',
                'status' => 'unknown',
            ];
        }

        // Calculate weighted average (sites and servers equally weighted)
        $average_score = round(array_sum($scores) / count($scores));

        // Determine status
        $status = 'healthy';
        if ($average_score < 60) {
            $status = 'critical';
        } elseif ($average_score < 80) {
            $status = 'warning';
        }

        return [
            'score' => $average_score,
            'grade' => $this->score_to_grade($average_score),
            'status' => $status,
        ];
    }

    /**
     * Calculate trends by comparing to previous report
     */
    private function calculate_trends(int $user_id, int $current_score): array {
        global $wpdb;

        $reports_table = Peanut_Database::health_reports_table();

        // Get the most recent previous report
        $previous = $wpdb->get_row($wpdb->prepare(
            "SELECT overall_score FROM {$reports_table}
             WHERE user_id = %d
             ORDER BY created_at DESC LIMIT 1",
            $user_id
        ), ARRAY_A);

        $previous_score = $previous ? (int) $previous['overall_score'] : null;
        $change = $previous_score !== null ? $current_score - $previous_score : null;

        return [
            'current' => $current_score,
            'previous' => $previous_score,
            'change' => $change,
            'direction' => $change === null ? null : ($change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')),
        ];
    }

    /**
     * Convert score to letter grade
     */
    private function score_to_grade(int $score): string {
        foreach (self::GRADE_THRESHOLDS as $grade => $threshold) {
            if ($score >= $threshold) {
                return $grade;
            }
        }
        return 'F';
    }

    /**
     * Format a site issue for display
     */
    private function format_site_issue(string $check_name, array $check_data): string {
        $status = $check_data['status'] ?? 'unknown';
        $value = $check_data['value'] ?? null;

        switch ($check_name) {
            case 'wp_version':
                return 'WordPress update available';
            case 'php_version':
                return sprintf('PHP %s (outdated)', $value);
            case 'ssl':
                if ($value === false) {
                    return 'No SSL certificate';
                }
                return 'SSL certificate issue';
            case 'plugins':
                $count = $check_data['outdated_count'] ?? 0;
                return sprintf('%d plugin%s need updates', $count, $count !== 1 ? 's' : '');
            case 'themes':
                $count = $check_data['outdated_count'] ?? 0;
                return sprintf('%d theme%s need updates', $count, $count !== 1 ? 's' : '');
            case 'uptime':
                return 'Uptime issue detected';
            default:
                return ucfirst(str_replace('_', ' ', $check_name)) . ' issue';
        }
    }

    /**
     * Format a server issue for display
     */
    private function format_server_issue(string $check_name, array $check_data): string {
        $status = $check_data['status'] ?? 'unknown';
        $value = $check_data['value'] ?? null;

        switch ($check_name) {
            case 'cpu_usage':
                return sprintf('CPU at %d%%', $value);
            case 'ram_usage':
                return sprintf('RAM at %d%%', $value);
            case 'disk_usage':
                return sprintf('Disk at %d%%', $value);
            case 'load_average':
                return sprintf('Load average: %.2f', $value);
            case 'services':
                $stopped = $check_data['stopped'] ?? [];
                return sprintf('%d service%s stopped', count($stopped), count($stopped) !== 1 ? 's' : '');
            case 'ssl_certs':
                $issues = $check_data['issue_count'] ?? 0;
                return sprintf('%d SSL certificate%s expiring', $issues, $issues !== 1 ? 's' : '');
            case 'plesk_updates':
                return 'Plesk updates available';
            default:
                return ucfirst(str_replace('_', ' ', $check_name)) . ' issue';
        }
    }

    /**
     * Save report to database
     */
    private function save_report(int $user_id, array $report_data, string $frequency): int|false {
        global $wpdb;

        $table = Peanut_Database::health_reports_table();

        $result = $wpdb->insert($table, [
            'user_id' => $user_id,
            'report_type' => $frequency,
            'period_start' => $report_data['period']['start'],
            'period_end' => $report_data['period']['end'],
            'overall_grade' => $report_data['overall']['grade'],
            'overall_score' => $report_data['overall']['score'],
            'sites_data' => json_encode($report_data['sites']),
            'servers_data' => json_encode($report_data['servers']),
            'recommendations' => json_encode($report_data['recommendations']),
            'created_at' => current_time('mysql'),
        ]);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get report history for a user
     */
    public function get_history(int $user_id, int $limit = 10): array {
        global $wpdb;

        $table = Peanut_Database::health_reports_table();

        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT id, report_type, period_start, period_end, overall_grade, overall_score, sent_at, created_at
             FROM {$table}
             WHERE user_id = %d
             ORDER BY created_at DESC
             LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);

        return array_map(function($report) {
            return [
                'id' => (int) $report['id'],
                'type' => $report['report_type'],
                'period' => [
                    'start' => $report['period_start'],
                    'end' => $report['period_end'],
                ],
                'grade' => $report['overall_grade'],
                'score' => (int) $report['overall_score'],
                'sent_at' => $report['sent_at'],
                'created_at' => $report['created_at'],
            ];
        }, $reports);
    }

    /**
     * Get a specific report by ID
     */
    public function get_report(int $report_id, int $user_id): ?array {
        global $wpdb;

        $table = Peanut_Database::health_reports_table();

        $report = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d AND user_id = %d",
            $report_id,
            $user_id
        ), ARRAY_A);

        if (!$report) {
            return null;
        }

        return [
            'id' => (int) $report['id'],
            'type' => $report['report_type'],
            'period' => [
                'start' => $report['period_start'],
                'end' => $report['period_end'],
            ],
            'overall' => [
                'grade' => $report['overall_grade'],
                'score' => (int) $report['overall_score'],
            ],
            'sites' => json_decode($report['sites_data'], true) ?: [],
            'servers' => json_decode($report['servers_data'], true) ?: [],
            'recommendations' => json_decode($report['recommendations'], true) ?: [],
            'sent_at' => $report['sent_at'],
            'created_at' => $report['created_at'],
        ];
    }

    /**
     * Get the latest report for a user
     */
    public function get_latest(int $user_id): ?array {
        global $wpdb;

        $table = Peanut_Database::health_reports_table();

        $report_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
            $user_id
        ));

        if (!$report_id) {
            return null;
        }

        return $this->get_report((int) $report_id, $user_id);
    }

    /**
     * Generate preview (current state without saving)
     */
    public function preview(int $user_id, array $settings): array {
        // Use yesterday as period end for preview
        $period_end = new DateTime('yesterday');
        $period_start = (clone $period_end)->modify('-6 days');

        // Get selected IDs (empty array means all)
        $selected_site_ids = $settings['selected_site_ids'] ?? [];
        $selected_server_ids = $settings['selected_server_ids'] ?? [];

        // Get sites data if included
        $sites_data = [];
        if ($settings['include_sites'] ?? true) {
            $sites_data = $this->aggregate_site_health($user_id, $period_start->format('Y-m-d'), $period_end->format('Y-m-d'), $selected_site_ids);
        }

        // Get servers data if included
        $servers_data = [];
        if ($settings['include_servers'] ?? true) {
            $servers_data = $this->aggregate_server_health($user_id, $period_start->format('Y-m-d'), $period_end->format('Y-m-d'), $selected_server_ids);
        }

        // Calculate overall score
        $overall = $this->calculate_overall_grade($sites_data, $servers_data);

        // Generate recommendations if included
        $recommendations = [];
        if ($settings['include_recommendations'] ?? true) {
            $recommendations_engine = new Health_Recommendations();
            $recommendations = $recommendations_engine->generate($sites_data, $servers_data);
        }

        // Get trends
        $trends = $this->calculate_trends($user_id, $overall['score']);

        return [
            'period' => [
                'start' => $period_start->format('Y-m-d'),
                'end' => $period_end->format('Y-m-d'),
                'type' => 'preview',
            ],
            'overall' => $overall,
            'sites' => $sites_data,
            'servers' => $servers_data,
            'recommendations' => $recommendations,
            'trends' => $trends,
            'generated_at' => current_time('mysql'),
        ];
    }

    /**
     * Render report as HTML email
     */
    public function render_email_html(array $report): string {
        $grade = $report['overall']['grade'] ?? 'N/A';
        $score = $report['overall']['score'] ?? 0;
        $trend_change = $report['trends']['change'] ?? null;
        $trend_text = $trend_change !== null
            ? ($trend_change > 0 ? "+{$trend_change}" : (string) $trend_change) . ' from last report'
            : 'First report';

        $brand_name = get_option('peanut_white_label_name', 'Marketing Suite');
        $primary_color = get_option('peanut_white_label_color', '#2563eb');

        // Grade colors
        $grade_colors = [
            'A' => '#16a34a',
            'B' => '#2563eb',
            'C' => '#eab308',
            'D' => '#f97316',
            'F' => '#dc2626',
        ];
        $grade_color = $grade_colors[$grade] ?? '#64748b';

        // Start building HTML
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Report</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background-color: #f1f5f9;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <!-- Header -->
        <div style="background-color: ' . esc_attr($primary_color) . '; color: white; padding: 20px; border-radius: 12px 12px 0 0; text-align: center;">
            <h1 style="margin: 0; font-size: 24px;">' . esc_html($brand_name) . '</h1>
            <p style="margin: 8px 0 0; opacity: 0.9;">' . ucfirst($report['period']['type'] ?? 'Weekly') . ' Health Report</p>
        </div>

        <!-- Main Content -->
        <div style="background-color: white; padding: 30px; border-radius: 0 0 12px 12px;">
            <!-- Overall Grade -->
            <div style="text-align: center; padding: 20px; border: 2px solid #e2e8f0; border-radius: 12px; margin-bottom: 30px;">
                <div style="display: inline-block; width: 80px; height: 80px; line-height: 80px; border-radius: 50%; background-color: ' . esc_attr($grade_color) . '20; color: ' . esc_attr($grade_color) . '; font-size: 40px; font-weight: bold;">
                    ' . esc_html($grade) . '
                </div>
                <p style="margin: 10px 0 0; font-size: 24px; font-weight: bold; color: #0f172a;">' . esc_html($score) . '/100</p>
                <p style="margin: 5px 0 0; color: #64748b;">' . esc_html($trend_text) . '</p>
            </div>';

        // Summary section
        $sites_summary = $report['sites']['summary'] ?? [];
        $servers_summary = $report['servers']['summary'] ?? [];

        if ($sites_summary || $servers_summary) {
            $html .= '<div style="margin-bottom: 30px;">
                <h2 style="margin: 0 0 15px; font-size: 18px; color: #0f172a;">Summary</h2>
                <div style="background-color: #f8fafc; padding: 15px; border-radius: 8px;">';

            if ($sites_summary) {
                $html .= '<p style="margin: 0 0 8px; color: #475569;">
                    <strong>Sites:</strong> ' . esc_html($sites_summary['total']) . ' total &bull;
                    <span style="color: #16a34a;">' . esc_html($sites_summary['healthy']) . ' healthy</span> &bull;
                    <span style="color: #eab308;">' . esc_html($sites_summary['warning']) . ' warning</span> &bull;
                    <span style="color: #dc2626;">' . esc_html($sites_summary['critical']) . ' critical</span>
                </p>';
            }

            if ($servers_summary) {
                $html .= '<p style="margin: 0; color: #475569;">
                    <strong>Servers:</strong> ' . esc_html($servers_summary['total']) . ' total &bull;
                    <span style="color: #16a34a;">' . esc_html($servers_summary['healthy']) . ' healthy</span> &bull;
                    <span style="color: #eab308;">' . esc_html($servers_summary['warning']) . ' warning</span> &bull;
                    <span style="color: #dc2626;">' . esc_html($servers_summary['critical']) . ' critical</span>
                </p>';
            }

            $html .= '</div></div>';
        }

        // Recommendations
        $recommendations = $report['recommendations'] ?? [];
        if ($recommendations) {
            $html .= '<div style="margin-bottom: 30px;">
                <h2 style="margin: 0 0 15px; font-size: 18px; color: #0f172a;">Top Issues to Address</h2>';

            $priority_colors = [
                'critical' => '#dc2626',
                'high' => '#f97316',
                'medium' => '#eab308',
                'low' => '#64748b',
            ];

            $count = 0;
            foreach ($recommendations as $rec) {
                if ($count >= 5) break;
                $priority = $rec['priority'] ?? 'medium';
                $color = $priority_colors[$priority] ?? '#64748b';

                $html .= '<div style="padding: 12px; border-left: 4px solid ' . esc_attr($color) . '; background-color: #f8fafc; margin-bottom: 8px; border-radius: 0 8px 8px 0;">
                    <p style="margin: 0; color: #0f172a;">' . esc_html($rec['message']) . '</p>
                </div>';
                $count++;
            }

            $html .= '</div>';
        }

        // Sites list (show worst 5)
        $sites_items = $report['sites']['items'] ?? [];
        if ($sites_items) {
            $html .= '<div style="margin-bottom: 30px;">
                <h2 style="margin: 0 0 15px; font-size: 18px; color: #0f172a;">Site Health</h2>
                <table style="width: 100%; border-collapse: collapse;">';

            $count = 0;
            foreach ($sites_items as $site) {
                if ($count >= 5) break;
                $site_grade_color = $grade_colors[$site['grade']] ?? '#64748b';
                $issues_text = !empty($site['issues']) ? implode(', ', array_slice($site['issues'], 0, 2)) : 'All good';

                $html .= '<tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 8px;">
                        <p style="margin: 0; font-weight: 500; color: #0f172a;">' . esc_html($site['name']) . '</p>
                    </td>
                    <td style="padding: 12px 8px; text-align: center;">
                        <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; background-color: ' . esc_attr($site_grade_color) . '20; color: ' . esc_attr($site_grade_color) . '; font-weight: bold;">
                            ' . esc_html($site['grade']) . ' (' . esc_html($site['score']) . ')
                        </span>
                    </td>
                    <td style="padding: 12px 8px; color: #64748b; font-size: 14px;">' . esc_html($issues_text) . '</td>
                </tr>';
                $count++;
            }

            $html .= '</table></div>';
        }

        // Servers list (show worst 5)
        $servers_items = $report['servers']['items'] ?? [];
        if ($servers_items) {
            $html .= '<div style="margin-bottom: 30px;">
                <h2 style="margin: 0 0 15px; font-size: 18px; color: #0f172a;">Server Health</h2>
                <table style="width: 100%; border-collapse: collapse;">';

            $count = 0;
            foreach ($servers_items as $server) {
                if ($count >= 5) break;
                $server_grade_color = $grade_colors[$server['grade']] ?? '#64748b';
                $issues_text = !empty($server['issues']) ? implode(', ', array_slice($server['issues'], 0, 2)) : 'All good';

                $html .= '<tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 8px;">
                        <p style="margin: 0; font-weight: 500; color: #0f172a;">' . esc_html($server['name']) . '</p>
                    </td>
                    <td style="padding: 12px 8px; text-align: center;">
                        <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; background-color: ' . esc_attr($server_grade_color) . '20; color: ' . esc_attr($server_grade_color) . '; font-weight: bold;">
                            ' . esc_html($server['grade']) . ' (' . esc_html($server['score']) . ')
                        </span>
                    </td>
                    <td style="padding: 12px 8px; color: #64748b; font-size: 14px;">' . esc_html($issues_text) . '</td>
                </tr>';
                $count++;
            }

            $html .= '</table></div>';
        }

        // Footer
        $html .= '<div style="text-align: center; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <a href="' . esc_url(admin_url('admin.php?page=peanut-suite#/health-reports')) . '"
                   style="display: inline-block; padding: 12px 24px; background-color: ' . esc_attr($primary_color) . '; color: white; text-decoration: none; border-radius: 8px; font-weight: 500;">
                    View Full Report
                </a>
                <p style="margin: 20px 0 0; color: #94a3b8; font-size: 12px;">
                    ' . esc_html($report['period']['start']) . ' to ' . esc_html($report['period']['end']) . '
                </p>
            </div>
        </div>
    </div>
</body>
</html>';

        return $html;
    }
}

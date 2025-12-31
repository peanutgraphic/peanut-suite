<?php
/**
 * Health Recommendations Engine
 *
 * Generates actionable recommendations based on site and server health data.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Health_Recommendations {

    /**
     * Priority levels
     */
    private const PRIORITY_CRITICAL = 'critical';
    private const PRIORITY_HIGH = 'high';
    private const PRIORITY_MEDIUM = 'medium';
    private const PRIORITY_LOW = 'low';

    /**
     * Generate recommendations from health data
     */
    public function generate(array $sites_data, array $servers_data): array {
        $recommendations = [];

        // Process site-related recommendations
        foreach ($sites_data['items'] ?? [] as $site) {
            $site_recs = $this->analyze_site($site);
            $recommendations = array_merge($recommendations, $site_recs);
        }

        // Process server-related recommendations
        foreach ($servers_data['items'] ?? [] as $server) {
            $server_recs = $this->analyze_server($server);
            $recommendations = array_merge($recommendations, $server_recs);
        }

        // Sort by priority
        usort($recommendations, function($a, $b) {
            $priorities = [
                self::PRIORITY_CRITICAL => 0,
                self::PRIORITY_HIGH => 1,
                self::PRIORITY_MEDIUM => 2,
                self::PRIORITY_LOW => 3,
            ];
            return ($priorities[$a['priority']] ?? 4) - ($priorities[$b['priority']] ?? 4);
        });

        // Deduplicate similar recommendations
        $recommendations = $this->deduplicate($recommendations);

        return $recommendations;
    }

    /**
     * Analyze a site and generate recommendations
     */
    private function analyze_site(array $site): array {
        $recommendations = [];
        $name = $site['name'];
        $issues = $site['issues'] ?? [];

        // Low score recommendation
        if (($site['score'] ?? 100) < 60) {
            $recommendations[] = [
                'priority' => self::PRIORITY_HIGH,
                'category' => 'site',
                'site' => $name,
                'message' => sprintf('%s has critical health issues (score: %d/100)', $name, $site['score']),
                'action' => 'Review all health checks and address critical issues immediately.',
            ];
        }

        // Check individual issues
        foreach ($issues as $issue) {
            $issue_lower = strtolower($issue);

            // WordPress update
            if (strpos($issue_lower, 'wordpress update') !== false) {
                $recommendations[] = [
                    'priority' => self::PRIORITY_HIGH,
                    'category' => 'wordpress',
                    'site' => $name,
                    'message' => sprintf('Update WordPress core on %s', $name),
                    'action' => 'Log into the WordPress admin and apply the available update.',
                ];
            }

            // SSL issues
            if (strpos($issue_lower, 'ssl') !== false || strpos($issue_lower, 'certificate') !== false) {
                $recommendations[] = [
                    'priority' => self::PRIORITY_CRITICAL,
                    'category' => 'ssl',
                    'site' => $name,
                    'message' => sprintf('SSL certificate issue on %s', $name),
                    'action' => 'Check SSL certificate status and renew if needed.',
                ];
            }

            // Plugin updates
            if (strpos($issue_lower, 'plugin') !== false && strpos($issue_lower, 'update') !== false) {
                if (preg_match('/(\d+)\s*plugin/', $issue_lower, $matches)) {
                    $count = (int) $matches[1];
                    $priority = $count >= 5 ? self::PRIORITY_HIGH : self::PRIORITY_MEDIUM;
                    $recommendations[] = [
                        'priority' => $priority,
                        'category' => 'plugins',
                        'site' => $name,
                        'message' => sprintf('Update %d plugins on %s', $count, $name),
                        'action' => 'Review and update plugins to their latest versions.',
                    ];
                }
            }

            // Theme updates
            if (strpos($issue_lower, 'theme') !== false && strpos($issue_lower, 'update') !== false) {
                $recommendations[] = [
                    'priority' => self::PRIORITY_MEDIUM,
                    'category' => 'themes',
                    'site' => $name,
                    'message' => sprintf('Update themes on %s', $name),
                    'action' => 'Review and update themes to their latest versions.',
                ];
            }

            // PHP version
            if (strpos($issue_lower, 'php') !== false && strpos($issue_lower, 'outdated') !== false) {
                $recommendations[] = [
                    'priority' => self::PRIORITY_MEDIUM,
                    'category' => 'php',
                    'site' => $name,
                    'message' => sprintf('PHP version is outdated on %s', $name),
                    'action' => 'Contact your hosting provider to upgrade PHP to a supported version.',
                ];
            }

            // Uptime issues
            if (strpos($issue_lower, 'uptime') !== false) {
                $recommendations[] = [
                    'priority' => self::PRIORITY_CRITICAL,
                    'category' => 'uptime',
                    'site' => $name,
                    'message' => sprintf('Uptime issues detected on %s', $name),
                    'action' => 'Check server logs and hosting provider status.',
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Analyze a server and generate recommendations
     */
    private function analyze_server(array $server): array {
        $recommendations = [];
        $name = $server['name'];
        $issues = $server['issues'] ?? [];

        // Low score recommendation
        if (($server['score'] ?? 100) < 60) {
            $recommendations[] = [
                'priority' => self::PRIORITY_HIGH,
                'category' => 'server',
                'server' => $name,
                'message' => sprintf('%s has critical health issues (score: %d/100)', $name, $server['score']),
                'action' => 'Review all server metrics and address critical issues immediately.',
            ];
        }

        // Check individual issues
        foreach ($issues as $issue) {
            $issue_lower = strtolower($issue);

            // CPU usage
            if (strpos($issue_lower, 'cpu') !== false) {
                if (preg_match('/(\d+)%/', $issue_lower, $matches)) {
                    $usage = (int) $matches[1];
                    $priority = $usage >= 90 ? self::PRIORITY_CRITICAL : self::PRIORITY_HIGH;
                    $recommendations[] = [
                        'priority' => $priority,
                        'category' => 'cpu',
                        'server' => $name,
                        'message' => sprintf('CPU usage at %d%% on %s', $usage, $name),
                        'action' => 'Identify resource-intensive processes and optimize or scale resources.',
                    ];
                }
            }

            // RAM usage
            if (strpos($issue_lower, 'ram') !== false) {
                if (preg_match('/(\d+)%/', $issue_lower, $matches)) {
                    $usage = (int) $matches[1];
                    $priority = $usage >= 90 ? self::PRIORITY_CRITICAL : self::PRIORITY_HIGH;
                    $recommendations[] = [
                        'priority' => $priority,
                        'category' => 'ram',
                        'server' => $name,
                        'message' => sprintf('RAM usage at %d%% on %s', $usage, $name),
                        'action' => 'Review memory-intensive processes and consider upgrading RAM.',
                    ];
                }
            }

            // Disk usage
            if (strpos($issue_lower, 'disk') !== false) {
                if (preg_match('/(\d+)%/', $issue_lower, $matches)) {
                    $usage = (int) $matches[1];
                    $priority = $usage >= 90 ? self::PRIORITY_CRITICAL : self::PRIORITY_HIGH;
                    $recommendations[] = [
                        'priority' => $priority,
                        'category' => 'disk',
                        'server' => $name,
                        'message' => sprintf('Disk usage at %d%% on %s', $usage, $name),
                        'action' => 'Clean up old files, logs, and backups, or expand storage.',
                    ];
                }
            }

            // Services stopped
            if (strpos($issue_lower, 'service') !== false && strpos($issue_lower, 'stopped') !== false) {
                if (preg_match('/(\d+)\s*service/', $issue_lower, $matches)) {
                    $count = (int) $matches[1];
                    $recommendations[] = [
                        'priority' => self::PRIORITY_CRITICAL,
                        'category' => 'services',
                        'server' => $name,
                        'message' => sprintf('%d critical service%s stopped on %s', $count, $count !== 1 ? 's' : '', $name),
                        'action' => 'Check service status in Plesk and restart stopped services.',
                    ];
                }
            }

            // SSL certificates expiring
            if (strpos($issue_lower, 'ssl') !== false || strpos($issue_lower, 'certificate') !== false) {
                if (preg_match('/(\d+)/', $issue_lower, $matches)) {
                    $count = (int) $matches[1];
                    $recommendations[] = [
                        'priority' => self::PRIORITY_HIGH,
                        'category' => 'ssl',
                        'server' => $name,
                        'message' => sprintf('%d SSL certificate%s expiring soon on %s', $count, $count !== 1 ? 's' : '', $name),
                        'action' => 'Renew SSL certificates before they expire.',
                    ];
                }
            }

            // Load average
            if (strpos($issue_lower, 'load') !== false) {
                $recommendations[] = [
                    'priority' => self::PRIORITY_MEDIUM,
                    'category' => 'load',
                    'server' => $name,
                    'message' => sprintf('High load average on %s', $name),
                    'action' => 'Investigate processes causing high load and optimize or scale.',
                ];
            }

            // Plesk updates
            if (strpos($issue_lower, 'plesk') !== false && strpos($issue_lower, 'update') !== false) {
                $recommendations[] = [
                    'priority' => self::PRIORITY_MEDIUM,
                    'category' => 'plesk',
                    'server' => $name,
                    'message' => sprintf('Plesk updates available on %s', $name),
                    'action' => 'Apply Plesk updates during a maintenance window.',
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Deduplicate similar recommendations
     */
    private function deduplicate(array $recommendations): array {
        $seen = [];
        $unique = [];

        foreach ($recommendations as $rec) {
            // Create a key based on category and the core message
            $key = $rec['category'] . '-' . ($rec['site'] ?? $rec['server'] ?? 'general');

            // For some categories, we want to combine counts instead of duplicating
            if (in_array($rec['category'], ['plugins', 'themes'])) {
                if (isset($seen[$key])) {
                    continue; // Skip duplicates
                }
            }

            $seen[$key] = true;
            $unique[] = $rec;
        }

        return $unique;
    }

    /**
     * Get recommendations for a specific category
     */
    public function filter_by_category(array $recommendations, string $category): array {
        return array_filter($recommendations, fn($rec) => $rec['category'] === $category);
    }

    /**
     * Get recommendations for a specific priority
     */
    public function filter_by_priority(array $recommendations, string $priority): array {
        return array_filter($recommendations, fn($rec) => $rec['priority'] === $priority);
    }

    /**
     * Count recommendations by priority
     */
    public function count_by_priority(array $recommendations): array {
        $counts = [
            self::PRIORITY_CRITICAL => 0,
            self::PRIORITY_HIGH => 0,
            self::PRIORITY_MEDIUM => 0,
            self::PRIORITY_LOW => 0,
        ];

        foreach ($recommendations as $rec) {
            $priority = $rec['priority'] ?? self::PRIORITY_LOW;
            if (isset($counts[$priority])) {
                $counts[$priority]++;
            }
        }

        return $counts;
    }
}

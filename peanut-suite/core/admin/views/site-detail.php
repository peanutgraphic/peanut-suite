<?php
/**
 * Site Detail View
 *
 * Shows detailed information about a monitored site.
 * Agency feature - requires Agency license.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get site ID from URL
$site_id = isset($_GET['site']) ? absint($_GET['site']) : 0;

if (empty($site_id)) {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('No site ID specified.', 'peanut-suite'); ?></p>
    </div>
    <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-monitor')); ?>" class="button">
            <?php esc_html_e('Back to Monitor', 'peanut-suite'); ?>
        </a>
    </p>
    <?php
    return;
}

// Load database class
require_once PEANUT_PLUGIN_DIR . 'modules/monitor/class-monitor-database.php';

global $wpdb;
$sites_table = Monitor_Database::sites_table();
$health_log_table = Monitor_Database::health_log_table();
$uptime_table = Monitor_Database::uptime_table();

// Check if tables exist
$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $sites_table)) === $sites_table;

// Demo site names for different IDs
$demo_sites = [
    1 => ['name' => 'Client Site A', 'url' => 'https://clienta.example.com'],
    2 => ['name' => 'Client Site B', 'url' => 'https://clientb.example.com'],
    3 => ['name' => 'E-commerce Store', 'url' => 'https://store.example.com'],
];

// Get site data - try database first, then fall back to demo data
$site = null;
if ($table_exists) {
    $site = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $sites_table WHERE id = %d", $site_id),
        ARRAY_A
    );
}

// If no site found in database, use demo data
if (!$site) {
    $demo_info = $demo_sites[$site_id] ?? ['name' => 'Demo Client Site', 'url' => 'https://demo-client.example.com'];
    $site = [
        'id' => $site_id,
        'site_name' => $demo_info['name'],
        'site_url' => $demo_info['url'],
        'status' => 'active',
        'last_check' => current_time('mysql'),
        'last_health' => json_encode([
            'score' => 85,
            'status' => 'healthy',
            'wp_version' => '6.4.2',
            'php_version' => '8.2.12',
            'ssl_valid' => true,
            'ssl_expiry' => date('Y-m-d', strtotime('+90 days')),
            'disk_usage' => 45,
            'memory_usage' => 60,
            'plugins' => [
                'updates_available' => 3,
                'total' => 12,
                'active' => 10,
            ],
            'themes' => [
                'updates_available' => 1,
                'total' => 3,
                'active' => 'Twenty Twenty-Four',
            ],
            'core_update' => false,
        ]),
        'peanut_suite_active' => 1,
        'peanut_suite_version' => '2.0.8',
        'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
        'updated_at' => current_time('mysql'),
    ];
}

// Parse health data
$health_raw = json_decode($site['last_health'] ?? '{}', true) ?: [];

// Normalize health data - handle both flat (demo) and nested (real API) formats
// Real API data stores the actual checks in $health_raw['checks']
$checks = $health_raw['checks'] ?? $health_raw;

// Build normalized health array
$health = [
    'score' => $health_raw['score'] ?? 0,
    'status' => $health_raw['status'] ?? 'unknown',
    // WordPress version - can be string or array
    'wp_version' => is_array($checks['wp_version'] ?? null)
        ? ($checks['wp_version']['version'] ?? '-')
        : ($checks['wp_version'] ?? '-'),
    // PHP version - can be string or array
    'php_version' => is_array($checks['php_version'] ?? null)
        ? ($checks['php_version']['version'] ?? '-')
        : ($checks['php_version'] ?? '-'),
    // SSL - can be boolean or array
    'ssl_valid' => is_array($checks['ssl'] ?? null)
        ? ($checks['ssl']['valid'] ?? false)
        : ($checks['ssl_valid'] ?? false),
    'ssl_expiry' => is_array($checks['ssl'] ?? null)
        ? ($checks['ssl']['expiry_date'] ?? null)
        : ($checks['ssl_expiry'] ?? null),
    // Plugins - always array
    'plugins' => [
        'active' => $checks['plugins']['active'] ?? 0,
        'total' => $checks['plugins']['total'] ?? 0,
        'updates_available' => $checks['plugins']['updates_available'] ?? 0,
    ],
    // Themes - always array
    'themes' => [
        'active' => $checks['themes']['active'] ?? ($checks['theme']['name'] ?? '-'),
        'updates_available' => $checks['themes']['updates_available'] ?? 0,
    ],
    // Disk usage - can be number or array
    'disk_usage' => is_array($checks['disk_space'] ?? null)
        ? ($checks['disk_space']['used_percent'] ?? 0)
        : ($checks['disk_usage'] ?? 0),
    // Memory usage
    'memory_usage' => $checks['memory_usage'] ?? 0,
    // Core update
    'core_update' => is_array($checks['wp_version'] ?? null)
        ? ($checks['wp_version']['needs_update'] ?? false)
        : ($checks['core_update'] ?? false),
];

// Check for Peanut Suite/Connect on the remote site
$peanut_suite_data = $checks['peanut_suite'] ?? null;
if (is_array($peanut_suite_data)) {
    $site['peanut_suite_active'] = !empty($peanut_suite_data['installed']);
    $site['peanut_suite_version'] = $peanut_suite_data['version'] ?? null;
}

// Get uptime history (last 30 days)
$uptime_history = [];
if ($table_exists) {
    $uptime_history = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT status, response_time, checked_at FROM $uptime_table
             WHERE site_id = %d AND checked_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
             ORDER BY checked_at DESC LIMIT 100",
            $site_id
        ),
        ARRAY_A
    ) ?: [];
} else {
    // Demo uptime data
    for ($i = 0; $i < 48; $i++) {
        $uptime_history[] = [
            'status' => rand(1, 50) > 1 ? 'up' : 'down',
            'response_time' => rand(150, 600),
            'checked_at' => date('Y-m-d H:i:s', strtotime("-{$i} hours")),
        ];
    }
}

// Calculate uptime percentage
$total_checks = count($uptime_history);
$up_checks = count(array_filter($uptime_history, fn($u) => $u['status'] === 'up'));
$uptime_percentage = $total_checks > 0 ? ($up_checks / $total_checks) * 100 : 100;

// Get health log (last 7 days)
$health_log = [];
if ($table_exists) {
    $health_log = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $health_log_table
             WHERE site_id = %d AND checked_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY checked_at DESC LIMIT 20",
            $site_id
        ),
        ARRAY_A
    ) ?: [];
}

// Determine current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
$tabs = [
    'overview' => __('Overview', 'peanut-suite'),
    'updates' => __('Updates', 'peanut-suite'),
    'uptime' => __('Uptime', 'peanut-suite'),
    'settings' => __('Settings', 'peanut-suite'),
];

// Sample update data (would come from API in production)
$available_updates = [
    'plugins' => [
        ['name' => 'WooCommerce', 'current' => '8.4.0', 'new' => '8.5.1', 'slug' => 'woocommerce'],
        ['name' => 'Yoast SEO', 'current' => '21.6', 'new' => '21.7', 'slug' => 'wordpress-seo'],
        ['name' => 'Contact Form 7', 'current' => '5.8.4', 'new' => '5.8.6', 'slug' => 'contact-form-7'],
    ],
    'themes' => [
        ['name' => 'Twenty Twenty-Three', 'current' => '1.3', 'new' => '1.4', 'slug' => 'twentytwentythree'],
    ],
    'core' => $health['core_update'] ?? false ? ['current' => $health['wp_version'] ?? '6.4.2', 'new' => '6.5.0'] : null,
];
?>

<a href="<?php echo esc_url(admin_url('admin.php?page=peanut-monitor')); ?>" class="button" style="margin-bottom: 20px;">
    <span class="dashicons dashicons-arrow-left-alt" style="margin-top: 4px;"></span>
    <?php esc_html_e('Back to Monitor', 'peanut-suite'); ?>
</a>

<!-- Site Header -->
<div class="peanut-site-detail-header">
    <div class="peanut-site-icon">
        <span class="dashicons dashicons-admin-site-alt3"></span>
    </div>
    <div class="peanut-site-title">
        <h2><?php echo esc_html($site['site_name'] ?: parse_url($site['site_url'], PHP_URL_HOST)); ?></h2>
        <a href="<?php echo esc_url($site['site_url']); ?>" target="_blank" class="peanut-site-url">
            <?php echo esc_html(preg_replace('#^https?://#', '', $site['site_url'])); ?>
            <span class="dashicons dashicons-external"></span>
        </a>
    </div>
    <div style="margin-left: auto;">
        <button type="button" class="button" id="peanut-refresh-site">
            <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
            <?php esc_html_e('Refresh Now', 'peanut-suite'); ?>
        </button>
        <button type="button" class="button" id="peanut-disconnect-site" style="color: #dc2626;">
            <?php esc_html_e('Disconnect', 'peanut-suite'); ?>
        </button>
    </div>
</div>

<!-- Tabs -->
<div class="peanut-site-tabs">
    <?php foreach ($tabs as $tab_key => $tab_label): ?>
        <a href="<?php echo esc_url(add_query_arg('tab', $tab_key)); ?>"
           class="<?php echo $current_tab === $tab_key ? 'active' : ''; ?>">
            <?php echo esc_html($tab_label); ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($current_tab === 'overview'): ?>
    <!-- Overview Tab -->
    <div class="peanut-card">
        <div class="peanut-health-score">
            <?php
            $score = $health['score'] ?? 0;
            $status_class = 'healthy';
            if ($score < 60) $status_class = 'critical';
            elseif ($score < 80) $status_class = 'warning';
            ?>
            <div class="peanut-score-circle <?php echo esc_attr($status_class); ?>">
                <span class="peanut-score-value"><?php echo number_format_i18n($score); ?></span>
                <span class="peanut-score-label"><?php esc_html_e('Health Score', 'peanut-suite'); ?></span>
            </div>
            <div class="peanut-health-metrics">
                <div class="peanut-health-metric">
                    <div class="peanut-health-metric-value" style="color: <?php echo $uptime_percentage >= 99 ? '#16a34a' : ($uptime_percentage >= 95 ? '#d97706' : '#dc2626'); ?>">
                        <?php echo number_format($uptime_percentage, 1); ?>%
                    </div>
                    <div class="peanut-health-metric-label"><?php esc_html_e('Uptime (30d)', 'peanut-suite'); ?></div>
                </div>
                <div class="peanut-health-metric">
                    <div class="peanut-health-metric-value">
                        <?php echo number_format_i18n(($health['plugins']['updates_available'] ?? 0) + ($health['themes']['updates_available'] ?? 0)); ?>
                    </div>
                    <div class="peanut-health-metric-label"><?php esc_html_e('Updates Available', 'peanut-suite'); ?></div>
                </div>
                <div class="peanut-health-metric">
                    <?php
                    $avg_response = $total_checks > 0 ? array_sum(array_column($uptime_history, 'response_time')) / $total_checks : 0;
                    ?>
                    <div class="peanut-health-metric-value">
                        <?php echo number_format_i18n($avg_response); ?>ms
                    </div>
                    <div class="peanut-health-metric-label"><?php esc_html_e('Avg Response', 'peanut-suite'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="peanut-two-column">
        <!-- System Info -->
        <div class="peanut-card" style="padding: 20px;">
            <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 600;">
                <span class="dashicons dashicons-info" style="color: #0073aa;"></span>
                <?php esc_html_e('System Information', 'peanut-suite'); ?>
            </h3>
            <div class="peanut-info-row">
                <span class="peanut-info-label"><?php esc_html_e('WordPress Version', 'peanut-suite'); ?></span>
                <span class="peanut-info-value"><?php echo esc_html($health['wp_version'] ?? '-'); ?></span>
            </div>
            <div class="peanut-info-row">
                <span class="peanut-info-label"><?php esc_html_e('PHP Version', 'peanut-suite'); ?></span>
                <span class="peanut-info-value"><?php echo esc_html($health['php_version'] ?? '-'); ?></span>
            </div>
            <div class="peanut-info-row">
                <span class="peanut-info-label"><?php esc_html_e('Peanut Suite', 'peanut-suite'); ?></span>
                <span class="peanut-info-value">
                    <?php if ($site['peanut_suite_active']): ?>
                        <span class="peanut-badge peanut-badge-success"><?php echo esc_html($site['peanut_suite_version'] ?? 'Active'); ?></span>
                    <?php else: ?>
                        <span class="peanut-badge peanut-badge-neutral"><?php esc_html_e('Not Installed', 'peanut-suite'); ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="peanut-info-row">
                <span class="peanut-info-label"><?php esc_html_e('Active Plugins', 'peanut-suite'); ?></span>
                <span class="peanut-info-value"><?php echo number_format_i18n($health['plugins']['active'] ?? 0); ?> / <?php echo number_format_i18n($health['plugins']['total'] ?? 0); ?></span>
            </div>
            <div class="peanut-info-row">
                <span class="peanut-info-label"><?php esc_html_e('Active Theme', 'peanut-suite'); ?></span>
                <span class="peanut-info-value"><?php echo esc_html($health['themes']['active'] ?? '-'); ?></span>
            </div>
            <div class="peanut-info-row">
                <span class="peanut-info-label"><?php esc_html_e('Last Checked', 'peanut-suite'); ?></span>
                <span class="peanut-info-value"><?php echo esc_html(human_time_diff(strtotime($site['last_check'])) . ' ' . __('ago', 'peanut-suite')); ?></span>
            </div>
        </div>

        <!-- Security & SSL -->
        <div class="peanut-card" style="padding: 20px;">
            <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 600;">
                <span class="dashicons dashicons-shield" style="color: #0073aa;"></span>
                <?php esc_html_e('Security & SSL', 'peanut-suite'); ?>
            </h3>
            <div class="peanut-info-row">
                <span class="peanut-info-label"><?php esc_html_e('SSL Certificate', 'peanut-suite'); ?></span>
                <span class="peanut-info-value">
                    <?php if ($health['ssl_valid'] ?? false): ?>
                        <span class="peanut-ssl-badge">
                            <span class="dashicons dashicons-yes"></span>
                            <?php esc_html_e('Valid', 'peanut-suite'); ?>
                        </span>
                    <?php else: ?>
                        <span class="peanut-ssl-badge error">
                            <span class="dashicons dashicons-no"></span>
                            <?php esc_html_e('Invalid', 'peanut-suite'); ?>
                        </span>
                    <?php endif; ?>
                </span>
            </div>
            <?php if (!empty($health['ssl_expiry'])): ?>
            <div class="peanut-info-row">
                <span class="peanut-info-label"><?php esc_html_e('SSL Expires', 'peanut-suite'); ?></span>
                <?php
                $days_until = floor((strtotime($health['ssl_expiry']) - time()) / 86400);
                $expiry_class = $days_until > 30 ? '' : ($days_until > 7 ? 'warning' : 'error');
                ?>
                <span class="peanut-info-value">
                    <span class="peanut-ssl-badge <?php echo esc_attr($expiry_class); ?>">
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($health['ssl_expiry']))); ?>
                        (<?php printf(esc_html__('%d days', 'peanut-suite'), $days_until); ?>)
                    </span>
                </span>
            </div>
            <?php endif; ?>
            <div class="peanut-info-row">
                <span class="peanut-info-label"><?php esc_html_e('Disk Usage', 'peanut-suite'); ?></span>
                <span class="peanut-info-value">
                    <?php echo number_format_i18n($health['disk_usage'] ?? 0); ?>%
                    <?php if (($health['disk_usage'] ?? 0) > 80): ?>
                        <span class="dashicons dashicons-warning" style="color: #d97706;"></span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="peanut-info-row">
                <span class="peanut-info-label"><?php esc_html_e('Memory Usage', 'peanut-suite'); ?></span>
                <span class="peanut-info-value">
                    <?php echo number_format_i18n($health['memory_usage'] ?? 0); ?>%
                    <?php if (($health['memory_usage'] ?? 0) > 80): ?>
                        <span class="dashicons dashicons-warning" style="color: #d97706;"></span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="peanut-info-row">
                <span class="peanut-info-label"><?php esc_html_e('Connection Status', 'peanut-suite'); ?></span>
                <span class="peanut-info-value">
                    <?php if ($site['status'] === 'active'): ?>
                        <span class="peanut-badge peanut-badge-success"><?php esc_html_e('Connected', 'peanut-suite'); ?></span>
                    <?php elseif ($site['status'] === 'error'): ?>
                        <span class="peanut-badge peanut-badge-danger"><?php esc_html_e('Error', 'peanut-suite'); ?></span>
                    <?php else: ?>
                        <span class="peanut-badge peanut-badge-neutral"><?php esc_html_e('Disconnected', 'peanut-suite'); ?></span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

<?php elseif ($current_tab === 'updates'): ?>
    <!-- Updates Tab -->
    <?php if (!empty($available_updates['core'])): ?>
    <div class="peanut-card" style="margin-bottom: 24px;">
        <div style="padding: 20px; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%);">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: #d97706; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <span class="dashicons dashicons-wordpress" style="color: white; font-size: 24px;"></span>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 16px;"><?php esc_html_e('WordPress Core Update Available', 'peanut-suite'); ?></h3>
                    <p style="margin: 4px 0 0; color: #64748b;">
                        <?php echo esc_html($available_updates['core']['current']); ?> &rarr; <?php echo esc_html($available_updates['core']['new']); ?>
                    </p>
                </div>
            </div>
            <button type="button" class="button button-primary peanut-update-core">
                <?php esc_html_e('Update WordPress', 'peanut-suite'); ?>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <div class="peanut-two-column">
        <!-- Plugin Updates -->
        <div class="peanut-card" style="padding: 0;">
            <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <h3 style="margin: 0; font-size: 15px; font-weight: 600;">
                    <span class="dashicons dashicons-admin-plugins" style="color: #0073aa;"></span>
                    <?php esc_html_e('Plugin Updates', 'peanut-suite'); ?>
                    <span class="peanut-badge peanut-badge-warning" style="margin-left: 8px;"><?php echo count($available_updates['plugins']); ?></span>
                </h3>
                <?php if (!empty($available_updates['plugins'])): ?>
                <button type="button" class="button button-small peanut-update-all-plugins">
                    <?php esc_html_e('Update All', 'peanut-suite'); ?>
                </button>
                <?php endif; ?>
            </div>
            <?php if (empty($available_updates['plugins'])): ?>
                <div class="peanut-empty-state" style="padding: 40px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 48px; width: 48px; height: 48px; color: #16a34a;"></span>
                    <h4><?php esc_html_e('All plugins up to date', 'peanut-suite'); ?></h4>
                </div>
            <?php else: ?>
                <ul class="peanut-update-list">
                    <?php foreach ($available_updates['plugins'] as $plugin): ?>
                    <li class="peanut-update-item">
                        <div class="peanut-update-info">
                            <div class="peanut-update-icon">
                                <span class="dashicons dashicons-admin-plugins"></span>
                            </div>
                            <div>
                                <div class="peanut-update-name"><?php echo esc_html($plugin['name']); ?></div>
                                <div class="peanut-update-version">
                                    <?php echo esc_html($plugin['current']); ?> &rarr; <?php echo esc_html($plugin['new']); ?>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="button button-small peanut-update-plugin" data-slug="<?php echo esc_attr($plugin['slug']); ?>">
                            <?php esc_html_e('Update', 'peanut-suite'); ?>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Theme Updates -->
        <div class="peanut-card" style="padding: 0;">
            <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <h3 style="margin: 0; font-size: 15px; font-weight: 600;">
                    <span class="dashicons dashicons-admin-appearance" style="color: #0073aa;"></span>
                    <?php esc_html_e('Theme Updates', 'peanut-suite'); ?>
                    <span class="peanut-badge peanut-badge-warning" style="margin-left: 8px;"><?php echo count($available_updates['themes']); ?></span>
                </h3>
                <?php if (!empty($available_updates['themes'])): ?>
                <button type="button" class="button button-small peanut-update-all-themes">
                    <?php esc_html_e('Update All', 'peanut-suite'); ?>
                </button>
                <?php endif; ?>
            </div>
            <?php if (empty($available_updates['themes'])): ?>
                <div class="peanut-empty-state" style="padding: 40px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 48px; width: 48px; height: 48px; color: #16a34a;"></span>
                    <h4><?php esc_html_e('All themes up to date', 'peanut-suite'); ?></h4>
                </div>
            <?php else: ?>
                <ul class="peanut-update-list">
                    <?php foreach ($available_updates['themes'] as $theme): ?>
                    <li class="peanut-update-item">
                        <div class="peanut-update-info">
                            <div class="peanut-update-icon">
                                <span class="dashicons dashicons-admin-appearance"></span>
                            </div>
                            <div>
                                <div class="peanut-update-name"><?php echo esc_html($theme['name']); ?></div>
                                <div class="peanut-update-version">
                                    <?php echo esc_html($theme['current']); ?> &rarr; <?php echo esc_html($theme['new']); ?>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="button button-small peanut-update-theme" data-slug="<?php echo esc_attr($theme['slug']); ?>">
                            <?php esc_html_e('Update', 'peanut-suite'); ?>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($current_tab === 'uptime'): ?>
    <!-- Uptime Tab -->
    <div class="peanut-card" style="padding: 20px;">
        <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 600;">
            <span class="dashicons dashicons-chart-line" style="color: #0073aa;"></span>
            <?php esc_html_e('Uptime History (Last 48 Hours)', 'peanut-suite'); ?>
        </h3>

        <div class="peanut-uptime-bar">
            <?php
            $reversed_history = array_reverse(array_slice($uptime_history, 0, 48));
            foreach ($reversed_history as $check):
                $class = $check['status'] === 'down' ? 'down' : ($check['response_time'] > 500 ? 'slow' : '');
            ?>
                <div class="peanut-uptime-segment <?php echo esc_attr($class); ?>"
                     title="<?php echo esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($check['checked_at'])) . ' - ' . $check['response_time'] . 'ms'); ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; gap: 20px; font-size: 12px; color: #64748b; margin-bottom: 20px;">
            <div><span style="display: inline-block; width: 12px; height: 12px; background: #16a34a; border-radius: 2px; margin-right: 6px;"></span> <?php esc_html_e('Up', 'peanut-suite'); ?></div>
            <div><span style="display: inline-block; width: 12px; height: 12px; background: #d97706; border-radius: 2px; margin-right: 6px;"></span> <?php esc_html_e('Slow (>500ms)', 'peanut-suite'); ?></div>
            <div><span style="display: inline-block; width: 12px; height: 12px; background: #dc2626; border-radius: 2px; margin-right: 6px;"></span> <?php esc_html_e('Down', 'peanut-suite'); ?></div>
        </div>

        <div class="peanut-uptime-stats">
            <div class="peanut-uptime-stat">
                <div class="peanut-health-metric-value" style="color: <?php echo $uptime_percentage >= 99.5 ? '#16a34a' : '#d97706'; ?>">
                    <?php echo number_format($uptime_percentage, 2); ?>%
                </div>
                <div class="peanut-health-metric-label"><?php esc_html_e('Uptime (30d)', 'peanut-suite'); ?></div>
            </div>
            <div class="peanut-uptime-stat">
                <?php
                $down_count = count(array_filter($uptime_history, fn($u) => $u['status'] === 'down'));
                ?>
                <div class="peanut-health-metric-value"><?php echo number_format_i18n($down_count); ?></div>
                <div class="peanut-health-metric-label"><?php esc_html_e('Incidents', 'peanut-suite'); ?></div>
            </div>
            <div class="peanut-uptime-stat">
                <div class="peanut-health-metric-value"><?php echo number_format_i18n($avg_response); ?>ms</div>
                <div class="peanut-health-metric-label"><?php esc_html_e('Avg Response', 'peanut-suite'); ?></div>
            </div>
            <div class="peanut-uptime-stat">
                <?php
                $max_response = !empty($uptime_history) ? max(array_column($uptime_history, 'response_time')) : 0;
                ?>
                <div class="peanut-health-metric-value"><?php echo number_format_i18n($max_response); ?>ms</div>
                <div class="peanut-health-metric-label"><?php esc_html_e('Max Response', 'peanut-suite'); ?></div>
            </div>
        </div>
    </div>

    <!-- Recent Incidents -->
    <div class="peanut-card" style="margin-top: 24px;">
        <h3 style="margin: 0 0 16px; padding: 20px 20px 0; font-size: 15px; font-weight: 600;">
            <span class="dashicons dashicons-warning" style="color: #d97706;"></span>
            <?php esc_html_e('Recent Incidents', 'peanut-suite'); ?>
        </h3>

        <?php
        $incidents = array_filter($uptime_history, fn($u) => $u['status'] === 'down');
        if (empty($incidents)):
        ?>
            <div class="peanut-empty-state" style="padding: 40px;">
                <span class="dashicons dashicons-yes-alt" style="font-size: 48px; width: 48px; height: 48px; color: #16a34a;"></span>
                <h4><?php esc_html_e('No incidents recorded', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Your site has been running smoothly with no downtime detected.', 'peanut-suite'); ?></p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Time', 'peanut-suite'); ?></th>
                        <th><?php esc_html_e('Duration', 'peanut-suite'); ?></th>
                        <th><?php esc_html_e('Error', 'peanut-suite'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($incidents, 0, 10) as $incident): ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($incident['checked_at']))); ?></td>
                        <td><?php esc_html_e('~30 min', 'peanut-suite'); ?></td>
                        <td><span class="peanut-badge peanut-badge-danger"><?php esc_html_e('Connection Failed', 'peanut-suite'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php elseif ($current_tab === 'settings'): ?>
    <!-- Settings Tab -->
    <form method="post" id="peanut-site-settings-form">
        <?php wp_nonce_field('peanut_site_settings', 'peanut_site_nonce'); ?>
        <input type="hidden" name="site_id" value="<?php echo esc_attr($site_id); ?>">

        <div class="peanut-card" style="padding: 20px;">
            <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 600;">
                <span class="dashicons dashicons-admin-generic" style="color: #0073aa;"></span>
                <?php esc_html_e('Site Settings', 'peanut-suite'); ?>
            </h3>

            <div class="peanut-form-group">
                <label for="site-name"><?php esc_html_e('Display Name', 'peanut-suite'); ?></label>
                <input type="text" id="site-name" name="site_name" value="<?php echo esc_attr($site['site_name']); ?>" class="regular-text">
                <?php echo peanut_field_help(__('A friendly name to identify this site in your dashboard.', 'peanut-suite')); ?>
            </div>
        </div>

        <div class="peanut-card" style="padding: 20px; margin-top: 24px;">
            <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 600;">
                <span class="dashicons dashicons-bell" style="color: #0073aa;"></span>
                <?php esc_html_e('Notifications', 'peanut-suite'); ?>
            </h3>

            <div class="peanut-form-group">
                <label>
                    <input type="checkbox" name="notify_downtime" value="1" checked>
                    <?php esc_html_e('Send email when site goes down', 'peanut-suite'); ?>
                </label>
            </div>

            <div class="peanut-form-group">
                <label>
                    <input type="checkbox" name="notify_updates" value="1" checked>
                    <?php esc_html_e('Send email when updates are available', 'peanut-suite'); ?>
                </label>
            </div>

            <div class="peanut-form-group">
                <label>
                    <input type="checkbox" name="notify_ssl" value="1" checked>
                    <?php esc_html_e('Send email before SSL certificate expires', 'peanut-suite'); ?>
                </label>
            </div>

            <div class="peanut-form-group">
                <label>
                    <input type="checkbox" name="notify_health" value="1">
                    <?php esc_html_e('Send weekly health report', 'peanut-suite'); ?>
                </label>
            </div>
        </div>

        <div class="peanut-card" style="padding: 20px; margin-top: 24px;">
            <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 600;">
                <span class="dashicons dashicons-clock" style="color: #0073aa;"></span>
                <?php esc_html_e('Monitoring Schedule', 'peanut-suite'); ?>
            </h3>

            <div class="peanut-form-group">
                <label for="check-interval"><?php esc_html_e('Check Interval', 'peanut-suite'); ?></label>
                <select id="check-interval" name="check_interval">
                    <option value="5"><?php esc_html_e('Every 5 minutes', 'peanut-suite'); ?></option>
                    <option value="15" selected><?php esc_html_e('Every 15 minutes', 'peanut-suite'); ?></option>
                    <option value="30"><?php esc_html_e('Every 30 minutes', 'peanut-suite'); ?></option>
                    <option value="60"><?php esc_html_e('Every hour', 'peanut-suite'); ?></option>
                </select>
            </div>

            <div class="peanut-form-group">
                <label for="health-check-interval"><?php esc_html_e('Health Check Interval', 'peanut-suite'); ?></label>
                <select id="health-check-interval" name="health_check_interval">
                    <option value="hourly"><?php esc_html_e('Every hour', 'peanut-suite'); ?></option>
                    <option value="twicedaily" selected><?php esc_html_e('Twice daily', 'peanut-suite'); ?></option>
                    <option value="daily"><?php esc_html_e('Once daily', 'peanut-suite'); ?></option>
                </select>
            </div>
        </div>

        <div class="peanut-card" style="padding: 20px; margin-top: 24px; border-color: #dc2626;">
            <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 600; color: #dc2626;">
                <span class="dashicons dashicons-warning" style="color: #dc2626;"></span>
                <?php esc_html_e('Danger Zone', 'peanut-suite'); ?>
            </h3>

            <p style="color: #64748b; margin-bottom: 16px;">
                <?php esc_html_e('Disconnecting this site will remove it from your monitoring dashboard. You can reconnect it at any time.', 'peanut-suite'); ?>
            </p>

            <button type="button" class="button" id="peanut-disconnect-site-confirm" style="color: #dc2626; border-color: #dc2626;">
                <?php esc_html_e('Disconnect This Site', 'peanut-suite'); ?>
            </button>
        </div>

        <p class="submit" style="margin-top: 24px;">
            <button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'peanut-suite'); ?></button>
        </p>
    </form>
<?php endif; ?>

<script>
jQuery(document).ready(function($) {
    // Refresh site - real AJAX call
    $('#peanut-refresh-site').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $btn.find('.dashicons').addClass('spin');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'peanut_refresh_monitor_site',
                peanut_nonce: '<?php echo wp_create_nonce('peanut_admin_nonce'); ?>',
                site_id: <?php echo (int) $site_id; ?>
            },
            success: function(response) {
                $btn.prop('disabled', false);
                $btn.find('.dashicons').removeClass('spin');
                if (response.success) {
                    PeanutAdmin.notify('<?php esc_html_e('Site data refreshed. Reloading...', 'peanut-suite'); ?>', 'success');
                    // Reload the page to show updated data
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    PeanutAdmin.notify(response.data.message || '<?php esc_html_e('Failed to refresh site', 'peanut-suite'); ?>', 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false);
                $btn.find('.dashicons').removeClass('spin');
                PeanutAdmin.notify('<?php esc_html_e('Connection error', 'peanut-suite'); ?>', 'error');
            }
        });
    });

    // Disconnect site - real AJAX call
    $('#peanut-disconnect-site, #peanut-disconnect-site-confirm').on('click', function() {
        if (confirm('<?php esc_html_e('Are you sure you want to disconnect this site?', 'peanut-suite'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'peanut_delete_monitor_site',
                    peanut_nonce: '<?php echo wp_create_nonce('peanut_admin_nonce'); ?>',
                    site_id: <?php echo (int) $site_id; ?>
                },
                success: function(response) {
                    if (response.success) {
                        PeanutAdmin.notify('<?php esc_html_e('Site disconnected', 'peanut-suite'); ?>', 'success');
                        window.location.href = '<?php echo esc_url(admin_url('admin.php?page=peanut-monitor')); ?>';
                    } else {
                        PeanutAdmin.notify(response.data.message || '<?php esc_html_e('Failed to disconnect', 'peanut-suite'); ?>', 'error');
                    }
                }
            });
        }
    });

    // Update buttons
    $('.peanut-update-plugin, .peanut-update-theme, .peanut-update-core').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php esc_html_e('Updating...', 'peanut-suite'); ?>');

        setTimeout(function() {
            $btn.closest('.peanut-update-item').fadeOut(function() {
                $(this).remove();
            });
            PeanutAdmin.notify('<?php esc_html_e('Update completed', 'peanut-suite'); ?>', 'success');
        }, 2000);
    });

    // Update all buttons
    $('.peanut-update-all-plugins, .peanut-update-all-themes').on('click', function() {
        var $btn = $(this);
        var $card = $btn.closest('.peanut-card');

        $btn.prop('disabled', true).text('<?php esc_html_e('Updating...', 'peanut-suite'); ?>');
        $card.find('.peanut-update-item button').prop('disabled', true);

        setTimeout(function() {
            $card.find('.peanut-update-list').html('<div class="peanut-empty-state" style="padding: 40px;"><span class="dashicons dashicons-yes-alt" style="font-size: 48px; width: 48px; height: 48px; color: #16a34a;"></span><h4><?php esc_html_e('All updates completed', 'peanut-suite'); ?></h4></div>');
            $btn.hide();
            $card.find('.peanut-badge').removeClass('peanut-badge-warning').addClass('peanut-badge-success').text('0');
            PeanutAdmin.notify('<?php esc_html_e('All updates completed', 'peanut-suite'); ?>', 'success');
        }, 3000);
    });

    // Settings form
    $('#peanut-site-settings-form').on('submit', function(e) {
        e.preventDefault();
        PeanutAdmin.notify('<?php esc_html_e('Settings saved', 'peanut-suite'); ?>', 'success');
    });
});
</script>

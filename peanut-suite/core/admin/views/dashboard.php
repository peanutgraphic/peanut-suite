<?php
/**
 * Dashboard View
 *
 * Main overview page showing stats, charts, and recent activity.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard stats via REST API internally
$stats = $this->get_dashboard_stats();
?>

<!-- Welcome Message (shown once after activation) -->
<?php if (get_option('peanut_show_welcome', true)): ?>
<div class="peanut-card peanut-welcome-card">
    <h3>
        <?php esc_html_e('Welcome to Marketing Suite!', 'peanut-suite'); ?>
        <button type="button" class="peanut-dismiss-card" data-dismiss-welcome aria-label="<?php esc_attr_e('Dismiss', 'peanut-suite'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </h3>
    <div class="peanut-ways-grid">
        <div class="peanut-way-card">
            <span class="dashicons dashicons-tag"></span>
            <h4><?php esc_html_e('Create UTM Campaigns', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Track your marketing efforts with UTM-tagged URLs to see where traffic comes from.', 'peanut-suite'); ?></p>
        </div>
        <div class="peanut-way-card">
            <span class="dashicons dashicons-admin-links"></span>
            <h4><?php esc_html_e('Shorten Links', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Create branded short links for social media, print materials, and easy sharing.', 'peanut-suite'); ?></p>
        </div>
        <div class="peanut-way-card">
            <span class="dashicons dashicons-chart-area"></span>
            <h4><?php esc_html_e('Track Results', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Monitor clicks, conversions, and ROI all in one unified dashboard.', 'peanut-suite'); ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="peanut-stats-grid">
    <div class="peanut-stat-card">
        <div class="peanut-stat-card-header">
            <span class="peanut-stat-label"><?php esc_html_e('UTM Campaigns', 'peanut-suite'); ?></span>
            <div class="peanut-stat-icon blue">
                <span class="dashicons dashicons-tag"></span>
            </div>
        </div>
        <div class="peanut-stat-value"><?php echo esc_html(number_format_i18n($stats['utms']['total'] ?? 0)); ?></div>
        <div class="peanut-stat-change <?php echo ($stats['utms']['change'] ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
            <span class="dashicons dashicons-arrow-<?php echo ($stats['utms']['change'] ?? 0) >= 0 ? 'up' : 'down'; ?>-alt"></span>
            <?php printf(esc_html__('%s this week', 'peanut-suite'), ($stats['utms']['change'] ?? 0) >= 0 ? '+' . ($stats['utms']['change'] ?? 0) : ($stats['utms']['change'] ?? 0)); ?>
        </div>
    </div>

    <div class="peanut-stat-card">
        <div class="peanut-stat-card-header">
            <span class="peanut-stat-label"><?php esc_html_e('Short Links', 'peanut-suite'); ?></span>
            <div class="peanut-stat-icon green">
                <span class="dashicons dashicons-admin-links"></span>
            </div>
        </div>
        <div class="peanut-stat-value"><?php echo esc_html(number_format_i18n($stats['links']['total'] ?? 0)); ?></div>
        <div class="peanut-stat-change <?php echo ($stats['links']['clicks'] ?? 0) > 0 ? 'positive' : 'neutral'; ?>">
            <span class="dashicons dashicons-chart-line"></span>
            <?php printf(esc_html__('%s clicks total', 'peanut-suite'), number_format_i18n($stats['links']['clicks'] ?? 0)); ?>
        </div>
    </div>

    <div class="peanut-stat-card">
        <div class="peanut-stat-card-header">
            <span class="peanut-stat-label"><?php esc_html_e('Contacts', 'peanut-suite'); ?></span>
            <div class="peanut-stat-icon yellow">
                <span class="dashicons dashicons-groups"></span>
            </div>
        </div>
        <div class="peanut-stat-value"><?php echo esc_html(number_format_i18n($stats['contacts']['total'] ?? 0)); ?></div>
        <div class="peanut-stat-change <?php echo ($stats['contacts']['change'] ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
            <span class="dashicons dashicons-arrow-<?php echo ($stats['contacts']['change'] ?? 0) >= 0 ? 'up' : 'down'; ?>-alt"></span>
            <?php printf(esc_html__('%s this week', 'peanut-suite'), ($stats['contacts']['change'] ?? 0) >= 0 ? '+' . ($stats['contacts']['change'] ?? 0) : ($stats['contacts']['change'] ?? 0)); ?>
        </div>
    </div>

    <div class="peanut-stat-card">
        <div class="peanut-stat-card-header">
            <span class="peanut-stat-label"><?php esc_html_e('Total Clicks', 'peanut-suite'); ?></span>
            <div class="peanut-stat-icon red">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
        </div>
        <div class="peanut-stat-value"><?php echo esc_html(number_format_i18n($stats['clicks']['total'] ?? 0)); ?></div>
        <div class="peanut-stat-change positive">
            <span class="dashicons dashicons-performance"></span>
            <?php esc_html_e('All time', 'peanut-suite'); ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="peanut-quick-actions">
    <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-utm-builder')); ?>" class="peanut-quick-action">
        <span class="dashicons dashicons-plus-alt2"></span>
        <?php esc_html_e('Create UTM', 'peanut-suite'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-links')); ?>" class="peanut-quick-action">
        <span class="dashicons dashicons-admin-links"></span>
        <?php esc_html_e('Add Link', 'peanut-suite'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-contacts')); ?>" class="peanut-quick-action">
        <span class="dashicons dashicons-groups"></span>
        <?php esc_html_e('View Contacts', 'peanut-suite'); ?>
    </a>
    <?php if (peanut_is_pro()): ?>
    <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-analytics')); ?>" class="peanut-quick-action">
        <span class="dashicons dashicons-chart-area"></span>
        <?php esc_html_e('View Analytics', 'peanut-suite'); ?>
    </a>
    <?php endif; ?>
</div>

<!-- Main Content Grid -->
<div class="peanut-grid peanut-grid-2">
    <!-- Recent UTM Codes -->
    <div class="peanut-card">
        <div class="peanut-card-header">
            <h3 class="peanut-card-title"><?php esc_html_e('Recent UTM Campaigns', 'peanut-suite'); ?></h3>
            <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-utm-library')); ?>" class="button button-small">
                <?php esc_html_e('View All', 'peanut-suite'); ?>
            </a>
        </div>
        <div class="peanut-card-body">
            <?php if (!empty($stats['recent_utms'])): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Campaign', 'peanut-suite'); ?></th>
                        <th><?php esc_html_e('Source', 'peanut-suite'); ?></th>
                        <th><?php esc_html_e('Clicks', 'peanut-suite'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['recent_utms'] as $utm): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($utm['utm_campaign'] ?? __('Untitled', 'peanut-suite')); ?></strong>
                            <br>
                            <small class="peanut-text-muted"><?php echo esc_html(wp_trim_words($utm['base_url'] ?? '', 5)); ?></small>
                        </td>
                        <td>
                            <span class="peanut-badge peanut-badge-info"><?php echo esc_html($utm['utm_source'] ?? '-'); ?></span>
                        </td>
                        <td><?php echo esc_html(number_format_i18n($utm['click_count'] ?? 0)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="peanut-empty-state">
                <div class="peanut-empty-state-icon">
                    <span class="dashicons dashicons-tag"></span>
                </div>
                <h3><?php esc_html_e('No UTM campaigns yet', 'peanut-suite'); ?></h3>
                <p><?php esc_html_e('Create your first UTM-tagged URL to start tracking your marketing campaigns.', 'peanut-suite'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-utm-builder')); ?>" class="button button-primary">
                    <?php esc_html_e('Create UTM Campaign', 'peanut-suite'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="peanut-card">
        <div class="peanut-card-header">
            <h3 class="peanut-card-title"><?php esc_html_e('Click Performance', 'peanut-suite'); ?></h3>
            <select id="peanut-chart-period" class="peanut-form-select" style="width: auto;">
                <option value="7"><?php esc_html_e('Last 7 days', 'peanut-suite'); ?></option>
                <option value="30" selected><?php esc_html_e('Last 30 days', 'peanut-suite'); ?></option>
                <option value="90"><?php esc_html_e('Last 90 days', 'peanut-suite'); ?></option>
            </select>
        </div>
        <div class="peanut-card-body">
            <div class="peanut-chart-container">
                <canvas id="peanut-performance-chart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Second Row -->
<div class="peanut-grid peanut-grid-2">
    <!-- Traffic Sources -->
    <div class="peanut-card">
        <div class="peanut-card-header">
            <h3 class="peanut-card-title"><?php esc_html_e('Traffic Sources', 'peanut-suite'); ?></h3>
        </div>
        <div class="peanut-card-body">
            <?php if (!empty($stats['sources']) && !empty($stats['sources']['labels'])): ?>
            <div class="peanut-chart-container" style="height: 250px;">
                <canvas id="peanut-sources-chart"></canvas>
            </div>
            <?php else: ?>
            <div class="peanut-empty-state">
                <div class="peanut-empty-state-icon">
                    <span class="dashicons dashicons-chart-pie"></span>
                </div>
                <h4><?php esc_html_e('No traffic sources yet', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Create UTM campaigns and start tracking clicks to see your traffic breakdown by source.', 'peanut-suite'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="peanut-card">
        <div class="peanut-card-header">
            <h3 class="peanut-card-title"><?php esc_html_e('Recent Activity', 'peanut-suite'); ?></h3>
        </div>
        <div class="peanut-card-body">
            <?php if (!empty($stats['activity'])): ?>
            <ul class="peanut-activity-list">
                <?php foreach ($stats['activity'] as $activity): ?>
                <li class="peanut-activity-item">
                    <span class="peanut-activity-icon <?php echo esc_attr($activity['type']); ?>">
                        <span class="dashicons dashicons-<?php echo esc_attr($activity['icon']); ?>"></span>
                    </span>
                    <div class="peanut-activity-content">
                        <span class="peanut-activity-text"><?php echo esc_html($activity['text']); ?></span>
                        <span class="peanut-activity-time"><?php echo esc_html(human_time_diff(strtotime($activity['time']), current_time('timestamp'))); ?> <?php esc_html_e('ago', 'peanut-suite'); ?></span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="peanut-empty-state" style="padding: 40px 20px;">
                <p><?php esc_html_e('No recent activity. Start using Marketing Suite to see your activity here.', 'peanut-suite'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Getting Started Tips (for new users) -->
<?php if (($stats['utms']['total'] ?? 0) === 0 && ($stats['links']['total'] ?? 0) === 0): ?>
<div class="peanut-card peanut-tips-card">
    <h3>
        <span class="dashicons dashicons-lightbulb"></span>
        <?php esc_html_e('Getting Started Tips', 'peanut-suite'); ?>
    </h3>
    <div class="peanut-ways-grid">
        <div class="peanut-way-card">
            <span class="dashicons dashicons-info-outline"></span>
            <h4><?php esc_html_e('What are UTM parameters?', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Tags added to URLs that track where traffic comes from. Data appears in your analytics when links are clicked.', 'peanut-suite'); ?></p>
        </div>
        <div class="peanut-way-card">
            <span class="dashicons dashicons-admin-links"></span>
            <h4><?php esc_html_e('Why use short links?', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Easier to share, look cleaner, and track clicks. Perfect for social media and print materials.', 'peanut-suite'); ?></p>
        </div>
        <div class="peanut-way-card">
            <span class="dashicons dashicons-yes-alt"></span>
            <h4><?php esc_html_e('Best practices', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Use consistent lowercase naming, be descriptive with campaigns, and document your conventions.', 'peanut-suite'); ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
jQuery(document).ready(function($) {
    // Initialize performance chart
    var performanceData = <?php echo wp_json_encode($stats['timeline'] ?? []); ?>;

    if (performanceData.labels && performanceData.labels.length > 0) {
        PeanutCharts.line('peanut-performance-chart', performanceData.labels, [
            {
                label: '<?php echo esc_js(__('UTM Clicks', 'peanut-suite')); ?>',
                data: performanceData.utm_clicks || []
            },
            {
                label: '<?php echo esc_js(__('Link Clicks', 'peanut-suite')); ?>',
                data: performanceData.link_clicks || []
            }
        ], {
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            }
        });
    }

    // Initialize sources chart
    var sourcesData = <?php echo wp_json_encode($stats['sources'] ?? []); ?>;

    if (sourcesData.labels && sourcesData.labels.length > 0) {
        PeanutCharts.doughnut('peanut-sources-chart', sourcesData.labels, sourcesData.data);
    }

    // Dismiss welcome banner
    $('[data-dismiss-welcome]').on('click', function() {
        $(this).closest('.peanut-welcome-banner').fadeOut();
        $.post(peanutAdmin.ajaxUrl, {
            action: 'peanut_dismiss_welcome',
            _wpnonce: peanutAdmin.nonce
        });
    });

    // Period selector for chart
    $('#peanut-chart-period').on('change', function() {
        // Reload chart data for selected period
        // This would make an AJAX call in a full implementation
    });
});
</script>

<?php
/**
 * Get dashboard stats helper method
 * This would be called from the Pages class
 */
function peanut_get_dashboard_stats(): array {
    global $wpdb;

    $stats = [
        'utms' => ['total' => 0, 'change' => 0],
        'links' => ['total' => 0, 'clicks' => 0],
        'contacts' => ['total' => 0, 'change' => 0],
        'clicks' => ['total' => 0],
        'recent_utms' => [],
        'sources' => [],
        'timeline' => [],
        'activity' => [],
    ];

    // Get UTM count
    $utms_table = $wpdb->prefix . 'peanut_utms';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $utms_table)) === $utms_table) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from trusted source
        $stats['utms']['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . esc_sql($utms_table));

        // Get recent UTMs
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from trusted source
        $stats['recent_utms'] = $wpdb->get_results(
            "SELECT * FROM " . esc_sql($utms_table) . " ORDER BY created_at DESC LIMIT 5",
            ARRAY_A
        ) ?: [];

        // Get total clicks
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from trusted source
        $stats['clicks']['total'] = (int) $wpdb->get_var("SELECT SUM(click_count) FROM " . esc_sql($utms_table));
    }

    // Get links count
    $links_table = $wpdb->prefix . 'peanut_links';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $links_table)) === $links_table) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from trusted source
        $stats['links']['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . esc_sql($links_table));
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from trusted source
        $stats['links']['clicks'] = (int) $wpdb->get_var("SELECT SUM(click_count) FROM " . esc_sql($links_table));
    }

    // Get contacts count
    $contacts_table = $wpdb->prefix . 'peanut_contacts';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $contacts_table)) === $contacts_table) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from trusted source
        $stats['contacts']['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . esc_sql($contacts_table));
    }

    return $stats;
}

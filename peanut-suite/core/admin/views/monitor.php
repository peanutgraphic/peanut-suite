<?php
/**
 * Site Monitor Page
 *
 * Monitor multiple WordPress sites from a single dashboard.
 * Agency feature - requires Agency license.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load Monitor dependencies
require_once PEANUT_PLUGIN_DIR . 'modules/monitor/class-monitor-database.php';
require_once PEANUT_PLUGIN_DIR . 'modules/monitor/class-monitor-sites.php';

// Get monitored sites from database
$sites_manager = new Monitor_Sites();
$sites_data = $sites_manager->get_all(['status' => 'active']);
$sites = [];

// Convert database objects to arrays with health data
foreach ($sites_data['items'] as $site) {
    $health = json_decode($site->last_health ?? '{}', true);
    $sites[] = [
        'id' => $site->id,
        'name' => $site->site_name,
        'url' => $site->site_url,
        'status' => $health['status'] ?? 'healthy',
        'wp_version' => $health['wp_version'] ?? '-',
        'php_version' => $health['php_version'] ?? '-',
        'updates_available' => $health['updates_available'] ?? 0,
        'last_check' => $site->last_check,
        'uptime' => $health['uptime'] ?? 100,
    ];
}

// Calculate stats
$total_sites = count($sites);
$healthy_sites = count(array_filter($sites, fn($s) => $s['status'] === 'healthy'));
$warning_sites = count(array_filter($sites, fn($s) => $s['status'] === 'warning'));
$critical_sites = count(array_filter($sites, fn($s) => $s['status'] === 'critical'));
$total_updates = array_sum(array_column($sites, 'updates_available'));
?>

<div class="peanut-monitor-page">

    <!-- Stats Cards -->
    <div class="peanut-stats-row">
        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-admin-multisite"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($total_sites); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Total Sites', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: rgba(70, 180, 80, 0.1);">
                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($healthy_sites); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Healthy', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: rgba(255, 186, 0, 0.1);">
                <span class="dashicons dashicons-warning" style="color: #ffba00;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($warning_sites); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Needs Attention', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: rgba(0, 160, 210, 0.1);">
                <span class="dashicons dashicons-update" style="color: #00a0d2;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($total_updates); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Updates Available', 'peanut-suite'); ?></span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="peanut-quick-actions">
        <button type="button" class="button" id="peanut-refresh-all">
            <span class="dashicons dashicons-update"></span>
            <?php esc_html_e('Refresh All Sites', 'peanut-suite'); ?>
        </button>
        <button type="button" class="button" id="peanut-update-all">
            <span class="dashicons dashicons-admin-plugins"></span>
            <?php esc_html_e('Update All Sites', 'peanut-suite'); ?>
        </button>
    </div>

    <!-- Sites List -->
    <div class="peanut-sites-grid">
        <?php if (empty($sites)): ?>
            <!-- Empty State -->
            <div class="peanut-empty-state-card">
                <span class="dashicons dashicons-admin-multisite"></span>
                <h3><?php esc_html_e('No Sites Connected', 'peanut-suite'); ?></h3>
                <p><?php esc_html_e('Start monitoring your WordPress sites by connecting your first site. You\'ll be able to track health, updates, and uptime all in one place.', 'peanut-suite'); ?></p>
                <button type="button" class="button button-primary button-hero" id="peanut-add-site-empty">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Add Your First Site', 'peanut-suite'); ?>
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($sites as $site): ?>
            <div class="peanut-site-card" data-site-id="<?php echo esc_attr($site['id']); ?>">
                <div class="peanut-site-header">
                    <div class="peanut-site-status <?php echo esc_attr($site['status']); ?>"></div>
                    <div class="peanut-site-info">
                        <h3><?php echo esc_html($site['name']); ?></h3>
                        <a href="<?php echo esc_url($site['url']); ?>" target="_blank" class="peanut-site-url">
                            <?php echo esc_html(preg_replace('#^https?://#', '', $site['url'])); ?>
                            <span class="dashicons dashicons-external"></span>
                        </a>
                    </div>
                    <div class="peanut-site-actions">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-site-detail&site=' . $site['id'])); ?>"
                           class="button button-small">
                            <?php esc_html_e('Details', 'peanut-suite'); ?>
                        </a>
                    </div>
                </div>
                <div class="peanut-site-stats">
                    <div class="peanut-site-stat">
                        <span class="peanut-site-stat-label"><?php esc_html_e('WordPress', 'peanut-suite'); ?></span>
                        <span class="peanut-site-stat-value"><?php echo esc_html($site['wp_version']); ?></span>
                    </div>
                    <div class="peanut-site-stat">
                        <span class="peanut-site-stat-label"><?php esc_html_e('PHP', 'peanut-suite'); ?></span>
                        <span class="peanut-site-stat-value"><?php echo esc_html($site['php_version']); ?></span>
                    </div>
                    <div class="peanut-site-stat">
                        <span class="peanut-site-stat-label"><?php esc_html_e('Updates', 'peanut-suite'); ?></span>
                        <span class="peanut-site-stat-value <?php echo $site['updates_available'] > 5 ? 'warning' : ''; ?>">
                            <?php echo number_format_i18n($site['updates_available']); ?>
                        </span>
                    </div>
                    <div class="peanut-site-stat">
                        <span class="peanut-site-stat-label"><?php esc_html_e('Uptime', 'peanut-suite'); ?></span>
                        <span class="peanut-site-stat-value"><?php echo number_format($site['uptime'], 1); ?>%</span>
                    </div>
                </div>
                <div class="peanut-site-footer">
                    <span class="peanut-last-check">
                        <?php esc_html_e('Last checked:', 'peanut-suite'); ?>
                        <?php if (!empty($site['last_check'])): ?>
                            <?php echo esc_html(human_time_diff(strtotime($site['last_check']), current_time('timestamp'))); ?>
                            <?php esc_html_e('ago', 'peanut-suite'); ?>
                        <?php else: ?>
                            <?php esc_html_e('Never', 'peanut-suite'); ?>
                        <?php endif; ?>
                    </span>
                    <button type="button" class="peanut-refresh-site" data-site="<?php echo esc_attr($site['id']); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Add Site Card -->
            <div class="peanut-site-card peanut-add-site-card" id="peanut-add-site">
                <div class="peanut-add-site-content">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <h4><?php esc_html_e('Add New Site', 'peanut-suite'); ?></h4>
                    <p><?php esc_html_e('Connect another WordPress site to monitor', 'peanut-suite'); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Setup Instructions -->
    <div class="peanut-card peanut-tips-card">
        <h3><?php esc_html_e('How to Connect a Site', 'peanut-suite'); ?></h3>
        <div class="peanut-ways-grid">
            <div class="peanut-way-card">
                <span class="dashicons dashicons-download"></span>
                <h4><?php esc_html_e('Install Peanut Suite', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Install and activate Peanut Suite on the WordPress site you want to monitor.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-way-card">
                <span class="dashicons dashicons-admin-network"></span>
                <h4><?php esc_html_e('Generate API Key', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Go to Peanut Suite → Settings → API on the remote site and generate a site key.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-way-card">
                <span class="dashicons dashicons-plus-alt"></span>
                <h4><?php esc_html_e('Add to Monitor', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Click "Add New Site" above and enter the site URL along with the API key.', 'peanut-suite'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Add Site Modal -->
<div id="peanut-add-site-modal" class="peanut-modal">
    <div class="peanut-modal-content">
        <div class="peanut-modal-header">
            <h2><?php esc_html_e('Add Site to Monitor', 'peanut-suite'); ?></h2>
            <button type="button" class="peanut-modal-close">&times;</button>
        </div>
        <div class="peanut-modal-body">
            <form id="peanut-add-site-form">
                <?php wp_nonce_field('peanut_admin_nonce', 'peanut_nonce'); ?>

                <div class="peanut-form-group">
                    <label for="site-name">
                        <?php esc_html_e('Site Name', 'peanut-suite'); ?>
                        <span class="required">*</span>
                        <?php echo peanut_tooltip(__('A friendly name to identify this site.', 'peanut-suite')); ?>
                    </label>
                    <input type="text" id="site-name" name="name" required placeholder="<?php esc_attr_e('e.g., Client Website', 'peanut-suite'); ?>">
                </div>

                <div class="peanut-form-group">
                    <label for="site-url">
                        <?php esc_html_e('Site URL', 'peanut-suite'); ?>
                        <span class="required">*</span>
                        <?php echo peanut_tooltip(__('The full URL of the WordPress site.', 'peanut-suite')); ?>
                    </label>
                    <input type="url" id="site-url" name="url" required placeholder="https://example.com">
                </div>

                <div class="peanut-form-group">
                    <label for="site-api-key">
                        <?php esc_html_e('API Key', 'peanut-suite'); ?>
                        <span class="required">*</span>
                        <?php echo peanut_tooltip(__('The API key generated on the remote site.', 'peanut-suite')); ?>
                    </label>
                    <input type="text" id="site-api-key" name="api_key" required placeholder="psk_xxxxxxxxxxxxx">
                    <?php echo peanut_field_help(__('Found in Peanut Suite > Settings > API on the remote site.', 'peanut-suite')); ?>
                </div>

                <div class="peanut-form-group">
                    <label>
                        <input type="checkbox" name="enable_notifications" value="1" checked>
                        <?php esc_html_e('Enable email notifications for this site', 'peanut-suite'); ?>
                    </label>
                </div>

                <div id="site-test-result" style="display: none;"></div>
            </form>
        </div>
        <div class="peanut-modal-footer">
            <button type="button" class="button" id="test-site-connection">
                <?php esc_html_e('Test Connection', 'peanut-suite'); ?>
            </button>
            <button type="button" class="button" data-dismiss="modal"><?php esc_html_e('Cancel', 'peanut-suite'); ?></button>
            <button type="submit" form="peanut-add-site-form" class="button button-primary">
                <?php esc_html_e('Add Site', 'peanut-suite'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Add site modal - both buttons
    $('#peanut-add-site, #peanut-add-site-empty').on('click', function() {
        PeanutAdmin.openModal('#peanut-add-site-modal');
    });

    // Test connection
    $('#test-site-connection').on('click', function() {
        var $btn = $(this);
        var url = $('#site-url').val();
        var apiKey = $('#site-api-key').val();

        if (!url || !apiKey) {
            PeanutAdmin.notify('<?php esc_html_e('Please enter URL and API key', 'peanut-suite'); ?>', 'warning');
            return;
        }

        $btn.prop('disabled', true).text('<?php esc_html_e('Testing...', 'peanut-suite'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'peanut_test_monitor_connection',
                peanut_nonce: $('#peanut_nonce').val(),
                url: url,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    $('#site-test-result')
                        .html('<div class="peanut-notice peanut-notice-success"><span class="dashicons dashicons-yes-alt"></span> ' + response.data.message + '</div>')
                        .show();
                } else {
                    $('#site-test-result')
                        .html('<div class="peanut-notice peanut-notice-error"><span class="dashicons dashicons-warning"></span> ' + response.data.message + '</div>')
                        .show();
                }
            },
            error: function() {
                $('#site-test-result')
                    .html('<div class="peanut-notice peanut-notice-error"><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Connection test failed. Please check the URL and try again.', 'peanut-suite'); ?></div>')
                    .show();
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php esc_html_e('Test Connection', 'peanut-suite'); ?>');
            }
        });
    });

    // Add site form submit
    $('#peanut-add-site-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $form.closest('.peanut-modal-content').find('button[type="submit"]');
        var originalText = $submitBtn.text();

        $submitBtn.prop('disabled', true).text('<?php esc_html_e('Adding...', 'peanut-suite'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'peanut_add_monitor_site',
                peanut_nonce: $('#peanut_nonce').val(),
                name: $('#site-name').val(),
                url: $('#site-url').val(),
                api_key: $('#site-api-key').val(),
                enable_notifications: $('input[name="enable_notifications"]').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    PeanutAdmin.notify(response.data.message, 'success');
                    PeanutAdmin.closeModal('#peanut-add-site-modal');
                    location.reload();
                } else {
                    PeanutAdmin.notify(response.data.message, 'error');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                PeanutAdmin.notify('<?php esc_html_e('An error occurred. Please try again.', 'peanut-suite'); ?>', 'error');
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Refresh single site
    $('.peanut-refresh-site').on('click', function() {
        var $btn = $(this);
        var siteId = $btn.data('site');

        $btn.addClass('spinning');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'peanut_refresh_monitor_site',
                peanut_nonce: '<?php echo wp_create_nonce('peanut_admin_nonce'); ?>',
                site_id: siteId
            },
            success: function(response) {
                if (response.success) {
                    PeanutAdmin.notify(response.data.message, 'success');
                    // Optionally update the card with new health data
                    location.reload();
                } else {
                    PeanutAdmin.notify(response.data.message, 'error');
                }
            },
            error: function() {
                PeanutAdmin.notify('<?php esc_html_e('Failed to refresh site.', 'peanut-suite'); ?>', 'error');
            },
            complete: function() {
                $btn.removeClass('spinning');
            }
        });
    });

    // Refresh all sites
    $('#peanut-refresh-all').on('click', function() {
        var $btn = $(this);
        var $refreshBtns = $('.peanut-refresh-site');
        var totalSites = $refreshBtns.length;
        var completed = 0;

        if (totalSites === 0) {
            PeanutAdmin.notify('<?php esc_html_e('No sites to refresh.', 'peanut-suite'); ?>', 'info');
            return;
        }

        $btn.prop('disabled', true);
        $refreshBtns.addClass('spinning');

        $refreshBtns.each(function() {
            var siteId = $(this).data('site');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'peanut_refresh_monitor_site',
                    peanut_nonce: '<?php echo wp_create_nonce('peanut_admin_nonce'); ?>',
                    site_id: siteId
                },
                complete: function() {
                    completed++;
                    if (completed >= totalSites) {
                        $refreshBtns.removeClass('spinning');
                        $btn.prop('disabled', false);
                        PeanutAdmin.notify('<?php esc_html_e('All sites refreshed', 'peanut-suite'); ?>', 'success');
                        location.reload();
                    }
                }
            });
        });
    });
});
</script>


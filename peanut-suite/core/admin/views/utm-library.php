<?php
/**
 * UTM Library Page
 *
 * Manage saved UTM campaigns and tracked URLs.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load the list table class
require_once PEANUT_PLUGIN_DIR . 'core/admin/tables/class-utm-list-table.php';

// Initialize and prepare list table
$list_table = new Peanut_UTM_List_Table();
$list_table->prepare_items();

// Get stats
global $wpdb;
$table_name = $wpdb->prefix . 'peanut_utms';
$stats = [
    'total' => 0,
    'clicks' => 0,
    'sources' => 0,
    'campaigns' => 0,
];

if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
    $stats['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $stats['clicks'] = (int) $wpdb->get_var("SELECT COALESCE(SUM(click_count), 0) FROM $table_name");
    $stats['sources'] = (int) $wpdb->get_var("SELECT COUNT(DISTINCT utm_source) FROM $table_name WHERE utm_source IS NOT NULL");
    $stats['campaigns'] = (int) $wpdb->get_var("SELECT COUNT(DISTINCT utm_campaign) FROM $table_name WHERE utm_campaign IS NOT NULL");
}
?>

<div class="peanut-utm-library-page">

    <!-- Stats Cards -->
    <div class="peanut-stats-row">
        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-tag"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['total']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Total UTMs', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['clicks']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Total Clicks', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: rgba(66, 133, 244, 0.1);">
                <span class="dashicons dashicons-megaphone" style="color: #4285f4;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['sources']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Sources', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: rgba(52, 168, 83, 0.1);">
                <span class="dashicons dashicons-flag" style="color: #34a853;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['campaigns']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Campaigns', 'peanut-suite'); ?></span>
            </div>
        </div>
    </div>

    <!-- Quick Tips -->
    <div class="peanut-info-banner">
        <span class="dashicons dashicons-lightbulb"></span>
        <div class="peanut-info-content">
            <strong><?php esc_html_e('Pro Tip: Organizing Your UTMs', 'peanut-suite'); ?></strong>
            <p><?php esc_html_e('Use consistent naming conventions across campaigns. For example, always use lowercase and hyphens: "facebook" not "Facebook", "spring-sale" not "Spring Sale". This makes filtering and analyzing your data much easier.', 'peanut-suite'); ?></p>
        </div>
        <button type="button" class="peanut-dismiss-banner">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>

    <!-- UTM Table -->
    <div class="peanut-card">
        <form method="get">
            <input type="hidden" name="page" value="peanut-utm-library" />
            <?php
            $list_table->search_box(__('Search UTMs', 'peanut-suite'), 'utm');
            $list_table->display();
            ?>
        </form>
    </div>

    <!-- Understanding Your UTMs -->
    <div class="peanut-card peanut-utm-guide">
        <h3><?php esc_html_e('Understanding Your UTM Data', 'peanut-suite'); ?></h3>
        <div class="peanut-utm-guide-grid">
            <div class="peanut-guide-item">
                <h4>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Click Tracking', 'peanut-suite'); ?>
                </h4>
                <p><?php esc_html_e('Every time someone visits your UTM-tagged URL, the click is recorded. High click counts indicate effective campaigns.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-guide-item">
                <h4>
                    <span class="dashicons dashicons-analytics"></span>
                    <?php esc_html_e('Google Analytics', 'peanut-suite'); ?>
                </h4>
                <p><?php esc_html_e('UTM parameters are automatically picked up by Google Analytics. View detailed reports under Acquisition > Campaigns.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-guide-item">
                <h4>
                    <span class="dashicons dashicons-filter"></span>
                    <?php esc_html_e('Source vs Medium', 'peanut-suite'); ?>
                </h4>
                <p><?php esc_html_e('Source is WHERE traffic comes from (google, facebook). Medium is HOW it comes (cpc, email, social).', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-guide-item">
                <h4>
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php esc_html_e('Campaign Names', 'peanut-suite'); ?>
                </h4>
                <p><?php esc_html_e('Use descriptive campaign names like "summer-sale-2024" or "product-launch-email" for easy identification.', 'peanut-suite'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Edit UTM Modal -->
<div id="peanut-edit-utm-modal" class="peanut-modal">
    <div class="peanut-modal-content">
        <div class="peanut-modal-header">
            <h2><?php esc_html_e('Edit UTM', 'peanut-suite'); ?></h2>
            <button type="button" class="peanut-modal-close">&times;</button>
        </div>
        <div class="peanut-modal-body">
            <form id="peanut-edit-utm-form">
                <?php wp_nonce_field('peanut_admin_nonce', 'peanut_nonce'); ?>
                <input type="hidden" id="utm-id" name="id">

                <div class="peanut-form-group">
                    <label for="utm-title">
                        <?php esc_html_e('Title / Label', 'peanut-suite'); ?>
                        <?php echo peanut_tooltip(__('An optional friendly name to identify this UTM.', 'peanut-suite')); ?>
                    </label>
                    <input type="text" id="utm-title" name="title" placeholder="<?php esc_attr_e('e.g., Facebook Summer Campaign', 'peanut-suite'); ?>">
                </div>

                <div class="peanut-form-group">
                    <label for="utm-base-url">
                        <?php esc_html_e('Base URL', 'peanut-suite'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="url" id="utm-base-url" name="base_url" required>
                </div>

                <div class="peanut-form-row">
                    <div class="peanut-form-group peanut-form-half">
                        <label for="utm-source">
                            <?php esc_html_e('Source', 'peanut-suite'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="text" id="utm-source" name="utm_source" required>
                    </div>
                    <div class="peanut-form-group peanut-form-half">
                        <label for="utm-medium">
                            <?php esc_html_e('Medium', 'peanut-suite'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="text" id="utm-medium" name="utm_medium" required>
                    </div>
                </div>

                <div class="peanut-form-group">
                    <label for="utm-campaign">
                        <?php esc_html_e('Campaign', 'peanut-suite'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="utm-campaign" name="utm_campaign" required>
                </div>

                <div class="peanut-form-row">
                    <div class="peanut-form-group peanut-form-half">
                        <label for="utm-term">
                            <?php esc_html_e('Term', 'peanut-suite'); ?>
                        </label>
                        <input type="text" id="utm-term" name="utm_term">
                    </div>
                    <div class="peanut-form-group peanut-form-half">
                        <label for="utm-content">
                            <?php esc_html_e('Content', 'peanut-suite'); ?>
                        </label>
                        <input type="text" id="utm-content" name="utm_content">
                    </div>
                </div>

                <div class="peanut-preview-box">
                    <label><?php esc_html_e('Preview URL:', 'peanut-suite'); ?></label>
                    <code id="utm-preview-url"></code>
                </div>
            </form>
        </div>
        <div class="peanut-modal-footer">
            <button type="button" class="button" data-dismiss="modal"><?php esc_html_e('Cancel', 'peanut-suite'); ?></button>
            <button type="submit" form="peanut-edit-utm-form" class="button button-primary">
                <?php esc_html_e('Save Changes', 'peanut-suite'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Copy UTM URL
    $(document).on('click', '.peanut-copy-utm', function(e) {
        e.preventDefault();
        var url = $(this).data('copy');
        PeanutAdmin.copyToClipboard(url);
    });

    // Edit UTM
    $(document).on('click', '.peanut-edit-utm', function(e) {
        e.preventDefault();
        var id = $(this).data('id');

        $.ajax({
            url: peanutAdmin.apiUrl + '/utms/' + id,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', peanutAdmin.nonce);
            },
            success: function(utm) {
                $('#utm-id').val(utm.id);
                $('#utm-title').val(utm.title || '');
                $('#utm-base-url').val(utm.base_url || '');
                $('#utm-source').val(utm.utm_source || '');
                $('#utm-medium').val(utm.utm_medium || '');
                $('#utm-campaign').val(utm.utm_campaign || '');
                $('#utm-term').val(utm.utm_term || '');
                $('#utm-content').val(utm.utm_content || '');

                updatePreview();
                PeanutAdmin.openModal('#peanut-edit-utm-modal');
            },
            error: function() {
                PeanutAdmin.notify('<?php esc_html_e('Failed to load UTM', 'peanut-suite'); ?>', 'error');
            }
        });
    });

    // Update preview
    function updatePreview() {
        var base = $('#utm-base-url').val();
        if (!base) {
            $('#utm-preview-url').text('');
            return;
        }

        var params = [];
        var fields = ['source', 'medium', 'campaign', 'term', 'content'];

        fields.forEach(function(field) {
            var val = $('#utm-' + field).val();
            if (val) {
                params.push('utm_' + field + '=' + encodeURIComponent(val));
            }
        });

        var separator = base.indexOf('?') > -1 ? '&' : '?';
        var full = params.length ? base + separator + params.join('&') : base;
        $('#utm-preview-url').text(full);
    }

    $('#peanut-edit-utm-form input').on('input', updatePreview);

    // Save UTM
    $('#peanut-edit-utm-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $form.closest('.peanut-modal-content').find('button[type="submit"]');
        var id = $('#utm-id').val();

        $submitBtn.prop('disabled', true).addClass('updating-message');

        $.ajax({
            url: peanutAdmin.apiUrl + '/utms/' + id,
            method: 'PUT',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', peanutAdmin.nonce);
            },
            data: {
                title: $('#utm-title').val(),
                base_url: $('#utm-base-url').val(),
                utm_source: $('#utm-source').val(),
                utm_medium: $('#utm-medium').val(),
                utm_campaign: $('#utm-campaign').val(),
                utm_term: $('#utm-term').val(),
                utm_content: $('#utm-content').val()
            },
            success: function() {
                PeanutAdmin.closeModal('#peanut-edit-utm-modal');
                PeanutAdmin.notify('<?php esc_html_e('UTM updated!', 'peanut-suite'); ?>', 'success');
                location.reload();
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || '<?php esc_html_e('Failed to save UTM', 'peanut-suite'); ?>';
                PeanutAdmin.notify(message, 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).removeClass('updating-message');
            }
        });
    });

    // Delete UTM
    $(document).on('click', '.peanut-delete-utm', function(e) {
        e.preventDefault();

        var $link = $(this);
        var id = $link.data('id');
        var confirmMsg = $link.data('confirm');

        if (!confirm(confirmMsg)) return;

        $.ajax({
            url: peanutAdmin.apiUrl + '/utms/' + id,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', peanutAdmin.nonce);
            },
            success: function() {
                PeanutAdmin.notify('<?php esc_html_e('UTM deleted', 'peanut-suite'); ?>', 'success');
                $link.closest('tr').fadeOut(function() {
                    $(this).remove();
                });
            },
            error: function() {
                PeanutAdmin.notify('<?php esc_html_e('Failed to delete UTM', 'peanut-suite'); ?>', 'error');
            }
        });
    });

    // Dismiss banner
    $('.peanut-dismiss-banner').on('click', function() {
        $(this).closest('.peanut-info-banner').slideUp();
    });
});
</script>


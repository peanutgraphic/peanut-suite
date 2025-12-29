<?php
/**
 * Links Management Page
 *
 * Create and manage short links with click tracking.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load the list table class
require_once PEANUT_PLUGIN_DIR . 'core/admin/tables/class-links-list-table.php';

// Initialize and prepare list table
$list_table = new Peanut_Links_List_Table();
$list_table->prepare_items();

// Get stats
global $wpdb;
$table_name = $wpdb->prefix . 'peanut_links';
$stats = [
    'total' => 0,
    'clicks' => 0,
    'active' => 0,
];

if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
    $stats['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $stats['clicks'] = (int) $wpdb->get_var("SELECT COALESCE(SUM(click_count), 0) FROM $table_name");
    $stats['active'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'");
}
?>

<div class="peanut-links-page">

    <!-- Stats Cards -->
    <div class="peanut-stats-row">
        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-admin-links"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['total']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Total Links', 'peanut-suite'); ?></span>
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
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['active']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Active Links', 'peanut-suite'); ?></span>
            </div>
        </div>
    </div>

    <!-- Info Banner -->
    <div class="peanut-info-banner">
        <span class="dashicons dashicons-info-outline"></span>
        <div class="peanut-info-content">
            <strong><?php esc_html_e('What are Short Links?', 'peanut-suite'); ?></strong>
            <p><?php esc_html_e('Short links are compact URLs that redirect to longer destinations. They\'re easier to share, look cleaner in marketing materials, and allow you to track clicks. Each link automatically tracks how many times it\'s been clicked.', 'peanut-suite'); ?></p>
        </div>
        <button type="button" class="peanut-dismiss-banner" aria-label="<?php esc_attr_e('Dismiss', 'peanut-suite'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>

    <!-- Links Table -->
    <div class="peanut-card">
        <form method="get">
            <input type="hidden" name="page" value="peanut-links" />
            <?php
            $list_table->search_box(__('Search Links', 'peanut-suite'), 'link');
            $list_table->display();
            ?>
        </form>
    </div>

    <!-- Use Cases -->
    <div class="peanut-card peanut-use-cases">
        <h3><?php esc_html_e('Common Uses for Short Links', 'peanut-suite'); ?></h3>
        <div class="peanut-use-cases-grid">
            <div class="peanut-use-case">
                <span class="dashicons dashicons-share"></span>
                <h4><?php esc_html_e('Social Media', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Share cleaner links on Twitter, LinkedIn, and other platforms where character count matters.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-use-case">
                <span class="dashicons dashicons-email-alt"></span>
                <h4><?php esc_html_e('Email Campaigns', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Track which emails drive the most clicks. Use descriptive slugs for easy identification.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-use-case">
                <span class="dashicons dashicons-format-image"></span>
                <h4><?php esc_html_e('Print Materials', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Short links are easier to type from business cards, flyers, and brochures.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-use-case">
                <span class="dashicons dashicons-chart-line"></span>
                <h4><?php esc_html_e('A/B Testing', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Create multiple links to the same destination to test which channels perform best.', 'peanut-suite'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Add Link Modal -->
<div id="peanut-add-link-modal" class="peanut-modal">
    <div class="peanut-modal-content">
        <div class="peanut-modal-header">
            <h2><?php esc_html_e('Create Short Link', 'peanut-suite'); ?></h2>
            <button type="button" class="peanut-modal-close">&times;</button>
        </div>
        <div class="peanut-modal-body">
            <form id="peanut-add-link-form">
                <?php wp_nonce_field('peanut_admin_nonce', 'peanut_nonce'); ?>

                <div class="peanut-form-group">
                    <label for="link-title">
                        <?php esc_html_e('Link Title', 'peanut-suite'); ?>
                        <?php echo peanut_tooltip(__('A descriptive name to help you identify this link. Only visible to you.', 'peanut-suite')); ?>
                    </label>
                    <input type="text" id="link-title" name="title" placeholder="<?php esc_attr_e('e.g., Summer Sale Landing Page', 'peanut-suite'); ?>">
                    <?php echo peanut_field_help(__('Optional. If left empty, the slug will be used as the title.', 'peanut-suite')); ?>
                </div>

                <div class="peanut-form-group">
                    <label for="link-destination">
                        <?php esc_html_e('Destination URL', 'peanut-suite'); ?>
                        <span class="required">*</span>
                        <?php echo peanut_tooltip(__('The full URL where visitors will be redirected when they click your short link.', 'peanut-suite')); ?>
                    </label>
                    <input type="url" id="link-destination" name="destination_url" required placeholder="https://example.com/your-landing-page">
                    <?php echo peanut_field_help(__('Include the full URL with https://', 'peanut-suite')); ?>
                </div>

                <div class="peanut-form-group">
                    <label for="link-slug">
                        <?php esc_html_e('Custom Slug', 'peanut-suite'); ?>
                        <?php echo peanut_tooltip(__('The short part after /go/ in your URL. Keep it short and memorable.', 'peanut-suite')); ?>
                    </label>
                    <div class="peanut-input-with-prefix">
                        <span class="peanut-input-prefix"><?php echo esc_html(home_url('/go/')); ?></span>
                        <input type="text" id="link-slug" name="slug" placeholder="summer-sale" pattern="[a-zA-Z0-9\-_]+">
                    </div>
                    <?php echo peanut_field_help(__('Leave empty to auto-generate. Use letters, numbers, and hyphens only.', 'peanut-suite')); ?>
                    <?php echo peanut_field_examples(['sale', 'promo2024', 'free-guide', 'newsletter']); ?>
                </div>

                <div class="peanut-form-row">
                    <div class="peanut-form-group peanut-form-half">
                        <label for="link-status">
                            <?php esc_html_e('Status', 'peanut-suite'); ?>
                            <?php echo peanut_tooltip(__('Inactive links will show a 404 page instead of redirecting.', 'peanut-suite')); ?>
                        </label>
                        <select id="link-status" name="status">
                            <option value="active"><?php esc_html_e('Active', 'peanut-suite'); ?></option>
                            <option value="inactive"><?php esc_html_e('Inactive', 'peanut-suite'); ?></option>
                        </select>
                    </div>

                    <div class="peanut-form-group peanut-form-half">
                        <label for="link-expires">
                            <?php esc_html_e('Expiration Date', 'peanut-suite'); ?>
                            <?php echo peanut_tooltip(__('Optional. After this date, the link will stop working.', 'peanut-suite')); ?>
                        </label>
                        <input type="date" id="link-expires" name="expires_at" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <!-- UTM Integration -->
                <div class="peanut-form-section">
                    <h4>
                        <?php esc_html_e('UTM Parameters', 'peanut-suite'); ?>
                        <span class="peanut-badge peanut-badge-info"><?php esc_html_e('Optional', 'peanut-suite'); ?></span>
                    </h4>
                    <p class="peanut-section-description">
                        <?php esc_html_e('Add UTM parameters to track this link in Google Analytics. These will be appended to your destination URL.', 'peanut-suite'); ?>
                    </p>

                    <div class="peanut-form-row">
                        <div class="peanut-form-group peanut-form-third">
                            <label for="link-utm-source"><?php esc_html_e('Source', 'peanut-suite'); ?></label>
                            <input type="text" id="link-utm-source" name="utm_source" placeholder="newsletter">
                        </div>
                        <div class="peanut-form-group peanut-form-third">
                            <label for="link-utm-medium"><?php esc_html_e('Medium', 'peanut-suite'); ?></label>
                            <input type="text" id="link-utm-medium" name="utm_medium" placeholder="email">
                        </div>
                        <div class="peanut-form-group peanut-form-third">
                            <label for="link-utm-campaign"><?php esc_html_e('Campaign', 'peanut-suite'); ?></label>
                            <input type="text" id="link-utm-campaign" name="utm_campaign" placeholder="summer2024">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="peanut-modal-footer">
            <button type="button" class="button" data-dismiss="modal"><?php esc_html_e('Cancel', 'peanut-suite'); ?></button>
            <button type="submit" form="peanut-add-link-form" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e('Create Link', 'peanut-suite'); ?>
            </button>
        </div>
    </div>
</div>

<!-- QR Code Modal -->
<div id="peanut-qr-modal" class="peanut-modal">
    <div class="peanut-modal-content peanut-modal-sm">
        <div class="peanut-modal-header">
            <h2><?php esc_html_e('QR Code', 'peanut-suite'); ?></h2>
            <button type="button" class="peanut-modal-close">&times;</button>
        </div>
        <div class="peanut-modal-body peanut-text-center">
            <div id="peanut-qr-code" class="peanut-qr-container">
                <!-- QR code will be generated here -->
            </div>
            <p class="peanut-qr-url"></p>
            <div class="peanut-qr-actions">
                <button type="button" class="button" id="peanut-download-qr">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Download PNG', 'peanut-suite'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize links management
    if (typeof PeanutLinks !== 'undefined') {
        PeanutLinks.init();
    }

    // Add link button
    $('#peanut-add-link').on('click', function() {
        PeanutAdmin.openModal('#peanut-add-link-modal');
    });

    // Form submission
    $('#peanut-add-link-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $form.closest('.peanut-modal-content').find('button[type="submit"]');

        $submitBtn.prop('disabled', true).addClass('updating-message');

        $.ajax({
            url: peanutAdmin.apiUrl + '/links',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', peanutAdmin.nonce);
            },
            data: {
                title: $('#link-title').val(),
                destination_url: $('#link-destination').val(),
                slug: $('#link-slug').val(),
                status: $('#link-status').val(),
                expires_at: $('#link-expires').val(),
                utm_source: $('#link-utm-source').val(),
                utm_medium: $('#link-utm-medium').val(),
                utm_campaign: $('#link-utm-campaign').val()
            },
            success: function(response) {
                PeanutAdmin.closeModal('#peanut-add-link-modal');
                PeanutAdmin.notify('<?php esc_html_e('Link created successfully!', 'peanut-suite'); ?>', 'success');
                location.reload();
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || '<?php esc_html_e('Failed to create link', 'peanut-suite'); ?>';
                PeanutAdmin.notify(message, 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).removeClass('updating-message');
            }
        });
    });

    // Copy link
    $(document).on('click', '.peanut-copy-link', function(e) {
        e.preventDefault();
        var url = $(this).data('copy');
        PeanutAdmin.copyToClipboard(url);
    });

    // QR Code
    $(document).on('click', '.peanut-qr-link', function(e) {
        e.preventDefault();
        var url = $(this).data('url');

        // Generate QR code using QRCode.js (loaded in admin.js)
        $('#peanut-qr-code').empty();

        if (typeof QRCode !== 'undefined') {
            new QRCode(document.getElementById('peanut-qr-code'), {
                text: url,
                width: 200,
                height: 200,
                colorDark: '#000000',
                colorLight: '#ffffff'
            });
        } else {
            // Fallback using API
            var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(url);
            $('#peanut-qr-code').html('<img src="' + qrUrl + '" alt="QR Code">');
        }

        $('.peanut-qr-url').text(url);
        PeanutAdmin.openModal('#peanut-qr-modal');
    });

    // Download QR
    $('#peanut-download-qr').on('click', function() {
        var canvas = $('#peanut-qr-code canvas')[0];
        if (canvas) {
            var link = document.createElement('a');
            link.download = 'qr-code.png';
            link.href = canvas.toDataURL();
            link.click();
        } else {
            // Fallback for img
            var img = $('#peanut-qr-code img')[0];
            if (img) {
                window.open(img.src, '_blank');
            }
        }
    });

    // Delete link
    $(document).on('click', '.peanut-delete-link', function(e) {
        e.preventDefault();

        var $link = $(this);
        var id = $link.data('id');
        var confirmMsg = $link.data('confirm');

        if (!confirm(confirmMsg)) return;

        $.ajax({
            url: peanutAdmin.apiUrl + '/links/' + id,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', peanutAdmin.nonce);
            },
            success: function() {
                PeanutAdmin.notify('<?php esc_html_e('Link deleted', 'peanut-suite'); ?>', 'success');
                $link.closest('tr').fadeOut(function() {
                    $(this).remove();
                });
            },
            error: function() {
                PeanutAdmin.notify('<?php esc_html_e('Failed to delete link', 'peanut-suite'); ?>', 'error');
            }
        });
    });

    // Dismiss info banner
    $('.peanut-dismiss-banner').on('click', function() {
        $(this).closest('.peanut-info-banner').slideUp();
    });
});
</script>


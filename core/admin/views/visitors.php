<?php
/**
 * Visitors Tracking Page
 *
 * Track website visitors and their journeys.
 * Pro feature - requires Pro or Agency license.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load the list table class
require_once PEANUT_PLUGIN_DIR . 'core/admin/tables/class-visitors-list-table.php';

// Initialize and prepare list table
$list_table = new Peanut_Visitors_List_Table();
$list_table->prepare_items();

// Get stats
global $wpdb;
$table_name = $wpdb->prefix . 'peanut_visitors';
$stats = [
    'total' => 0,
    'identified' => 0,
    'active_today' => 0,
    'pageviews' => 0,
];

if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
    $stats['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $stats['identified'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE email IS NOT NULL");
    $stats['active_today'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(last_seen_at) = CURDATE()");
    $stats['pageviews'] = (int) $wpdb->get_var("SELECT COALESCE(SUM(pageview_count), 0) FROM $table_name");
}

// Get tracking snippet
$site_id = get_option('peanut_site_id', wp_generate_uuid4());
if (!get_option('peanut_site_id')) {
    update_option('peanut_site_id', $site_id);
}
$tracking_endpoint = rest_url(PEANUT_API_NAMESPACE . '/track');
?>

<div class="peanut-visitors-page">

    <!-- Stats Cards -->
    <div class="peanut-stats-row">
        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['total']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Total Visitors', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: rgba(70, 180, 80, 0.1);">
                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['identified']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Identified', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: rgba(0, 160, 210, 0.1);">
                <span class="dashicons dashicons-clock" style="color: #00a0d2;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['active_today']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Active Today', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: rgba(136, 84, 208, 0.1);">
                <span class="dashicons dashicons-admin-page" style="color: #8854d0;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['pageviews']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Total Pageviews', 'peanut-suite'); ?></span>
            </div>
        </div>
    </div>

    <!-- How Tracking Works -->
    <div class="peanut-info-grid peanut-tracking-info">
        <div class="peanut-info-card">
            <span class="dashicons dashicons-editor-code"></span>
            <h4><?php esc_html_e('How It Works', 'peanut-suite'); ?></h4>
            <ol>
                <li><?php esc_html_e('Add the tracking snippet to your site', 'peanut-suite'); ?></li>
                <li><?php esc_html_e('Visitors are tracked automatically', 'peanut-suite'); ?></li>
                <li><?php esc_html_e('When they submit a form, they become "identified"', 'peanut-suite'); ?></li>
                <li><?php esc_html_e('View their full journey and touchpoints', 'peanut-suite'); ?></li>
            </ol>
        </div>
        <div class="peanut-info-card">
            <span class="dashicons dashicons-privacy"></span>
            <h4><?php esc_html_e('Privacy Compliant', 'peanut-suite'); ?></h4>
            <ul>
                <li><?php esc_html_e('No cookies required for basic tracking', 'peanut-suite'); ?></li>
                <li><?php esc_html_e('Data stored on your own server', 'peanut-suite'); ?></li>
                <li><?php esc_html_e('GDPR-friendly by design', 'peanut-suite'); ?></li>
                <li><?php esc_html_e('Export/delete visitor data anytime', 'peanut-suite'); ?></li>
            </ul>
        </div>
        <div class="peanut-info-card">
            <span class="dashicons dashicons-chart-area"></span>
            <h4><?php esc_html_e('What You\'ll See', 'peanut-suite'); ?></h4>
            <ul>
                <li><?php esc_html_e('Pages visited and time on site', 'peanut-suite'); ?></li>
                <li><?php esc_html_e('Traffic source and UTM parameters', 'peanut-suite'); ?></li>
                <li><?php esc_html_e('Device and location (approximate)', 'peanut-suite'); ?></li>
                <li><?php esc_html_e('Conversion events and form fills', 'peanut-suite'); ?></li>
            </ul>
        </div>
    </div>

    <!-- Visitors Table -->
    <div class="peanut-card">
        <form method="get">
            <input type="hidden" name="page" value="peanut-visitors" />
            <?php
            $list_table->search_box(__('Search Visitors', 'peanut-suite'), 'visitor');
            $list_table->display();
            ?>
        </form>
    </div>

    <!-- Visitor Journey Explanation -->
    <div class="peanut-card peanut-journey-explainer">
        <h3><?php esc_html_e('Understanding Visitor Status', 'peanut-suite'); ?></h3>
        <div class="peanut-status-cards">
            <div class="peanut-status-card">
                <span class="peanut-badge peanut-badge-neutral"><?php esc_html_e('Anonymous', 'peanut-suite'); ?></span>
                <p><?php esc_html_e('First-time visitors before they provide any information. Tracked by a unique ID.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-status-arrow">&rarr;</div>
            <div class="peanut-status-card">
                <span class="peanut-badge peanut-badge-info"><?php esc_html_e('Known', 'peanut-suite'); ?></span>
                <p><?php esc_html_e('Visitor has provided an email (via form, chat, etc.) but isn\'t in your contacts yet.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-status-arrow">&rarr;</div>
            <div class="peanut-status-card">
                <span class="peanut-badge peanut-badge-success"><?php esc_html_e('Identified', 'peanut-suite'); ?></span>
                <p><?php esc_html_e('Linked to a contact record. Full history and attribution data available.', 'peanut-suite'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Tracking Snippet Modal -->
<div id="peanut-snippet-modal" class="peanut-modal">
    <div class="peanut-modal-content peanut-modal-lg">
        <div class="peanut-modal-header">
            <h2><?php esc_html_e('Tracking Code', 'peanut-suite'); ?></h2>
            <button type="button" class="peanut-modal-close">&times;</button>
        </div>
        <div class="peanut-modal-body">
            <div class="peanut-snippet-tabs">
                <button type="button" class="peanut-tab-btn active" data-tab="auto"><?php esc_html_e('Automatic (WordPress)', 'peanut-suite'); ?></button>
                <button type="button" class="peanut-tab-btn" data-tab="manual"><?php esc_html_e('Manual (HTML)', 'peanut-suite'); ?></button>
            </div>

            <div class="peanut-tab-content" id="tab-auto">
                <div class="peanut-notice peanut-notice-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <div>
                        <strong><?php esc_html_e('Automatic Tracking Enabled', 'peanut-suite'); ?></strong>
                        <p><?php esc_html_e('Peanut Suite automatically adds tracking to your WordPress site. No manual installation required!', 'peanut-suite'); ?></p>
                    </div>
                </div>
                <h4><?php esc_html_e('Verification', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('To verify tracking is working:', 'peanut-suite'); ?></p>
                <ol>
                    <li><?php esc_html_e('Open your site in an incognito/private browser window', 'peanut-suite'); ?></li>
                    <li><?php esc_html_e('Visit a few pages on your site', 'peanut-suite'); ?></li>
                    <li><?php esc_html_e('Return here and refresh - you should see your visit', 'peanut-suite'); ?></li>
                </ol>
            </div>

            <div class="peanut-tab-content" id="tab-manual" style="display: none;">
                <p><?php esc_html_e('If you need to add tracking to a non-WordPress site, use this code snippet:', 'peanut-suite'); ?></p>
                <div class="peanut-code-block">
                    <button type="button" class="peanut-copy-code" data-target="tracking-code">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                    <pre id="tracking-code"><code>&lt;script&gt;
(function() {
    var peanut = {
        siteId: '<?php echo esc_js($site_id); ?>',
        endpoint: '<?php echo esc_js($tracking_endpoint); ?>'
    };

    // Generate visitor ID
    var visitorId = localStorage.getItem('peanut_vid');
    if (!visitorId) {
        visitorId = 'v_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('peanut_vid', visitorId);
    }

    // Track pageview
    function track(event, data) {
        data = data || {};
        data.visitor_id = visitorId;
        data.site_id = peanut.siteId;
        data.event = event;
        data.url = window.location.href;
        data.referrer = document.referrer;
        data.title = document.title;

        navigator.sendBeacon(peanut.endpoint, JSON.stringify(data));
    }

    // Track initial pageview
    track('pageview');

    // Expose identify function
    window.peanutIdentify = function(email, data) {
        data = data || {};
        data.email = email;
        track('identify', data);
    };
})();
&lt;/script&gt;</code></pre>
                </div>
                <h4><?php esc_html_e('Identifying Visitors', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('When a visitor provides their email (form submission, signup, etc.), call:', 'peanut-suite'); ?></p>
                <div class="peanut-code-block">
                    <pre><code>peanutIdentify('visitor@example.com', {
    first_name: 'John',
    last_name: 'Doe'
});</code></pre>
                </div>
            </div>
        </div>
        <div class="peanut-modal-footer">
            <button type="button" class="button" data-dismiss="modal"><?php esc_html_e('Close', 'peanut-suite'); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Show snippet modal
    $('#peanut-get-snippet').on('click', function() {
        PeanutAdmin.openModal('#peanut-snippet-modal');
    });

    // Tab switching
    $('.peanut-tab-btn').on('click', function() {
        var tab = $(this).data('tab');
        $('.peanut-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.peanut-tab-content').hide();
        $('#tab-' + tab).show();
    });

    // Copy code
    $('.peanut-copy-code').on('click', function() {
        var target = $(this).data('target');
        var code = $('#' + target).text();
        PeanutAdmin.copyToClipboard(code);
    });

    // Convert visitor to contact
    $(document).on('click', '.peanut-convert-visitor', function(e) {
        e.preventDefault();
        var visitorId = $(this).data('id');
        var email = $(this).data('email');

        if (!confirm('<?php esc_html_e('Add this visitor to your contacts?', 'peanut-suite'); ?>')) return;

        $.ajax({
            url: peanutAdmin.apiUrl + '/contacts',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', peanutAdmin.nonce);
            },
            data: {
                email: email,
                source: 'visitor',
                visitor_id: visitorId
            },
            success: function() {
                PeanutAdmin.notify('<?php esc_html_e('Contact created!', 'peanut-suite'); ?>', 'success');
                location.reload();
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || '<?php esc_html_e('Failed to create contact', 'peanut-suite'); ?>';
                PeanutAdmin.notify(message, 'error');
            }
        });
    });
});
</script>


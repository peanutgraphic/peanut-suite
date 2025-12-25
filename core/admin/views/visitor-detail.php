<?php
/**
 * Visitor Detail View
 *
 * Shows detailed visitor profile with event timeline.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get visitor ID from URL
$visitor_id = isset($_GET['visitor']) ? sanitize_text_field($_GET['visitor']) : '';

if (empty($visitor_id)) {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('No visitor ID specified.', 'peanut-suite'); ?></p>
    </div>
    <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-visitors')); ?>" class="button">
            <?php esc_html_e('Back to Visitors', 'peanut-suite'); ?>
        </a>
    </p>
    <?php
    return;
}

// Load database class
require_once PEANUT_PLUGIN_DIR . 'modules/visitors/class-visitors-database.php';
use PeanutSuite\Visitors\Visitors_Database;

global $wpdb;
$visitors_table = Visitors_Database::get_visitors_table();
$events_table = Visitors_Database::get_events_table();

// Get visitor
$visitor = $wpdb->get_row(
    $wpdb->prepare("SELECT * FROM $visitors_table WHERE visitor_id = %s", $visitor_id),
    ARRAY_A
);

if (!$visitor) {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('Visitor not found.', 'peanut-suite'); ?></p>
    </div>
    <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-visitors')); ?>" class="button">
            <?php esc_html_e('Back to Visitors', 'peanut-suite'); ?>
        </a>
    </p>
    <?php
    return;
}

// Get visitor events
$events = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $events_table WHERE visitor_id = %s ORDER BY created_at DESC LIMIT 100",
        $visitor_id
    ),
    ARRAY_A
) ?: [];

// Get UTM data from events
$utm_data = [];
foreach ($events as $event) {
    if (!empty($event['utm_source']) && !isset($utm_data['source'])) {
        $utm_data = [
            'source' => $event['utm_source'],
            'medium' => $event['utm_medium'],
            'campaign' => $event['utm_campaign'],
            'term' => $event['utm_term'],
            'content' => $event['utm_content'],
        ];
        break;
    }
}

// Determine visitor status
$status = 'anonymous';
$status_label = __('Anonymous', 'peanut-suite');
$status_class = 'peanut-badge-neutral';
if (!empty($visitor['email'])) {
    $status = 'identified';
    $status_label = __('Identified', 'peanut-suite');
    $status_class = 'peanut-badge-success';
}
if (!empty($visitor['contact_id'])) {
    $status = 'contact';
    $status_label = __('Contact', 'peanut-suite');
    $status_class = 'peanut-badge-success';
}
?>

<a href="<?php echo esc_url(admin_url('admin.php?page=peanut-visitors')); ?>" class="button" style="margin-bottom: 20px;">
    <span class="dashicons dashicons-arrow-left-alt" style="margin-top: 4px;"></span>
    <?php esc_html_e('Back to Visitors', 'peanut-suite'); ?>
</a>

<div class="peanut-visitor-layout">
    <!-- Sidebar -->
    <div class="peanut-visitor-sidebar">
        <!-- Profile Card -->
        <div class="peanut-card peanut-profile-card">
            <div class="peanut-profile-avatar">
                <?php if (!empty($visitor['email'])): ?>
                    <img src="<?php echo esc_url(get_avatar_url($visitor['email'], ['size' => 80])); ?>" alt="" style="width: 80px; height: 80px; border-radius: 50%;">
                <?php else: ?>
                    <span class="dashicons dashicons-admin-users"></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($visitor['email'])): ?>
                <div class="peanut-profile-name"><?php echo esc_html($visitor['email']); ?></div>
            <?php else: ?>
                <div class="peanut-profile-name"><?php echo esc_html(substr($visitor_id, 0, 8) . '...'); ?></div>
            <?php endif; ?>

            <span class="peanut-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>

            <div class="peanut-profile-meta">
                <div class="peanut-profile-meta-item">
                    <span class="peanut-profile-meta-label"><?php esc_html_e('First Seen', 'peanut-suite'); ?></span>
                    <span class="peanut-profile-meta-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($visitor['first_seen']))); ?></span>
                </div>
                <div class="peanut-profile-meta-item">
                    <span class="peanut-profile-meta-label"><?php esc_html_e('Last Seen', 'peanut-suite'); ?></span>
                    <span class="peanut-profile-meta-value"><?php echo esc_html(human_time_diff(strtotime($visitor['last_seen'])) . ' ' . __('ago', 'peanut-suite')); ?></span>
                </div>
                <div class="peanut-profile-meta-item">
                    <span class="peanut-profile-meta-label"><?php esc_html_e('Total Visits', 'peanut-suite'); ?></span>
                    <span class="peanut-profile-meta-value"><?php echo number_format_i18n($visitor['total_visits'] ?? 0); ?></span>
                </div>
                <div class="peanut-profile-meta-item">
                    <span class="peanut-profile-meta-label"><?php esc_html_e('Page Views', 'peanut-suite'); ?></span>
                    <span class="peanut-profile-meta-value"><?php echo number_format_i18n($visitor['total_pageviews'] ?? 0); ?></span>
                </div>
                <?php if (!empty($visitor['device_type'])): ?>
                <div class="peanut-profile-meta-item">
                    <span class="peanut-profile-meta-label"><?php esc_html_e('Device', 'peanut-suite'); ?></span>
                    <span class="peanut-profile-meta-value"><?php echo esc_html(ucfirst($visitor['device_type'])); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($visitor['browser'])): ?>
                <div class="peanut-profile-meta-item">
                    <span class="peanut-profile-meta-label"><?php esc_html_e('Browser', 'peanut-suite'); ?></span>
                    <span class="peanut-profile-meta-value"><?php echo esc_html($visitor['browser']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($visitor['country'])): ?>
                <div class="peanut-profile-meta-item">
                    <span class="peanut-profile-meta-label"><?php esc_html_e('Location', 'peanut-suite'); ?></span>
                    <span class="peanut-profile-meta-value">
                        <?php
                        $location = [];
                        if (!empty($visitor['city'])) $location[] = $visitor['city'];
                        if (!empty($visitor['country'])) $location[] = strtoupper($visitor['country']);
                        echo esc_html(implode(', ', $location));
                        ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- UTM Attribution -->
        <?php if (!empty($utm_data['source'])): ?>
        <div class="peanut-card" style="padding: 20px;">
            <h3 style="margin: 0 0 12px; font-size: 14px; font-weight: 600;">
                <span class="dashicons dashicons-tag" style="color: #0073aa;"></span>
                <?php esc_html_e('Attribution', 'peanut-suite'); ?>
            </h3>
            <div>
                <?php if (!empty($utm_data['source'])): ?>
                    <span class="peanut-utm-tag"><strong>Source:</strong> <?php echo esc_html($utm_data['source']); ?></span>
                <?php endif; ?>
                <?php if (!empty($utm_data['medium'])): ?>
                    <span class="peanut-utm-tag"><strong>Medium:</strong> <?php echo esc_html($utm_data['medium']); ?></span>
                <?php endif; ?>
                <?php if (!empty($utm_data['campaign'])): ?>
                    <span class="peanut-utm-tag"><strong>Campaign:</strong> <?php echo esc_html($utm_data['campaign']); ?></span>
                <?php endif; ?>
                <?php if (!empty($utm_data['term'])): ?>
                    <span class="peanut-utm-tag"><strong>Term:</strong> <?php echo esc_html($utm_data['term']); ?></span>
                <?php endif; ?>
                <?php if (!empty($utm_data['content'])): ?>
                    <span class="peanut-utm-tag"><strong>Content:</strong> <?php echo esc_html($utm_data['content']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Timeline -->
    <div class="peanut-card" style="padding: 20px;">
        <h3 style="margin: 0 0 20px; font-size: 16px; font-weight: 600;">
            <span class="dashicons dashicons-backup" style="color: #0073aa;"></span>
            <?php esc_html_e('Activity Timeline', 'peanut-suite'); ?>
        </h3>

        <?php if (empty($events)): ?>
            <div class="peanut-empty-state" style="padding: 40px;">
                <span class="dashicons dashicons-clock" style="font-size: 48px; width: 48px; height: 48px; color: #cbd5e1;"></span>
                <h4><?php esc_html_e('No events recorded yet', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Events will appear here as this visitor interacts with your site.', 'peanut-suite'); ?></p>
            </div>
        <?php else: ?>
            <div class="peanut-timeline">
                <?php foreach ($events as $event):
                    $event_class = '';
                    $event_icon = 'visibility';
                    switch ($event['event_type']) {
                        case 'pageview':
                            $event_class = 'pageview';
                            $event_icon = 'visibility';
                            break;
                        case 'identify':
                            $event_class = 'identify';
                            $event_icon = 'admin-users';
                            break;
                        case 'conversion':
                        case 'form_submit':
                            $event_class = 'conversion';
                            $event_icon = 'yes-alt';
                            break;
                    }
                ?>
                <div class="peanut-timeline-item <?php echo esc_attr($event_class); ?>">
                    <div class="peanut-timeline-type">
                        <span class="dashicons dashicons-<?php echo esc_attr($event_icon); ?>" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $event['event_type']))); ?>
                    </div>
                    <?php if (!empty($event['page_title'])): ?>
                        <div class="peanut-timeline-title"><?php echo esc_html($event['page_title']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($event['page_url'])): ?>
                        <div class="peanut-timeline-url"><?php echo esc_html($event['page_url']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($event['referrer'])): ?>
                        <div class="peanut-timeline-url">
                            <strong><?php esc_html_e('Referrer:', 'peanut-suite'); ?></strong> <?php echo esc_html($event['referrer']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="peanut-timeline-time">
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event['created_at']))); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
/**
 * Popups List View
 *
 * Displays all popups with stats and management options.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load list table
require_once PEANUT_PLUGIN_DIR . 'core/admin/tables/class-popups-list-table.php';

// Get stats
global $wpdb;
require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-database.php';
$table_name = Popups_Database::popups_table();

$stats = [
    'total' => 0,
    'active' => 0,
    'views' => 0,
    'conversions' => 0,
];

if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
    $stats['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $stats['active'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'");
    $stats['views'] = (int) $wpdb->get_var("SELECT COALESCE(SUM(views), 0) FROM $table_name");
    $stats['conversions'] = (int) $wpdb->get_var("SELECT COALESCE(SUM(conversions), 0) FROM $table_name");
}

$conversion_rate = $stats['views'] > 0 ? ($stats['conversions'] / $stats['views']) * 100 : 0;

// Initialize list table
$list_table = new Peanut_Popups_List_Table();
$list_table->prepare_items();
?>

<!-- Stats Cards -->
<div class="peanut-stats-grid">
    <div class="peanut-stat-card">
        <div class="peanut-stat-icon" style="background: #e0f2fe;">
            <span class="dashicons dashicons-format-chat" style="color: #0284c7;"></span>
        </div>
        <div class="peanut-stat-content">
            <div class="peanut-stat-value"><?php echo number_format_i18n($stats['total']); ?></div>
            <div class="peanut-stat-label"><?php esc_html_e('Total Popups', 'peanut-suite'); ?></div>
        </div>
    </div>

    <div class="peanut-stat-card">
        <div class="peanut-stat-icon" style="background: #dcfce7;">
            <span class="dashicons dashicons-yes-alt" style="color: #16a34a;"></span>
        </div>
        <div class="peanut-stat-content">
            <div class="peanut-stat-value"><?php echo number_format_i18n($stats['active']); ?></div>
            <div class="peanut-stat-label"><?php esc_html_e('Active', 'peanut-suite'); ?></div>
        </div>
    </div>

    <div class="peanut-stat-card">
        <div class="peanut-stat-icon" style="background: #fef3c7;">
            <span class="dashicons dashicons-visibility" style="color: #d97706;"></span>
        </div>
        <div class="peanut-stat-content">
            <div class="peanut-stat-value"><?php echo number_format_i18n($stats['views']); ?></div>
            <div class="peanut-stat-label"><?php esc_html_e('Total Views', 'peanut-suite'); ?></div>
        </div>
    </div>

    <div class="peanut-stat-card">
        <div class="peanut-stat-icon" style="background: #ede9fe;">
            <span class="dashicons dashicons-forms" style="color: #7c3aed;"></span>
        </div>
        <div class="peanut-stat-content">
            <div class="peanut-stat-value"><?php echo number_format_i18n($stats['conversions']); ?></div>
            <div class="peanut-stat-label"><?php esc_html_e('Conversions', 'peanut-suite'); ?></div>
        </div>
    </div>
</div>

<!-- Conversion Rate Highlight -->
<?php if ($stats['views'] > 0): ?>
<div class="peanut-card peanut-conversion-highlight">
    <div class="peanut-conversion-rate">
        <span class="peanut-conversion-value <?php echo $conversion_rate >= 3 ? 'excellent' : ($conversion_rate >= 1 ? 'good' : 'needs-work'); ?>">
            <?php echo number_format($conversion_rate, 2); ?>%
        </span>
    </div>
    <div class="peanut-conversion-info">
        <strong><?php esc_html_e('Overall Conversion Rate', 'peanut-suite'); ?></strong>
        <p>
            <?php
            if ($conversion_rate >= 3) {
                esc_html_e('Excellent! Your popups are performing well.', 'peanut-suite');
            } elseif ($conversion_rate >= 1) {
                esc_html_e('Good performance. Try A/B testing to improve.', 'peanut-suite');
            } else {
                esc_html_e('Consider optimizing your popup timing and copy.', 'peanut-suite');
            }
            ?>
        </p>
    </div>
</div>
<?php endif; ?>

<!-- Popups List -->
<div class="peanut-card">
    <form method="get">
        <input type="hidden" name="page" value="peanut-popups" />
        <?php
        $list_table->search_box(__('Search Popups', 'peanut-suite'), 'popup-search');
        $list_table->display();
        ?>
    </form>
</div>

<!-- Popup Type Guide -->
<div class="peanut-card peanut-tips-card">
    <h3><?php esc_html_e('Popup Types', 'peanut-suite'); ?></h3>
    <div class="peanut-ways-grid">
        <div class="peanut-way-card">
            <span class="dashicons dashicons-format-chat"></span>
            <h4><?php esc_html_e('Modal', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Center overlay popup that grabs attention. Great for important announcements and signup forms.', 'peanut-suite'); ?></p>
        </div>
        <div class="peanut-way-card">
            <span class="dashicons dashicons-migrate"></span>
            <h4><?php esc_html_e('Slide-in', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Corner notification that slides in. Less intrusive, perfect for subtle prompts and chat widgets.', 'peanut-suite'); ?></p>
        </div>
        <div class="peanut-way-card">
            <span class="dashicons dashicons-minus"></span>
            <h4><?php esc_html_e('Bar', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Top or bottom banner that stays visible. Ideal for announcements, promotions, and cookie notices.', 'peanut-suite'); ?></p>
        </div>
        <div class="peanut-way-card">
            <span class="dashicons dashicons-fullscreen-alt"></span>
            <h4><?php esc_html_e('Fullscreen', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Welcome mat style that takes over the screen. Best for high-priority offers and email capture.', 'peanut-suite'); ?></p>
        </div>
    </div>
</div>

<!-- Tips -->
<div class="peanut-card peanut-tips-card">
    <h3>
        <span class="dashicons dashicons-lightbulb"></span>
        <?php esc_html_e('Tips for Better Conversions', 'peanut-suite'); ?>
    </h3>
    <div class="peanut-ways-grid">
        <div class="peanut-way-card">
            <span class="dashicons dashicons-dismiss"></span>
            <h4><?php esc_html_e('Exit Intent', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Use exit-intent triggers to catch visitors before they leave with a compelling last offer.', 'peanut-suite'); ?></p>
        </div>
        <div class="peanut-way-card">
            <span class="dashicons dashicons-email-alt"></span>
            <h4><?php esc_html_e('Keep It Short', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Shorter forms convert better. Email-only forms typically see 2-3x higher conversion rates.', 'peanut-suite'); ?></p>
        </div>
        <div class="peanut-way-card">
            <span class="dashicons dashicons-megaphone"></span>
            <h4><?php esc_html_e('Clear Value', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Offer clear value (discount, free resource) prominently in your headline to drive action.', 'peanut-suite'); ?></p>
        </div>
        <div class="peanut-way-card">
            <span class="dashicons dashicons-admin-site-alt3"></span>
            <h4><?php esc_html_e('Page Targeting', 'peanut-suite'); ?></h4>
            <p><?php esc_html_e('Use page targeting to show relevant offers based on the content visitors are viewing.', 'peanut-suite'); ?></p>
        </div>
    </div>
</div>

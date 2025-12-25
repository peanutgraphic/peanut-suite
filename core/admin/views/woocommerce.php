<?php
/**
 * WooCommerce Attribution View
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
$woo_active = class_exists('WooCommerce');

global $wpdb;
$table = $wpdb->prefix . 'peanut_woo_orders';

// Get date range
$period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '30';
$start_date = date('Y-m-d', strtotime("-{$period} days"));

// Get attribution stats
$stats = [
    'total_revenue' => 0,
    'attributed_revenue' => 0,
    'total_orders' => 0,
    'attributed_orders' => 0,
];

if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table) {
    $stats['total_revenue'] = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(order_total), 0) FROM $table WHERE created_at >= %s",
        $start_date
    ));

    $stats['attributed_revenue'] = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(order_total), 0) FROM $table WHERE utm_source IS NOT NULL AND created_at >= %s",
        $start_date
    ));

    $stats['total_orders'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE created_at >= %s",
        $start_date
    ));

    $stats['attributed_orders'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE utm_source IS NOT NULL AND created_at >= %s",
        $start_date
    ));

    // Get revenue by source
    $by_source = $wpdb->get_results($wpdb->prepare(
        "SELECT utm_source, COUNT(*) as orders, SUM(order_total) as revenue
         FROM $table
         WHERE utm_source IS NOT NULL AND created_at >= %s
         GROUP BY utm_source
         ORDER BY revenue DESC",
        $start_date
    ), ARRAY_A) ?: [];

    // Get revenue by campaign
    $by_campaign = $wpdb->get_results($wpdb->prepare(
        "SELECT utm_campaign, COUNT(*) as orders, SUM(order_total) as revenue
         FROM $table
         WHERE utm_campaign IS NOT NULL AND created_at >= %s
         GROUP BY utm_campaign
         ORDER BY revenue DESC",
        $start_date
    ), ARRAY_A) ?: [];

    // Get recent attributed orders
    $recent_orders = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table
         WHERE utm_source IS NOT NULL
         ORDER BY created_at DESC LIMIT 10"
    ), ARRAY_A) ?: [];
}

$attribution_rate = $stats['total_orders'] > 0
    ? round(($stats['attributed_orders'] / $stats['total_orders']) * 100, 1)
    : 0;
?>

<div class="peanut-content">
    <?php if (!$woo_active): ?>
        <div class="peanut-notice peanut-notice-warning">
            <span class="dashicons dashicons-warning"></span>
            <div>
                <strong><?php esc_html_e('WooCommerce Not Detected', 'peanut-suite'); ?></strong>
                <p><?php esc_html_e('This module requires WooCommerce to be installed and active.', 'peanut-suite'); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Period Filter -->
    <div class="peanut-toolbar">
        <div class="peanut-period-filter">
            <label><?php esc_html_e('Period:', 'peanut-suite'); ?></label>
            <select id="period-filter" onchange="window.location.href='?page=peanut-woocommerce&period='+this.value">
                <option value="7" <?php selected($period, '7'); ?>><?php esc_html_e('Last 7 days', 'peanut-suite'); ?></option>
                <option value="30" <?php selected($period, '30'); ?>><?php esc_html_e('Last 30 days', 'peanut-suite'); ?></option>
                <option value="90" <?php selected($period, '90'); ?>><?php esc_html_e('Last 90 days', 'peanut-suite'); ?></option>
                <option value="365" <?php selected($period, '365'); ?>><?php esc_html_e('Last year', 'peanut-suite'); ?></option>
            </select>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="peanut-stats-grid">
        <div class="peanut-stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-chart-line"></span></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo peanut_format_price($stats['attributed_revenue']); ?></div>
                <div class="stat-label"><?php esc_html_e('Attributed Revenue', 'peanut-suite'); ?></div>
            </div>
        </div>
        <div class="peanut-stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-cart"></span></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['attributed_orders']); ?></div>
                <div class="stat-label"><?php esc_html_e('Attributed Orders', 'peanut-suite'); ?></div>
            </div>
        </div>
        <div class="peanut-stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-visibility"></span></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($attribution_rate); ?>%</div>
                <div class="stat-label"><?php esc_html_e('Attribution Rate', 'peanut-suite'); ?></div>
            </div>
        </div>
        <div class="peanut-stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-money-alt"></span></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['attributed_orders'] > 0 ? peanut_format_price($stats['attributed_revenue'] / $stats['attributed_orders']) : peanut_format_price(0); ?></div>
                <div class="stat-label"><?php esc_html_e('Avg Order Value', 'peanut-suite'); ?></div>
            </div>
        </div>
    </div>

    <div class="peanut-grid peanut-grid-2">
        <!-- Revenue by Source -->
        <div class="peanut-card">
            <div class="peanut-card-header">
                <h3><?php esc_html_e('Revenue by Source', 'peanut-suite'); ?></h3>
            </div>
            <div class="peanut-card-body">
                <?php if (!empty($by_source)): ?>
                    <table class="peanut-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Source', 'peanut-suite'); ?></th>
                                <th><?php esc_html_e('Orders', 'peanut-suite'); ?></th>
                                <th><?php esc_html_e('Revenue', 'peanut-suite'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($by_source as $row): ?>
                                <tr>
                                    <td>
                                        <span class="peanut-source-badge"><?php echo esc_html(ucfirst($row['utm_source'])); ?></span>
                                    </td>
                                    <td><?php echo esc_html($row['orders']); ?></td>
                                    <td><strong><?php echo peanut_format_price($row['revenue']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="peanut-empty-state">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <p><?php esc_html_e('No attributed revenue yet.', 'peanut-suite'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Revenue by Campaign -->
        <div class="peanut-card">
            <div class="peanut-card-header">
                <h3><?php esc_html_e('Revenue by Campaign', 'peanut-suite'); ?></h3>
            </div>
            <div class="peanut-card-body">
                <?php if (!empty($by_campaign)): ?>
                    <table class="peanut-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Campaign', 'peanut-suite'); ?></th>
                                <th><?php esc_html_e('Orders', 'peanut-suite'); ?></th>
                                <th><?php esc_html_e('Revenue', 'peanut-suite'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($by_campaign as $row): ?>
                                <tr>
                                    <td><?php echo esc_html($row['utm_campaign']); ?></td>
                                    <td><?php echo esc_html($row['orders']); ?></td>
                                    <td><strong><?php echo peanut_format_price($row['revenue']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="peanut-empty-state">
                        <span class="dashicons dashicons-megaphone"></span>
                        <p><?php esc_html_e('No campaign data yet.', 'peanut-suite'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Attributed Orders -->
    <div class="peanut-card">
        <div class="peanut-card-header">
            <h3><?php esc_html_e('Recent Attributed Orders', 'peanut-suite'); ?></h3>
        </div>
        <div class="peanut-card-body">
            <?php if (!empty($recent_orders)): ?>
                <table class="peanut-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Order', 'peanut-suite'); ?></th>
                            <th><?php esc_html_e('Amount', 'peanut-suite'); ?></th>
                            <th><?php esc_html_e('Source', 'peanut-suite'); ?></th>
                            <th><?php esc_html_e('Medium', 'peanut-suite'); ?></th>
                            <th><?php esc_html_e('Campaign', 'peanut-suite'); ?></th>
                            <th><?php esc_html_e('Date', 'peanut-suite'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $order['order_id'] . '&action=edit')); ?>">
                                        #<?php echo esc_html($order['order_id']); ?>
                                    </a>
                                </td>
                                <td><strong><?php echo peanut_format_price($order['order_total']); ?></strong></td>
                                <td><span class="peanut-source-badge"><?php echo esc_html($order['utm_source']); ?></span></td>
                                <td><?php echo esc_html($order['utm_medium'] ?: '-'); ?></td>
                                <td><?php echo esc_html($order['utm_campaign'] ?: '-'); ?></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($order['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="peanut-empty-state">
                    <span class="dashicons dashicons-cart"></span>
                    <p><?php esc_html_e('No attributed orders yet. Orders placed after visitors arrive via UTM-tagged links will appear here.', 'peanut-suite'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- How It Works -->
    <div class="peanut-card peanut-help-card">
        <div class="peanut-card-header">
            <h3><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e('How WooCommerce Attribution Works', 'peanut-suite'); ?></h3>
        </div>
        <div class="peanut-card-body">
            <div class="peanut-help-grid">
                <div class="help-item">
                    <span class="help-number">1</span>
                    <div>
                        <strong><?php esc_html_e('UTM Capture', 'peanut-suite'); ?></strong>
                        <p><?php esc_html_e('When visitors arrive via UTM-tagged links, their parameters are saved in a cookie.', 'peanut-suite'); ?></p>
                    </div>
                </div>
                <div class="help-item">
                    <span class="help-number">2</span>
                    <div>
                        <strong><?php esc_html_e('Order Attribution', 'peanut-suite'); ?></strong>
                        <p><?php esc_html_e('When they complete a purchase, UTM data is attached to the order.', 'peanut-suite'); ?></p>
                    </div>
                </div>
                <div class="help-item">
                    <span class="help-number">3</span>
                    <div>
                        <strong><?php esc_html_e('Revenue Tracking', 'peanut-suite'); ?></strong>
                        <p><?php esc_html_e('See exactly which campaigns and sources drive the most revenue.', 'peanut-suite'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

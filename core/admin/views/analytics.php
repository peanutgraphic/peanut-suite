<?php
/**
 * Analytics Dashboard Page
 *
 * Comprehensive analytics and insights.
 * Pro feature - requires Pro or Agency license.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get date range
$period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '30d';
$custom_start = isset($_GET['start']) ? sanitize_text_field($_GET['start']) : '';
$custom_end = isset($_GET['end']) ? sanitize_text_field($_GET['end']) : '';

// If custom dates are set, use custom period
if ($custom_start && $custom_end) {
    $period = 'custom';
}

$periods = [
    '7d' => __('Last 7 Days', 'peanut-suite'),
    '30d' => __('Last 30 Days', 'peanut-suite'),
    '90d' => __('Last 90 Days', 'peanut-suite'),
    'ytd' => __('Year to Date', 'peanut-suite'),
];

// Sample data (would come from database in real implementation)
$stats = [
    'visitors' => ['current' => 2847, 'previous' => 2534, 'change' => 12.3],
    'pageviews' => ['current' => 8923, 'previous' => 7845, 'change' => 13.7],
    'conversions' => ['current' => 156, 'previous' => 142, 'change' => 9.9],
    'conversion_rate' => ['current' => 5.48, 'previous' => 5.60, 'change' => -2.1],
];

// Generate timeline labels
$timeline_labels = [];
$days = $period === '7d' ? 7 : ($period === '30d' ? 30 : 90);
for ($i = $days - 1; $i >= 0; $i--) {
    $timeline_labels[] = date('M j', strtotime("-$i days"));
}
?>

<div class="peanut-analytics-page">

    <!-- Period Selector -->
    <div class="peanut-period-selector">
        <?php foreach ($periods as $key => $label): ?>
            <a href="<?php echo esc_url(add_query_arg(['period' => $key, 'start' => false, 'end' => false])); ?>"
               class="peanut-period-btn <?php echo $period === $key ? 'active' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>

        <span class="peanut-period-separator">|</span>

        <form method="get" class="peanut-date-inputs">
            <input type="hidden" name="page" value="peanut-analytics">
            <input type="hidden" name="period" value="custom">
            <input type="date" name="start" value="<?php echo esc_attr($custom_start ?: date('Y-m-d', strtotime('-30 days'))); ?>">
            <span><?php esc_html_e('to', 'peanut-suite'); ?></span>
            <input type="date" name="end" value="<?php echo esc_attr($custom_end ?: date('Y-m-d')); ?>">
            <button type="submit" class="button"><?php esc_html_e('Apply', 'peanut-suite'); ?></button>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="peanut-stats-row">
        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['visitors']['current']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Visitors', 'peanut-suite'); ?></span>
                <?php
                $change = $stats['visitors']['change'];
                $class = $change >= 0 ? 'positive' : 'negative';
                $arrow = $change >= 0 ? '&uarr;' : '&darr;';
                ?>
                <span class="peanut-stat-change <?php echo $class; ?>">
                    <?php echo $arrow . ' ' . abs($change); ?>%
                </span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-admin-page"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['pageviews']['current']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Pageviews', 'peanut-suite'); ?></span>
                <?php
                $change = $stats['pageviews']['change'];
                $class = $change >= 0 ? 'positive' : 'negative';
                $arrow = $change >= 0 ? '&uarr;' : '&darr;';
                ?>
                <span class="peanut-stat-change <?php echo $class; ?>">
                    <?php echo $arrow . ' ' . abs($change); ?>%
                </span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-flag"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['conversions']['current']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Conversions', 'peanut-suite'); ?></span>
                <?php
                $change = $stats['conversions']['change'];
                $class = $change >= 0 ? 'positive' : 'negative';
                $arrow = $change >= 0 ? '&uarr;' : '&darr;';
                ?>
                <span class="peanut-stat-change <?php echo $class; ?>">
                    <?php echo $arrow . ' ' . abs($change); ?>%
                </span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format($stats['conversion_rate']['current'], 2); ?>%</span>
                <span class="peanut-stat-label"><?php esc_html_e('Conversion Rate', 'peanut-suite'); ?></span>
                <?php
                $change = $stats['conversion_rate']['change'];
                $class = $change >= 0 ? 'positive' : 'negative';
                $arrow = $change >= 0 ? '&uarr;' : '&darr;';
                ?>
                <span class="peanut-stat-change <?php echo $class; ?>">
                    <?php echo $arrow . ' ' . abs($change); ?>%
                </span>
            </div>
        </div>
    </div>

    <!-- Main Chart -->
    <div class="peanut-card">
        <div class="peanut-card-header">
            <h3><?php esc_html_e('Traffic Overview', 'peanut-suite'); ?></h3>
            <div class="peanut-chart-legend">
                <span class="peanut-legend-item">
                    <span class="peanut-legend-color" style="background: #0073aa;"></span>
                    <?php esc_html_e('Visitors', 'peanut-suite'); ?>
                </span>
                <span class="peanut-legend-item">
                    <span class="peanut-legend-color" style="background: #00a0d2;"></span>
                    <?php esc_html_e('Pageviews', 'peanut-suite'); ?>
                </span>
            </div>
        </div>
        <div class="peanut-chart-container">
            <canvas id="traffic-chart" height="300"></canvas>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="peanut-analytics-grid">
        <!-- Traffic Sources -->
        <div class="peanut-card">
            <h3><?php esc_html_e('Traffic Sources', 'peanut-suite'); ?></h3>
            <div class="peanut-chart-container peanut-chart-sm">
                <canvas id="sources-chart" height="250"></canvas>
            </div>
            <table class="peanut-mini-table">
                <tbody>
                    <tr>
                        <td><span class="peanut-dot" style="background: #4285f4;"></span> Google</td>
                        <td class="text-right"><strong>42%</strong></td>
                    </tr>
                    <tr>
                        <td><span class="peanut-dot" style="background: #666;"></span> Direct</td>
                        <td class="text-right"><strong>28%</strong></td>
                    </tr>
                    <tr>
                        <td><span class="peanut-dot" style="background: #1877f2;"></span> Facebook</td>
                        <td class="text-right"><strong>15%</strong></td>
                    </tr>
                    <tr>
                        <td><span class="peanut-dot" style="background: #ea4335;"></span> Email</td>
                        <td class="text-right"><strong>10%</strong></td>
                    </tr>
                    <tr>
                        <td><span class="peanut-dot" style="background: #ccc;"></span> Other</td>
                        <td class="text-right"><strong>5%</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Device Breakdown -->
        <div class="peanut-card">
            <h3><?php esc_html_e('Devices', 'peanut-suite'); ?></h3>
            <div class="peanut-chart-container peanut-chart-sm">
                <canvas id="devices-chart" height="250"></canvas>
            </div>
            <table class="peanut-mini-table">
                <tbody>
                    <tr>
                        <td><span class="dashicons dashicons-desktop"></span> Desktop</td>
                        <td class="text-right"><strong>58%</strong></td>
                    </tr>
                    <tr>
                        <td><span class="dashicons dashicons-smartphone"></span> Mobile</td>
                        <td class="text-right"><strong>35%</strong></td>
                    </tr>
                    <tr>
                        <td><span class="dashicons dashicons-tablet"></span> Tablet</td>
                        <td class="text-right"><strong>7%</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Pages -->
    <div class="peanut-card">
        <h3><?php esc_html_e('Top Pages', 'peanut-suite'); ?></h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Page', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Pageviews', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Unique Visitors', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Avg. Time', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Bounce Rate', 'peanut-suite'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><a href="#"><?php echo esc_html(home_url('/')); ?></a></td>
                    <td>2,456</td>
                    <td>1,823</td>
                    <td>1:45</td>
                    <td>42%</td>
                </tr>
                <tr>
                    <td><a href="#"><?php echo esc_html(home_url('/pricing')); ?></a></td>
                    <td>1,234</td>
                    <td>987</td>
                    <td>2:30</td>
                    <td>28%</td>
                </tr>
                <tr>
                    <td><a href="#"><?php echo esc_html(home_url('/features')); ?></a></td>
                    <td>876</td>
                    <td>654</td>
                    <td>1:58</td>
                    <td>35%</td>
                </tr>
                <tr>
                    <td><a href="#"><?php echo esc_html(home_url('/blog')); ?></a></td>
                    <td>654</td>
                    <td>543</td>
                    <td>3:12</td>
                    <td>55%</td>
                </tr>
                <tr>
                    <td><a href="#"><?php echo esc_html(home_url('/contact')); ?></a></td>
                    <td>432</td>
                    <td>398</td>
                    <td>1:15</td>
                    <td>22%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Top Campaigns -->
    <div class="peanut-card">
        <h3><?php esc_html_e('Top Campaigns', 'peanut-suite'); ?></h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Campaign', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Source / Medium', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Visitors', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Conversions', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Conv. Rate', 'peanut-suite'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>summer-sale-2024</strong></td>
                    <td>google / cpc</td>
                    <td>456</td>
                    <td>34</td>
                    <td><span class="peanut-badge peanut-badge-success">7.5%</span></td>
                </tr>
                <tr>
                    <td><strong>newsletter-jan</strong></td>
                    <td>email / newsletter</td>
                    <td>234</td>
                    <td>28</td>
                    <td><span class="peanut-badge peanut-badge-success">12%</span></td>
                </tr>
                <tr>
                    <td><strong>fb-retargeting</strong></td>
                    <td>facebook / retargeting</td>
                    <td>187</td>
                    <td>15</td>
                    <td><span class="peanut-badge peanut-badge-info">8%</span></td>
                </tr>
                <tr>
                    <td><strong>linkedin-b2b</strong></td>
                    <td>linkedin / sponsored</td>
                    <td>145</td>
                    <td>8</td>
                    <td><span class="peanut-badge peanut-badge-info">5.5%</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Traffic overview chart
    if (typeof PeanutCharts !== 'undefined' && document.getElementById('traffic-chart')) {
        PeanutCharts.line('traffic-chart', {
            labels: <?php echo json_encode(array_slice($timeline_labels, -14)); ?>,
            datasets: [
                {
                    label: '<?php esc_html_e('Visitors', 'peanut-suite'); ?>',
                    data: [85, 92, 78, 95, 88, 105, 98, 112, 95, 108, 125, 118, 132, 128],
                    borderColor: '#0073aa',
                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                    fill: true
                },
                {
                    label: '<?php esc_html_e('Pageviews', 'peanut-suite'); ?>',
                    data: [245, 268, 212, 289, 256, 312, 298, 345, 278, 324, 378, 356, 412, 398],
                    borderColor: '#00a0d2',
                    backgroundColor: 'transparent',
                    fill: false
                }
            ]
        });
    }

    // Sources chart
    if (typeof PeanutCharts !== 'undefined' && document.getElementById('sources-chart')) {
        PeanutCharts.doughnut('sources-chart', {
            labels: ['Google', 'Direct', 'Facebook', 'Email', 'Other'],
            datasets: [{
                data: [42, 28, 15, 10, 5],
                backgroundColor: ['#4285f4', '#666', '#1877f2', '#ea4335', '#ccc']
            }]
        });
    }

    // Devices chart
    if (typeof PeanutCharts !== 'undefined' && document.getElementById('devices-chart')) {
        PeanutCharts.doughnut('devices-chart', {
            labels: ['Desktop', 'Mobile', 'Tablet'],
            datasets: [{
                data: [58, 35, 7],
                backgroundColor: ['#0073aa', '#00a0d2', '#7ad03a']
            }]
        });
    }
});
</script>


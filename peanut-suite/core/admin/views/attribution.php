<?php
/**
 * Attribution Analysis Page
 *
 * Multi-touch attribution modeling and analysis.
 * Pro feature - requires Pro or Agency license.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get date range
$end_date = current_time('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

if (isset($_GET['start'])) {
    $start_date = sanitize_text_field($_GET['start']);
}
if (isset($_GET['end'])) {
    $end_date = sanitize_text_field($_GET['end']);
}

// Get selected model
$model = isset($_GET['model']) ? sanitize_text_field($_GET['model']) : 'first_touch';

// Attribution models
$models = [
    'first_touch' => [
        'name' => __('First Touch', 'peanut-suite'),
        'description' => __('100% credit to the first touchpoint that introduced the visitor.', 'peanut-suite'),
        'icon' => 'dashicons-flag',
    ],
    'last_touch' => [
        'name' => __('Last Touch', 'peanut-suite'),
        'description' => __('100% credit to the last touchpoint before conversion.', 'peanut-suite'),
        'icon' => 'dashicons-awards',
    ],
    'linear' => [
        'name' => __('Linear', 'peanut-suite'),
        'description' => __('Equal credit distributed across all touchpoints.', 'peanut-suite'),
        'icon' => 'dashicons-editor-justify',
    ],
    'time_decay' => [
        'name' => __('Time Decay', 'peanut-suite'),
        'description' => __('More credit to touchpoints closer to conversion.', 'peanut-suite'),
        'icon' => 'dashicons-clock',
    ],
    'position_based' => [
        'name' => __('Position Based', 'peanut-suite'),
        'description' => __('40% to first, 40% to last, 20% distributed to middle.', 'peanut-suite'),
        'icon' => 'dashicons-chart-bar',
    ],
];

// Get sample data (would come from database in real implementation)
$channel_data = [
    ['channel' => 'Google / CPC', 'conversions' => 45, 'value' => 4500, 'contribution' => 28],
    ['channel' => 'Facebook / Social', 'conversions' => 32, 'value' => 2800, 'contribution' => 20],
    ['channel' => 'Email / Newsletter', 'conversions' => 28, 'value' => 3200, 'contribution' => 18],
    ['channel' => 'Direct', 'conversions' => 22, 'value' => 1800, 'contribution' => 14],
    ['channel' => 'Google / Organic', 'conversions' => 18, 'value' => 1500, 'contribution' => 11],
    ['channel' => 'LinkedIn / Social', 'conversions' => 15, 'value' => 1200, 'contribution' => 9],
];
?>

<div class="peanut-attribution-page">

    <!-- Filters -->
    <div class="peanut-filters-bar">
        <form method="get" class="peanut-filter-form">
            <input type="hidden" name="page" value="peanut-attribution">

            <div class="peanut-filter-group">
                <label><?php esc_html_e('Attribution Model', 'peanut-suite'); ?></label>
                <select name="model" id="attribution-model">
                    <?php foreach ($models as $key => $model_data): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($model, $key); ?>>
                            <?php echo esc_html($model_data['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="peanut-filter-group">
                <label><?php esc_html_e('Date Range', 'peanut-suite'); ?></label>
                <div class="peanut-date-range">
                    <input type="date" name="start" value="<?php echo esc_attr($start_date); ?>">
                    <span><?php esc_html_e('to', 'peanut-suite'); ?></span>
                    <input type="date" name="end" value="<?php echo esc_attr($end_date); ?>">
                </div>
            </div>

            <button type="submit" class="button"><?php esc_html_e('Apply', 'peanut-suite'); ?></button>
        </form>
    </div>

    <!-- Model Explanation -->
    <div class="peanut-model-explainer">
        <div class="peanut-model-icon">
            <span class="dashicons <?php echo esc_attr($models[$model]['icon']); ?>"></span>
        </div>
        <div class="peanut-model-info">
            <h3><?php echo esc_html($models[$model]['name']); ?> <?php esc_html_e('Attribution', 'peanut-suite'); ?></h3>
            <p><?php echo esc_html($models[$model]['description']); ?></p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="peanut-stats-row">
        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-flag"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value">160</span>
                <span class="peanut-stat-label"><?php esc_html_e('Total Conversions', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value">6</span>
                <span class="peanut-stat-label"><?php esc_html_e('Channels', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-randomize"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value">2.4</span>
                <span class="peanut-stat-label"><?php esc_html_e('Avg. Touchpoints', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value">$15,000</span>
                <span class="peanut-stat-label"><?php esc_html_e('Total Value', 'peanut-suite'); ?></span>
            </div>
        </div>
    </div>

    <!-- Channel Performance -->
    <div class="peanut-card">
        <h3><?php esc_html_e('Channel Performance', 'peanut-suite'); ?></h3>
        <div class="peanut-chart-container">
            <canvas id="channel-chart" height="300"></canvas>
        </div>
    </div>

    <!-- Channel Table -->
    <div class="peanut-card">
        <h3><?php esc_html_e('Attribution by Channel', 'peanut-suite'); ?></h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Channel', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Conversions', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Value', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Contribution %', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Visualization', 'peanut-suite'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($channel_data as $channel): ?>
                <tr>
                    <td><strong><?php echo esc_html($channel['channel']); ?></strong></td>
                    <td><?php echo number_format_i18n($channel['conversions']); ?></td>
                    <td>$<?php echo number_format_i18n($channel['value']); ?></td>
                    <td><?php echo esc_html($channel['contribution']); ?>%</td>
                    <td>
                        <div class="peanut-contribution-bar">
                            <div class="peanut-contribution-fill" style="width: <?php echo esc_attr($channel['contribution']); ?>%"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Model Comparison -->
    <div class="peanut-card">
        <h3><?php esc_html_e('Compare Attribution Models', 'peanut-suite'); ?></h3>
        <p class="peanut-card-description">
            <?php esc_html_e('See how different models attribute conversions to understand the complete customer journey.', 'peanut-suite'); ?>
        </p>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Channel', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('First Touch', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Last Touch', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Linear', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Time Decay', 'peanut-suite'); ?></th>
                    <th><?php esc_html_e('Position Based', 'peanut-suite'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Google / CPC</strong></td>
                    <td>52</td>
                    <td>38</td>
                    <td>45</td>
                    <td>42</td>
                    <td>47</td>
                </tr>
                <tr>
                    <td><strong>Facebook / Social</strong></td>
                    <td>28</td>
                    <td>35</td>
                    <td>32</td>
                    <td>33</td>
                    <td>30</td>
                </tr>
                <tr>
                    <td><strong>Email / Newsletter</strong></td>
                    <td>18</td>
                    <td>42</td>
                    <td>28</td>
                    <td>35</td>
                    <td>32</td>
                </tr>
                <tr>
                    <td><strong>Direct</strong></td>
                    <td>15</td>
                    <td>28</td>
                    <td>22</td>
                    <td>24</td>
                    <td>20</td>
                </tr>
                <tr>
                    <td><strong>Google / Organic</strong></td>
                    <td>25</td>
                    <td>10</td>
                    <td>18</td>
                    <td>14</td>
                    <td>18</td>
                </tr>
                <tr>
                    <td><strong>LinkedIn / Social</strong></td>
                    <td>22</td>
                    <td>7</td>
                    <td>15</td>
                    <td>12</td>
                    <td>13</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Understanding Attribution -->
    <div class="peanut-card peanut-tips-card">
        <h3><?php esc_html_e('Understanding Attribution Models', 'peanut-suite'); ?></h3>
        <div class="peanut-ways-grid">
            <div class="peanut-way-card <?php echo $model === 'first_touch' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-flag"></span>
                <h4><?php esc_html_e('First Touch', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('100% credit to the first touchpoint. Best for understanding brand awareness and discovery channels.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-way-card <?php echo $model === 'last_touch' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-awards"></span>
                <h4><?php esc_html_e('Last Touch', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('100% credit to the last touchpoint. Best for conversion-focused campaigns and direct response.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-way-card <?php echo $model === 'linear' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-editor-justify"></span>
                <h4><?php esc_html_e('Linear', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Equal credit to all touchpoints. Best for a balanced view when all channels contribute equally.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-way-card <?php echo $model === 'time_decay' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-clock"></span>
                <h4><?php esc_html_e('Time Decay', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('More credit to recent touchpoints. Best for short sales cycles and time-sensitive campaigns.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-way-card <?php echo $model === 'position_based' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-chart-bar"></span>
                <h4><?php esc_html_e('Position Based', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('40% first, 40% last, 20% middle. Best for understanding the complete customer journey.', 'peanut-suite'); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Auto-submit on model change
    $('#attribution-model').on('change', function() {
        $(this).closest('form').submit();
    });

    // Channel performance chart
    if (typeof PeanutCharts !== 'undefined' && document.getElementById('channel-chart')) {
        PeanutCharts.horizontalBar('channel-chart', {
            labels: ['Google / CPC', 'Facebook / Social', 'Email / Newsletter', 'Direct', 'Google / Organic', 'LinkedIn / Social'],
            datasets: [{
                label: '<?php esc_html_e('Conversions', 'peanut-suite'); ?>',
                data: [45, 32, 28, 22, 18, 15],
                backgroundColor: [
                    'rgba(66, 133, 244, 0.8)',
                    'rgba(24, 119, 242, 0.8)',
                    'rgba(234, 67, 53, 0.8)',
                    'rgba(102, 102, 102, 0.8)',
                    'rgba(52, 168, 83, 0.8)',
                    'rgba(10, 102, 194, 0.8)'
                ]
            }]
        });
    }
});
</script>


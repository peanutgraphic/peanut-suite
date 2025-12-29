<?php
/**
 * Form Analytics View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$forms_table = $wpdb->prefix . 'peanut_form_analytics';
$fields_table = $wpdb->prefix . 'peanut_form_fields';

// Get period
$period = isset($_GET['period']) ? intval($_GET['period']) : 30;
$start_date = date('Y-m-d', strtotime("-{$period} days"));

// Get form stats
$forms = [];
if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $forms_table)) === $forms_table) {
    $forms = $wpdb->get_results($wpdb->prepare(
        "SELECT form_id, form_name, form_plugin,
                SUM(views) as total_views,
                SUM(starts) as total_starts,
                SUM(submissions) as total_submissions,
                SUM(abandonments) as total_abandonments
         FROM $forms_table
         WHERE date >= %s
         GROUP BY form_id, form_name, form_plugin
         ORDER BY total_views DESC",
        $start_date
    ), ARRAY_A) ?: [];
}

// Calculate totals
$total_views = array_sum(array_column($forms, 'total_views'));
$total_starts = array_sum(array_column($forms, 'total_starts'));
$total_submissions = array_sum(array_column($forms, 'total_submissions'));
$total_abandonments = array_sum(array_column($forms, 'total_abandonments'));

$start_rate = $total_views > 0 ? round(($total_starts / $total_views) * 100, 1) : 0;
$submit_rate = $total_starts > 0 ? round(($total_submissions / $total_starts) * 100, 1) : 0;
$abandon_rate = $total_starts > 0 ? round(($total_abandonments / $total_starts) * 100, 1) : 0;

// Get field analytics for selected form
$selected_form = isset($_GET['form']) ? sanitize_text_field($_GET['form']) : '';
$field_stats = [];
if ($selected_form && $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $fields_table)) === $fields_table) {
    $field_stats = $wpdb->get_results($wpdb->prepare(
        "SELECT field_name, field_type,
                SUM(interactions) as total_interactions,
                SUM(completions) as total_completions,
                AVG(time_spent) as avg_time,
                SUM(dropoffs) as total_dropoffs
         FROM $fields_table
         WHERE form_id = %s AND date >= %s
         GROUP BY field_name, field_type
         ORDER BY field_order ASC",
        $selected_form, $start_date
    ), ARRAY_A) ?: [];
}

// Supported plugins
$plugins = [
    'wpforms' => ['name' => 'WPForms', 'active' => class_exists('WPForms')],
    'gravityforms' => ['name' => 'Gravity Forms', 'active' => class_exists('GFForms')],
    'cf7' => ['name' => 'Contact Form 7', 'active' => class_exists('WPCF7')],
    'ninja' => ['name' => 'Ninja Forms', 'active' => class_exists('Ninja_Forms')],
];
?>

<div class="peanut-content">
    <!-- Period Filter -->
    <div class="peanut-toolbar">
        <div class="peanut-period-filter">
            <label><?php esc_html_e('Period:', 'peanut-suite'); ?></label>
            <select id="period-filter" onchange="window.location.href='?page=peanut-forms&period='+this.value">
                <option value="7" <?php selected($period, 7); ?>><?php esc_html_e('Last 7 days', 'peanut-suite'); ?></option>
                <option value="30" <?php selected($period, 30); ?>><?php esc_html_e('Last 30 days', 'peanut-suite'); ?></option>
                <option value="90" <?php selected($period, 90); ?>><?php esc_html_e('Last 90 days', 'peanut-suite'); ?></option>
            </select>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="peanut-stats-grid">
        <div class="peanut-stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-visibility"></span></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html(number_format($total_views)); ?></div>
                <div class="stat-label"><?php esc_html_e('Form Views', 'peanut-suite'); ?></div>
            </div>
        </div>
        <div class="peanut-stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-edit"></span></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($start_rate); ?>%</div>
                <div class="stat-label"><?php esc_html_e('Start Rate', 'peanut-suite'); ?></div>
            </div>
        </div>
        <div class="peanut-stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($submit_rate); ?>%</div>
                <div class="stat-label"><?php esc_html_e('Completion Rate', 'peanut-suite'); ?></div>
            </div>
        </div>
        <div class="peanut-stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-dismiss"></span></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($abandon_rate); ?>%</div>
                <div class="stat-label"><?php esc_html_e('Abandonment Rate', 'peanut-suite'); ?></div>
            </div>
        </div>
    </div>

    <!-- Integrations Status -->
    <div class="peanut-card">
        <div class="peanut-card-header">
            <h3><?php esc_html_e('Form Plugin Integrations', 'peanut-suite'); ?></h3>
        </div>
        <div class="peanut-card-body">
            <div class="peanut-integrations-grid">
                <?php foreach ($plugins as $slug => $plugin): ?>
                    <div class="integration-item <?php echo $plugin['active'] ? 'active' : 'inactive'; ?>">
                        <span class="dashicons dashicons-<?php echo $plugin['active'] ? 'yes-alt' : 'marker'; ?>"></span>
                        <span><?php echo esc_html($plugin['name']); ?></span>
                        <span class="integration-status">
                            <?php echo $plugin['active']
                                ? esc_html__('Tracking', 'peanut-suite')
                                : esc_html__('Not Installed', 'peanut-suite'); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Forms Table -->
    <div class="peanut-card">
        <div class="peanut-card-header">
            <h3><?php esc_html_e('Form Performance', 'peanut-suite'); ?></h3>
        </div>
        <div class="peanut-card-body">
            <?php if (!empty($forms)): ?>
                <table class="peanut-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Form', 'peanut-suite'); ?></th>
                            <th><?php esc_html_e('Plugin', 'peanut-suite'); ?></th>
                            <th><?php esc_html_e('Views', 'peanut-suite'); ?></th>
                            <th><?php esc_html_e('Starts', 'peanut-suite'); ?></th>
                            <th><?php esc_html_e('Submissions', 'peanut-suite'); ?></th>
                            <th><?php esc_html_e('Completion %', 'peanut-suite'); ?></th>
                            <th><?php esc_html_e('Actions', 'peanut-suite'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($forms as $form):
                            $completion = $form['total_starts'] > 0
                                ? round(($form['total_submissions'] / $form['total_starts']) * 100, 1)
                                : 0;
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($form['form_name'] ?: 'Form #' . $form['form_id']); ?></strong></td>
                                <td>
                                    <span class="peanut-badge"><?php echo esc_html(ucfirst($form['form_plugin'])); ?></span>
                                </td>
                                <td><?php echo esc_html(number_format($form['total_views'])); ?></td>
                                <td><?php echo esc_html(number_format($form['total_starts'])); ?></td>
                                <td><?php echo esc_html(number_format($form['total_submissions'])); ?></td>
                                <td>
                                    <div class="peanut-progress-bar">
                                        <div class="progress-fill <?php echo $completion >= 50 ? 'good' : ($completion >= 25 ? 'medium' : 'low'); ?>"
                                             style="width: <?php echo $completion; ?>%"></div>
                                        <span class="progress-text"><?php echo $completion; ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <a href="?page=peanut-forms&form=<?php echo urlencode($form['form_id']); ?>&period=<?php echo $period; ?>"
                                       class="button button-small">
                                        <?php esc_html_e('Field Analysis', 'peanut-suite'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="peanut-empty-state">
                    <span class="dashicons dashicons-feedback"></span>
                    <h3><?php esc_html_e('No Form Data Yet', 'peanut-suite'); ?></h3>
                    <p><?php esc_html_e('Form analytics will appear here once visitors interact with your forms.', 'peanut-suite'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($field_stats)): ?>
    <!-- Field Analytics -->
    <div class="peanut-card">
        <div class="peanut-card-header">
            <h3><?php printf(__('Field Analysis: %s', 'peanut-suite'), esc_html($selected_form)); ?></h3>
            <a href="?page=peanut-forms&period=<?php echo $period; ?>" class="button button-small">
                <?php esc_html_e('Back to All Forms', 'peanut-suite'); ?>
            </a>
        </div>
        <div class="peanut-card-body">
            <table class="peanut-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Field', 'peanut-suite'); ?></th>
                        <th><?php esc_html_e('Type', 'peanut-suite'); ?></th>
                        <th><?php esc_html_e('Interactions', 'peanut-suite'); ?></th>
                        <th><?php esc_html_e('Completions', 'peanut-suite'); ?></th>
                        <th><?php esc_html_e('Avg Time', 'peanut-suite'); ?></th>
                        <th><?php esc_html_e('Drop-offs', 'peanut-suite'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($field_stats as $field): ?>
                        <tr>
                            <td><strong><?php echo esc_html($field['field_name']); ?></strong></td>
                            <td><span class="peanut-badge peanut-badge-secondary"><?php echo esc_html($field['field_type']); ?></span></td>
                            <td><?php echo esc_html(number_format($field['total_interactions'])); ?></td>
                            <td><?php echo esc_html(number_format($field['total_completions'])); ?></td>
                            <td><?php echo esc_html(round($field['avg_time'], 1)); ?>s</td>
                            <td>
                                <?php if ($field['total_dropoffs'] > 0): ?>
                                    <span class="peanut-badge peanut-badge-warning"><?php echo esc_html($field['total_dropoffs']); ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- How It Works -->
    <div class="peanut-card peanut-help-card">
        <div class="peanut-card-header">
            <h3><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e('How Form Analytics Works', 'peanut-suite'); ?></h3>
        </div>
        <div class="peanut-card-body">
            <div class="peanut-help-grid">
                <div class="help-item">
                    <span class="help-number">1</span>
                    <div>
                        <strong><?php esc_html_e('Automatic Tracking', 'peanut-suite'); ?></strong>
                        <p><?php esc_html_e('Forms are detected and tracked automatically when visitors view and interact with them.', 'peanut-suite'); ?></p>
                    </div>
                </div>
                <div class="help-item">
                    <span class="help-number">2</span>
                    <div>
                        <strong><?php esc_html_e('Field-Level Insights', 'peanut-suite'); ?></strong>
                        <p><?php esc_html_e('See which fields cause users to abandon, and how long they spend on each.', 'peanut-suite'); ?></p>
                    </div>
                </div>
                <div class="help-item">
                    <span class="help-number">3</span>
                    <div>
                        <strong><?php esc_html_e('Optimize Conversions', 'peanut-suite'); ?></strong>
                        <p><?php esc_html_e('Use insights to remove friction and improve your form completion rates.', 'peanut-suite'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.peanut-integrations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}
.integration-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: #f9f9f9;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
}
.integration-item.active {
    background: #d4edda;
    border-color: #c3e6cb;
}
.integration-item.active .dashicons {
    color: #28a745;
}
.integration-item.inactive .dashicons {
    color: #999;
}
.integration-status {
    margin-left: auto;
    font-size: 12px;
    color: #666;
}
.peanut-progress-bar {
    position: relative;
    background: #e9ecef;
    border-radius: 4px;
    height: 20px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s;
}
.progress-fill.good { background: #28a745; }
.progress-fill.medium { background: #ffc107; }
.progress-fill.low { background: #dc3545; }
.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 11px;
    font-weight: 600;
    color: #333;
}
</style>

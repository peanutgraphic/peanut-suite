<?php
/**
 * Backlink Discovery View
 *
 * Monitor and track sites linking to you.
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . PEANUT_TABLE_PREFIX . 'backlinks';

// Check if table exists
$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table;

// Handle actions
if (isset($_POST['action']) && $_POST['action'] === 'add_backlink' && wp_verify_nonce($_POST['_wpnonce'], 'peanut_add_backlink')) {
    $source_url = esc_url_raw($_POST['source_url']);
    $target_url = esc_url_raw($_POST['target_url'] ?? home_url());
    $anchor_text = sanitize_text_field($_POST['anchor_text'] ?? '');
    $link_type = in_array($_POST['link_type'], ['dofollow', 'nofollow', 'ugc', 'sponsored']) ? $_POST['link_type'] : 'dofollow';
    $notes = sanitize_textarea_field($_POST['notes'] ?? '');

    if ($source_url && $table_exists) {
        $source_domain = wp_parse_url($source_url, PHP_URL_HOST);

        $result = $wpdb->insert($table, [
            'source_url' => $source_url,
            'source_domain' => $source_domain,
            'target_url' => $target_url ?: home_url(),
            'anchor_text' => $anchor_text,
            'link_type' => $link_type,
            'status' => 'pending',
            'discovery_source' => 'manual',
            'notes' => $notes,
            'first_seen' => current_time('mysql'),
        ]);

        if ($result) {
            echo '<div class="notice notice-success"><p>' . esc_html__('Backlink added successfully.', 'peanut-suite') . '</p></div>';
        }
    }
}

// Get stats
$stats = [
    'total' => 0,
    'active' => 0,
    'lost' => 0,
    'dofollow' => 0,
    'unique_domains' => 0,
    'new_30_days' => 0,
];

if ($table_exists) {
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from trusted source
    $stats_row = $wpdb->get_row(
        "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost,
            SUM(CASE WHEN link_type = 'dofollow' AND status = 'active' THEN 1 ELSE 0 END) as dofollow,
            COUNT(DISTINCT source_domain) as unique_domains,
            SUM(CASE WHEN first_seen >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_30_days
        FROM " . esc_sql($table),
        ARRAY_A
    );
    $stats = array_merge($stats, $stats_row ?: []);
}

// Get backlinks
$status_filter = sanitize_key($_GET['status'] ?? '');
$per_page = 25;
$current_page = max(1, (int) ($_GET['paged'] ?? 1));
$offset = ($current_page - 1) * $per_page;

$where = '1=1';
if ($status_filter) {
    $where = $wpdb->prepare('status = %s', $status_filter);
}

$backlinks = [];
$total = 0;
if ($table_exists) {
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from trusted source, $where already prepared
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . esc_sql($table) . " WHERE $where");
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from trusted source, $where already prepared
    $backlinks = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM " . esc_sql($table) . " WHERE $where ORDER BY first_seen DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );
}

$total_pages = ceil($total / $per_page);

// Settings
$settings = get_option('peanut_backlinks_settings', [
    'alert_on_lost' => true,
    'alert_email' => get_option('admin_email'),
    'auto_verify_days' => 7,
]);
?>

<div class="peanut-page-header">
    <div>
        <h1><?php esc_html_e('Backlink Discovery', 'peanut-suite'); ?></h1>
        <p class="peanut-page-description"><?php esc_html_e('Monitor and track sites linking to your website.', 'peanut-suite'); ?></p>
    </div>
    <div class="peanut-page-actions">
        <button type="button" class="button" id="peanut-verify-backlinks">
            <span class="dashicons dashicons-update"></span>
            <?php esc_html_e('Verify All', 'peanut-suite'); ?>
        </button>
        <button type="button" class="button button-primary" data-peanut-modal="add-backlink-modal">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Add Backlink', 'peanut-suite'); ?>
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="peanut-stats-grid">
    <div class="peanut-stat-card">
        <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
        <div class="stat-label"><?php esc_html_e('Total Backlinks', 'peanut-suite'); ?></div>
    </div>
    <div class="peanut-stat-card">
        <div class="stat-value" style="color: #10b981;"><?php echo number_format($stats['active']); ?></div>
        <div class="stat-label"><?php esc_html_e('Active', 'peanut-suite'); ?></div>
    </div>
    <div class="peanut-stat-card">
        <div class="stat-value" style="color: #ef4444;"><?php echo number_format($stats['lost']); ?></div>
        <div class="stat-label"><?php esc_html_e('Lost', 'peanut-suite'); ?></div>
    </div>
    <div class="peanut-stat-card">
        <div class="stat-value"><?php echo number_format($stats['unique_domains']); ?></div>
        <div class="stat-label"><?php esc_html_e('Referring Domains', 'peanut-suite'); ?></div>
    </div>
    <div class="peanut-stat-card">
        <div class="stat-value"><?php echo number_format($stats['dofollow']); ?></div>
        <div class="stat-label"><?php esc_html_e('Dofollow Links', 'peanut-suite'); ?></div>
    </div>
    <div class="peanut-stat-card">
        <div class="stat-value" style="color: #3b82f6;"><?php echo number_format($stats['new_30_days']); ?></div>
        <div class="stat-label"><?php esc_html_e('New (30 days)', 'peanut-suite'); ?></div>
    </div>
</div>

<!-- Filters -->
<div class="peanut-filters">
    <form method="get">
        <input type="hidden" name="page" value="peanut-backlinks">
        <select name="status" onchange="this.form.submit()">
            <option value=""><?php esc_html_e('All Statuses', 'peanut-suite'); ?></option>
            <option value="active" <?php selected($status_filter, 'active'); ?>><?php esc_html_e('Active', 'peanut-suite'); ?></option>
            <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php esc_html_e('Pending Verification', 'peanut-suite'); ?></option>
            <option value="lost" <?php selected($status_filter, 'lost'); ?>><?php esc_html_e('Lost', 'peanut-suite'); ?></option>
            <option value="broken" <?php selected($status_filter, 'broken'); ?>><?php esc_html_e('Broken', 'peanut-suite'); ?></option>
        </select>
    </form>
</div>

<!-- Backlinks Table -->
<div class="peanut-card" style="margin-top: 24px;">
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 35%;"><?php esc_html_e('Source Page', 'peanut-suite'); ?></th>
                <th style="width: 20%;"><?php esc_html_e('Anchor Text', 'peanut-suite'); ?></th>
                <th style="width: 10%;"><?php esc_html_e('Type', 'peanut-suite'); ?></th>
                <th style="width: 10%;"><?php esc_html_e('Status', 'peanut-suite'); ?></th>
                <th style="width: 15%;"><?php esc_html_e('First Seen', 'peanut-suite'); ?></th>
                <th style="width: 10%;"><?php esc_html_e('Actions', 'peanut-suite'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($backlinks)): ?>
                <tr>
                    <td colspan="6">
                        <div class="peanut-empty-state">
                            <span class="dashicons dashicons-admin-links" style="font-size: 48px; width: 48px; height: 48px; color: #94a3b8;"></span>
                            <h3><?php esc_html_e('No backlinks found', 'peanut-suite'); ?></h3>
                            <p><?php esc_html_e('Add backlinks manually or wait for automatic discovery from your referrer traffic.', 'peanut-suite'); ?></p>
                            <button type="button" class="button button-primary" data-peanut-modal="add-backlink-modal">
                                <?php esc_html_e('Add Your First Backlink', 'peanut-suite'); ?>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($backlinks as $backlink): ?>
                    <tr>
                        <td>
                            <div class="backlink-source">
                                <strong><?php echo esc_html($backlink->source_domain); ?></strong>
                                <br>
                                <a href="<?php echo esc_url($backlink->source_url); ?>" target="_blank" class="row-url">
                                    <?php echo esc_html(strlen($backlink->source_url) > 60 ? substr($backlink->source_url, 0, 60) . '...' : $backlink->source_url); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </div>
                        </td>
                        <td>
                            <?php if ($backlink->anchor_text): ?>
                                <code><?php echo esc_html($backlink->anchor_text); ?></code>
                            <?php else: ?>
                                <span class="text-muted"><?php esc_html_e('Not verified', 'peanut-suite'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $type_badges = [
                                'dofollow' => 'background: #dcfce7; color: #166534;',
                                'nofollow' => 'background: #fef3c7; color: #92400e;',
                                'ugc' => 'background: #e0e7ff; color: #3730a3;',
                                'sponsored' => 'background: #fce7f3; color: #9d174d;',
                            ];
                            $style = $type_badges[$backlink->link_type] ?? '';
                            ?>
                            <span class="peanut-badge" style="<?php echo esc_attr($style); ?>">
                                <?php echo esc_html($backlink->link_type); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $status_badges = [
                                'active' => 'background: #dcfce7; color: #166534;',
                                'pending' => 'background: #e0e7ff; color: #3730a3;',
                                'lost' => 'background: #fee2e2; color: #991b1b;',
                                'broken' => 'background: #fef3c7; color: #92400e;',
                            ];
                            $style = $status_badges[$backlink->status] ?? '';
                            ?>
                            <span class="peanut-badge" style="<?php echo esc_attr($style); ?>">
                                <?php echo esc_html(ucfirst($backlink->status)); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo esc_html(human_time_diff(strtotime($backlink->first_seen))); ?> ago
                            <?php if ($backlink->last_checked): ?>
                                <br>
                                <small class="text-muted">
                                    <?php printf(esc_html__('Verified: %s', 'peanut-suite'), human_time_diff(strtotime($backlink->last_checked)) . ' ago'); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="<?php echo esc_url($backlink->source_url); ?>" target="_blank" title="<?php esc_attr_e('Visit', 'peanut-suite'); ?>">
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                                <button type="button" class="button-link peanut-delete-backlink" data-id="<?php echo esc_attr($backlink->id); ?>" title="<?php esc_attr_e('Delete', 'peanut-suite'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                $pagination_args = [
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ];
                echo paginate_links($pagination_args);
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Backlink Modal -->
<div id="add-backlink-modal" class="peanut-modal-backdrop">
    <div class="peanut-modal" style="max-width: 500px;">
        <div class="peanut-modal-header">
            <h3><?php esc_html_e('Add Backlink', 'peanut-suite'); ?></h3>
            <button type="button" class="peanut-modal-close">&times;</button>
        </div>
        <div class="peanut-modal-body">
            <form method="post">
                <?php wp_nonce_field('peanut_add_backlink'); ?>
                <input type="hidden" name="action" value="add_backlink">

                <div class="peanut-form-row">
                    <label class="peanut-form-label" for="source_url"><?php esc_html_e('Source URL', 'peanut-suite'); ?> *</label>
                    <input type="url" id="source_url" name="source_url" class="peanut-form-input" required
                           placeholder="https://example.com/page-linking-to-you">
                    <?php echo peanut_field_help(__('The page that contains a link to your website.', 'peanut-suite')); ?>
                </div>

                <div class="peanut-form-row">
                    <label class="peanut-form-label" for="target_url"><?php esc_html_e('Target URL', 'peanut-suite'); ?></label>
                    <input type="url" id="target_url" name="target_url" class="peanut-form-input"
                           value="<?php echo esc_url(home_url()); ?>"
                           placeholder="<?php echo esc_url(home_url()); ?>">
                    <?php echo peanut_field_help(__('The page on your site being linked to. Defaults to your homepage.', 'peanut-suite')); ?>
                </div>

                <div class="peanut-form-row">
                    <label class="peanut-form-label" for="anchor_text"><?php esc_html_e('Anchor Text', 'peanut-suite'); ?></label>
                    <input type="text" id="anchor_text" name="anchor_text" class="peanut-form-input"
                           placeholder="<?php esc_attr_e('e.g., click here, brand name', 'peanut-suite'); ?>">
                </div>

                <div class="peanut-form-row">
                    <label class="peanut-form-label" for="link_type"><?php esc_html_e('Link Type', 'peanut-suite'); ?></label>
                    <select id="link_type" name="link_type" class="peanut-form-select">
                        <option value="dofollow"><?php esc_html_e('Dofollow', 'peanut-suite'); ?></option>
                        <option value="nofollow"><?php esc_html_e('Nofollow', 'peanut-suite'); ?></option>
                        <option value="ugc"><?php esc_html_e('UGC (User Generated Content)', 'peanut-suite'); ?></option>
                        <option value="sponsored"><?php esc_html_e('Sponsored', 'peanut-suite'); ?></option>
                    </select>
                </div>

                <div class="peanut-form-row">
                    <label class="peanut-form-label" for="notes"><?php esc_html_e('Notes', 'peanut-suite'); ?></label>
                    <textarea id="notes" name="notes" class="peanut-form-textarea" rows="2"
                              placeholder="<?php esc_attr_e('Optional notes about this backlink...', 'peanut-suite'); ?>"></textarea>
                </div>

                <div class="peanut-form-actions">
                    <button type="button" class="button" data-dismiss="modal"><?php esc_html_e('Cancel', 'peanut-suite'); ?></button>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Add Backlink', 'peanut-suite'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Verify all backlinks
    $('#peanut-verify-backlinks').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e('Verifying...', 'peanut-suite'); ?>');

        $.ajax({
            url: '<?php echo esc_url(rest_url(PEANUT_API_NAMESPACE . '/backlinks/verify')); ?>',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                PeanutAdmin.showNotice('success', response.message);
                setTimeout(function() {
                    location.reload();
                }, 2000);
            },
            error: function() {
                PeanutAdmin.showNotice('error', '<?php esc_html_e('Verification failed', 'peanut-suite'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> <?php esc_html_e('Verify All', 'peanut-suite'); ?>');
            }
        });
    });

    // Delete backlink
    $('.peanut-delete-backlink').on('click', function() {
        if (!confirm('<?php esc_html_e('Delete this backlink record?', 'peanut-suite'); ?>')) {
            return;
        }

        var $btn = $(this);
        var id = $btn.data('id');

        $.ajax({
            url: '<?php echo esc_url(rest_url(PEANUT_API_NAMESPACE . '/backlinks/')); ?>' + id,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function() {
                $btn.closest('tr').fadeOut(function() {
                    $(this).remove();
                });
            },
            error: function() {
                PeanutAdmin.showNotice('error', '<?php esc_html_e('Failed to delete', 'peanut-suite'); ?>');
            }
        });
    });
});
</script>

<style>
.peanut-stats-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.backlink-source {
    line-height: 1.4;
}
.backlink-source a {
    color: #64748b;
    text-decoration: none;
    font-size: 12px;
}
.backlink-source a:hover {
    color: #f97316;
}
.row-url .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
    vertical-align: middle;
}
.row-actions {
    display: flex;
    gap: 8px;
}
.row-actions .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #64748b;
}
.row-actions .dashicons:hover {
    color: #f97316;
}
.text-muted {
    color: #94a3b8;
    font-style: italic;
}
.spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
@media (max-width: 1200px) {
    .peanut-stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (max-width: 768px) {
    .peanut-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

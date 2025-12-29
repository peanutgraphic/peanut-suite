<?php
/**
 * Invoices List View
 *
 * List and manage all invoices.
 * Agency feature - requires Agency license.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check Stripe configuration
$settings = get_option('peanut_settings', []);
$stripe_configured = !empty($settings['stripe_secret_key']);

// Get stats
require_once PEANUT_PLUGIN_DIR . 'modules/invoicing/class-invoicing-database.php';
$stats = Invoicing_Database::get_stats();

// Get filter status
$current_status = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';

// Get invoices
$invoices = Invoicing_Database::get_all([
    'status' => $current_status,
    'limit' => 50,
]);

// Status badges
$status_colors = [
    'draft' => 'neutral',
    'sent' => 'info',
    'paid' => 'success',
    'overdue' => 'danger',
    'voided' => 'neutral',
    'payment_failed' => 'danger',
];
?>

<div class="peanut-page-header">
    <div>
        <h1><?php esc_html_e('Invoicing', 'peanut-suite'); ?></h1>
        <p class="peanut-page-description"><?php esc_html_e('Create and manage invoices for your clients. Powered by Stripe.', 'peanut-suite'); ?></p>
    </div>
    <div>
        <?php if ($stripe_configured): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-invoicing&view=create')); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt2" style="margin-top: 3px;"></span>
                <?php esc_html_e('Create Invoice', 'peanut-suite'); ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$stripe_configured): ?>
    <!-- Stripe Setup Required -->
    <div class="peanut-card">
        <div class="peanut-empty-state">
            <span class="dashicons dashicons-money-alt" style="font-size: 48px; width: 48px; height: 48px; color: #6772e5;"></span>
            <h3><?php esc_html_e('Connect Stripe to Get Started', 'peanut-suite'); ?></h3>
            <p><?php esc_html_e('Add your Stripe API keys in Settings to create and send professional invoices.', 'peanut-suite'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-settings&tab=integrations')); ?>" class="button button-primary">
                <?php esc_html_e('Configure Stripe', 'peanut-suite'); ?>
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Stats Cards -->
    <div class="peanut-stats-row">
        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: #dbeafe;">
                <span class="dashicons dashicons-media-text" style="color: #3b82f6;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['draft']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Drafts', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: #fef3c7;">
                <span class="dashicons dashicons-email" style="color: #f59e0b;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['sent']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Sent', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: #d1fae5;">
                <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['paid']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Paid', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: #fee2e2;">
                <span class="dashicons dashicons-warning" style="color: #ef4444;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['overdue']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Overdue', 'peanut-suite'); ?></span>
            </div>
        </div>
    </div>

    <!-- Revenue Stats -->
    <div class="peanut-stats-row" style="margin-top: 16px;">
        <div class="peanut-stat-card" style="flex: 1;">
            <div class="peanut-stat-content" style="text-align: center;">
                <span class="peanut-stat-value" style="color: #10b981; font-size: 28px;">
                    $<?php echo number_format($stats['total_paid'], 2); ?>
                </span>
                <span class="peanut-stat-label"><?php esc_html_e('Total Paid', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card" style="flex: 1;">
            <div class="peanut-stat-content" style="text-align: center;">
                <span class="peanut-stat-value" style="color: #f59e0b; font-size: 28px;">
                    $<?php echo number_format($stats['total_outstanding'], 2); ?>
                </span>
                <span class="peanut-stat-label"><?php esc_html_e('Outstanding', 'peanut-suite'); ?></span>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="peanut-filter-tabs" style="margin-top: 24px;">
        <a href="<?php echo esc_url(remove_query_arg('status')); ?>"
           class="<?php echo empty($current_status) ? 'active' : ''; ?>">
            <?php esc_html_e('All', 'peanut-suite'); ?>
            <span class="count"><?php echo number_format_i18n($stats['total']); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('status', 'draft')); ?>"
           class="<?php echo $current_status === 'draft' ? 'active' : ''; ?>">
            <?php esc_html_e('Drafts', 'peanut-suite'); ?>
            <span class="count"><?php echo number_format_i18n($stats['draft']); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('status', 'sent')); ?>"
           class="<?php echo $current_status === 'sent' ? 'active' : ''; ?>">
            <?php esc_html_e('Sent', 'peanut-suite'); ?>
            <span class="count"><?php echo number_format_i18n($stats['sent']); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('status', 'paid')); ?>"
           class="<?php echo $current_status === 'paid' ? 'active' : ''; ?>">
            <?php esc_html_e('Paid', 'peanut-suite'); ?>
            <span class="count"><?php echo number_format_i18n($stats['paid']); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('status', 'overdue')); ?>"
           class="<?php echo $current_status === 'overdue' ? 'active' : ''; ?>">
            <?php esc_html_e('Overdue', 'peanut-suite'); ?>
            <span class="count"><?php echo number_format_i18n($stats['overdue']); ?></span>
        </a>
    </div>

    <!-- Invoices Table -->
    <div class="peanut-card" style="margin-top: 16px; padding: 0;">
        <?php if (empty($invoices)): ?>
            <div class="peanut-empty-state" style="padding: 60px;">
                <span class="dashicons dashicons-media-spreadsheet" style="font-size: 48px; width: 48px; height: 48px; color: #94a3b8;"></span>
                <h3><?php esc_html_e('No invoices yet', 'peanut-suite'); ?></h3>
                <p><?php esc_html_e('Create your first invoice to start getting paid by clients.', 'peanut-suite'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-invoicing&view=create')); ?>" class="button button-primary">
                    <?php esc_html_e('Create Invoice', 'peanut-suite'); ?>
                </a>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 120px;"><?php esc_html_e('Invoice #', 'peanut-suite'); ?></th>
                        <th><?php esc_html_e('Client', 'peanut-suite'); ?></th>
                        <th style="width: 100px;"><?php esc_html_e('Amount', 'peanut-suite'); ?></th>
                        <th style="width: 100px;"><?php esc_html_e('Status', 'peanut-suite'); ?></th>
                        <th style="width: 100px;"><?php esc_html_e('Due Date', 'peanut-suite'); ?></th>
                        <th style="width: 120px;"><?php esc_html_e('Created', 'peanut-suite'); ?></th>
                        <th style="width: 150px;"><?php esc_html_e('Actions', 'peanut-suite'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-invoicing&view=detail&id=' . $invoice['id'])); ?>">
                                    <strong><?php echo esc_html($invoice['invoice_number']); ?></strong>
                                </a>
                            </td>
                            <td>
                                <div><?php echo esc_html($invoice['client_name']); ?></div>
                                <div style="color: #64748b; font-size: 12px;"><?php echo esc_html($invoice['client_email']); ?></div>
                            </td>
                            <td>
                                <strong>$<?php echo number_format($invoice['total'], 2); ?></strong>
                                <div style="color: #64748b; font-size: 11px;"><?php echo esc_html($invoice['currency']); ?></div>
                            </td>
                            <td>
                                <span class="peanut-badge peanut-badge-<?php echo esc_attr($status_colors[$invoice['status']] ?? 'neutral'); ?>">
                                    <?php echo esc_html(ucfirst($invoice['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($invoice['due_date']): ?>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($invoice['due_date']))); ?>
                                <?php else: ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($invoice['created_at']))); ?>
                            </td>
                            <td>
                                <div class="peanut-row-actions">
                                    <?php if ($invoice['status'] === 'draft'): ?>
                                        <button type="button" class="button button-small peanut-send-invoice" data-id="<?php echo esc_attr($invoice['id']); ?>">
                                            <?php esc_html_e('Send', 'peanut-suite'); ?>
                                        </button>
                                    <?php endif; ?>

                                    <?php if (in_array($invoice['status'], ['sent', 'overdue'])): ?>
                                        <?php if ($invoice['payment_url']): ?>
                                            <a href="<?php echo esc_url($invoice['payment_url']); ?>" target="_blank" class="button button-small">
                                                <?php esc_html_e('Pay Link', 'peanut-suite'); ?>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" class="button button-small peanut-mark-paid" data-id="<?php echo esc_attr($invoice['id']); ?>">
                                            <?php esc_html_e('Mark Paid', 'peanut-suite'); ?>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($invoice['pdf_url']): ?>
                                        <a href="<?php echo esc_url($invoice['pdf_url']); ?>" target="_blank" class="button button-small" title="<?php esc_attr_e('Download PDF', 'peanut-suite'); ?>">
                                            <span class="dashicons dashicons-pdf" style="margin-top: 3px;"></span>
                                        </a>
                                    <?php endif; ?>

                                    <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-invoicing&view=detail&id=' . $invoice['id'])); ?>" class="button button-small">
                                        <?php esc_html_e('View', 'peanut-suite'); ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
jQuery(document).ready(function($) {
    // Send invoice
    $('.peanut-send-invoice').on('click', function() {
        var $btn = $(this);
        var id = $btn.data('id');

        if (!confirm('<?php esc_html_e('Send this invoice to the client?', 'peanut-suite'); ?>')) {
            return;
        }

        $btn.prop('disabled', true).text('<?php esc_html_e('Sending...', 'peanut-suite'); ?>');

        $.ajax({
            url: '<?php echo esc_url(rest_url(PEANUT_API_NAMESPACE . '/invoices/')); ?>' + id + '/send',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                PeanutAdmin.notify('<?php esc_html_e('Invoice sent successfully!', 'peanut-suite'); ?>', 'success');
                location.reload();
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.error || '<?php esc_html_e('Failed to send invoice', 'peanut-suite'); ?>';
                PeanutAdmin.notify(msg, 'error');
                $btn.prop('disabled', false).text('<?php esc_html_e('Send', 'peanut-suite'); ?>');
            }
        });
    });

    // Mark as paid
    $('.peanut-mark-paid').on('click', function() {
        var $btn = $(this);
        var id = $btn.data('id');

        if (!confirm('<?php esc_html_e('Mark this invoice as paid (outside of Stripe)?', 'peanut-suite'); ?>')) {
            return;
        }

        $btn.prop('disabled', true).text('<?php esc_html_e('Updating...', 'peanut-suite'); ?>');

        $.ajax({
            url: '<?php echo esc_url(rest_url(PEANUT_API_NAMESPACE . '/invoices/')); ?>' + id + '/mark-paid',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                PeanutAdmin.notify('<?php esc_html_e('Invoice marked as paid!', 'peanut-suite'); ?>', 'success');
                location.reload();
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.error || '<?php esc_html_e('Failed to update invoice', 'peanut-suite'); ?>';
                PeanutAdmin.notify(msg, 'error');
                $btn.prop('disabled', false).text('<?php esc_html_e('Mark Paid', 'peanut-suite'); ?>');
            }
        });
    });
});
</script>

<?php
/**
 * Invoice Detail View
 *
 * View and manage a single invoice.
 * Agency feature - requires Agency license.
 */

if (!defined('ABSPATH')) {
    exit;
}

$invoice_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

if (!$invoice_id) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Invoice not found.', 'peanut-suite') . '</p></div>';
    return;
}

require_once PEANUT_PLUGIN_DIR . 'modules/invoicing/class-invoicing-database.php';
$invoice = Invoicing_Database::get($invoice_id);

if (!$invoice) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Invoice not found.', 'peanut-suite') . '</p></div>';
    return;
}

// Status colors
$status_colors = [
    'draft' => 'neutral',
    'sent' => 'info',
    'paid' => 'success',
    'overdue' => 'danger',
    'voided' => 'neutral',
    'payment_failed' => 'danger',
];

$status_color = $status_colors[$invoice['status']] ?? 'neutral';
?>

<div class="peanut-page-header">
    <div>
        <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-invoicing')); ?>" class="peanut-back-link">
            <span class="dashicons dashicons-arrow-left-alt"></span>
            <?php esc_html_e('Back to Invoices', 'peanut-suite'); ?>
        </a>
        <h1>
            <?php echo esc_html($invoice['invoice_number']); ?>
            <span class="peanut-badge peanut-badge-<?php echo esc_attr($status_color); ?>" style="font-size: 14px; vertical-align: middle; margin-left: 12px;">
                <?php echo esc_html(ucfirst($invoice['status'])); ?>
            </span>
        </h1>
    </div>
    <div class="peanut-header-actions">
        <?php if ($invoice['status'] === 'draft'): ?>
            <button type="button" class="button button-primary" id="send-invoice">
                <span class="dashicons dashicons-email" style="margin-top: 3px;"></span>
                <?php esc_html_e('Send Invoice', 'peanut-suite'); ?>
            </button>
            <button type="button" class="button" id="delete-invoice" style="color: #dc2626;">
                <?php esc_html_e('Delete', 'peanut-suite'); ?>
            </button>
        <?php endif; ?>

        <?php if (in_array($invoice['status'], ['sent', 'overdue'])): ?>
            <?php if ($invoice['payment_url']): ?>
                <a href="<?php echo esc_url($invoice['payment_url']); ?>" target="_blank" class="button button-primary">
                    <span class="dashicons dashicons-external" style="margin-top: 3px;"></span>
                    <?php esc_html_e('Payment Link', 'peanut-suite'); ?>
                </a>
            <?php endif; ?>
            <button type="button" class="button" id="mark-paid">
                <?php esc_html_e('Mark as Paid', 'peanut-suite'); ?>
            </button>
            <button type="button" class="button" id="void-invoice" style="color: #dc2626;">
                <?php esc_html_e('Void Invoice', 'peanut-suite'); ?>
            </button>
        <?php endif; ?>

        <?php if ($invoice['pdf_url']): ?>
            <a href="<?php echo esc_url($invoice['pdf_url']); ?>" target="_blank" class="button">
                <span class="dashicons dashicons-pdf" style="margin-top: 3px;"></span>
                <?php esc_html_e('Download PDF', 'peanut-suite'); ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="peanut-invoice-detail-grid">
    <!-- Invoice Preview -->
    <div class="peanut-card peanut-invoice-preview">
        <div class="peanut-invoice-header">
            <div>
                <h2 style="margin: 0; font-size: 24px;"><?php esc_html_e('INVOICE', 'peanut-suite'); ?></h2>
                <div style="color: #64748b; margin-top: 4px;"><?php echo esc_html($invoice['invoice_number']); ?></div>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: 600;"><?php echo esc_html(get_bloginfo('name')); ?></div>
                <div style="color: #64748b; font-size: 13px;"><?php echo esc_html(get_bloginfo('admin_email')); ?></div>
            </div>
        </div>

        <div class="peanut-invoice-parties">
            <div class="peanut-invoice-to">
                <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;"><?php esc_html_e('BILL TO', 'peanut-suite'); ?></div>
                <div style="font-weight: 600;"><?php echo esc_html($invoice['client_name']); ?></div>
                <?php if ($invoice['client_company']): ?>
                    <div><?php echo esc_html($invoice['client_company']); ?></div>
                <?php endif; ?>
                <div><?php echo esc_html($invoice['client_email']); ?></div>
                <?php if ($invoice['client_address']): ?>
                    <div style="white-space: pre-line;"><?php echo esc_html($invoice['client_address']); ?></div>
                <?php endif; ?>
            </div>
            <div class="peanut-invoice-dates">
                <div>
                    <span style="color: #64748b;"><?php esc_html_e('Invoice Date:', 'peanut-suite'); ?></span>
                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($invoice['created_at']))); ?>
                </div>
                <div>
                    <span style="color: #64748b;"><?php esc_html_e('Due Date:', 'peanut-suite'); ?></span>
                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($invoice['due_date']))); ?>
                </div>
                <?php if ($invoice['paid_at']): ?>
                    <div style="color: #16a34a;">
                        <span style="color: #64748b;"><?php esc_html_e('Paid:', 'peanut-suite'); ?></span>
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($invoice['paid_at']))); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <table class="peanut-invoice-items-table">
            <thead>
                <tr>
                    <th style="width: 60%;"><?php esc_html_e('Description', 'peanut-suite'); ?></th>
                    <th style="width: 10%; text-align: center;"><?php esc_html_e('Qty', 'peanut-suite'); ?></th>
                    <th style="width: 15%; text-align: right;"><?php esc_html_e('Unit Price', 'peanut-suite'); ?></th>
                    <th style="width: 15%; text-align: right;"><?php esc_html_e('Amount', 'peanut-suite'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($invoice['items'])): ?>
                    <?php foreach ($invoice['items'] as $item): ?>
                        <tr>
                            <td><?php echo esc_html($item['description']); ?></td>
                            <td style="text-align: center;"><?php echo number_format($item['quantity'], 2); ?></td>
                            <td style="text-align: right;">$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td style="text-align: right;">$<?php echo number_format($item['amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #64748b;">
                            <?php esc_html_e('No items', 'peanut-suite'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="peanut-invoice-totals-section">
            <div class="peanut-invoice-totals">
                <div class="peanut-invoice-total-row">
                    <span><?php esc_html_e('Subtotal', 'peanut-suite'); ?></span>
                    <span>$<?php echo number_format($invoice['subtotal'], 2); ?></span>
                </div>
                <?php if ($invoice['tax_amount'] > 0): ?>
                    <div class="peanut-invoice-total-row">
                        <span><?php esc_html_e('Tax', 'peanut-suite'); ?> (<?php echo number_format($invoice['tax_percent'], 2); ?>%)</span>
                        <span>$<?php echo number_format($invoice['tax_amount'], 2); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($invoice['discount_amount'] > 0): ?>
                    <div class="peanut-invoice-total-row">
                        <span><?php esc_html_e('Discount', 'peanut-suite'); ?></span>
                        <span>-$<?php echo number_format($invoice['discount_amount'], 2); ?></span>
                    </div>
                <?php endif; ?>
                <div class="peanut-invoice-total-row peanut-invoice-grand-total">
                    <span><?php esc_html_e('Total', 'peanut-suite'); ?></span>
                    <span>$<?php echo number_format($invoice['total'], 2); ?> <?php echo esc_html($invoice['currency']); ?></span>
                </div>
                <?php if ($invoice['status'] === 'paid'): ?>
                    <div class="peanut-invoice-total-row" style="color: #16a34a;">
                        <span><?php esc_html_e('Amount Paid', 'peanut-suite'); ?></span>
                        <span>$<?php echo number_format($invoice['total'], 2); ?></span>
                    </div>
                    <div class="peanut-invoice-total-row" style="font-weight: 700;">
                        <span><?php esc_html_e('Balance Due', 'peanut-suite'); ?></span>
                        <span>$0.00</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($invoice['notes']): ?>
            <div class="peanut-invoice-notes">
                <div style="font-weight: 600; margin-bottom: 4px;"><?php esc_html_e('Notes', 'peanut-suite'); ?></div>
                <div style="color: #64748b;"><?php echo nl2br(esc_html($invoice['notes'])); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($invoice['footer']): ?>
            <div class="peanut-invoice-footer">
                <?php echo nl2br(esc_html($invoice['footer'])); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar Info -->
    <div class="peanut-invoice-sidebar">
        <div class="peanut-card">
            <h3><?php esc_html_e('Invoice Details', 'peanut-suite'); ?></h3>

            <div class="peanut-detail-row">
                <span><?php esc_html_e('Status', 'peanut-suite'); ?></span>
                <span class="peanut-badge peanut-badge-<?php echo esc_attr($status_color); ?>">
                    <?php echo esc_html(ucfirst($invoice['status'])); ?>
                </span>
            </div>

            <div class="peanut-detail-row">
                <span><?php esc_html_e('Created', 'peanut-suite'); ?></span>
                <span><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($invoice['created_at']))); ?></span>
            </div>

            <?php if ($invoice['sent_at']): ?>
                <div class="peanut-detail-row">
                    <span><?php esc_html_e('Sent', 'peanut-suite'); ?></span>
                    <span><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($invoice['sent_at']))); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($invoice['paid_at']): ?>
                <div class="peanut-detail-row">
                    <span><?php esc_html_e('Paid', 'peanut-suite'); ?></span>
                    <span><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($invoice['paid_at']))); ?></span>
                </div>
            <?php endif; ?>

            <div class="peanut-detail-row">
                <span><?php esc_html_e('Currency', 'peanut-suite'); ?></span>
                <span><?php echo esc_html($invoice['currency']); ?></span>
            </div>

            <?php if ($invoice['stripe_invoice_id']): ?>
                <div class="peanut-detail-row">
                    <span><?php esc_html_e('Stripe ID', 'peanut-suite'); ?></span>
                    <span style="font-size: 11px; font-family: monospace;"><?php echo esc_html($invoice['stripe_invoice_id']); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($invoice['payment_url'] && in_array($invoice['status'], ['sent', 'overdue'])): ?>
            <div class="peanut-card" style="margin-top: 16px;">
                <h3><?php esc_html_e('Share Payment Link', 'peanut-suite'); ?></h3>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 12px;">
                    <?php esc_html_e('Send this link to your client to collect payment:', 'peanut-suite'); ?>
                </p>
                <div style="display: flex; gap: 8px;">
                    <input type="text" value="<?php echo esc_attr($invoice['payment_url']); ?>" readonly style="flex: 1; font-size: 12px;">
                    <button type="button" class="button" id="copy-payment-link">
                        <?php esc_html_e('Copy', 'peanut-suite'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.peanut-back-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #64748b;
    text-decoration: none;
    font-size: 13px;
    margin-bottom: 8px;
}

.peanut-back-link:hover {
    color: #0073aa;
}

.peanut-header-actions {
    display: flex;
    gap: 8px;
}

.peanut-invoice-detail-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 24px;
    margin-top: 24px;
}

.peanut-invoice-preview {
    padding: 40px !important;
    background: #fff !important;
}

.peanut-invoice-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 2px solid #1e293b;
}

.peanut-invoice-parties {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
}

.peanut-invoice-to {
    line-height: 1.6;
}

.peanut-invoice-dates {
    text-align: right;
    line-height: 1.8;
}

.peanut-invoice-items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 24px;
}

.peanut-invoice-items-table th {
    text-align: left;
    padding: 12px 8px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
    text-transform: uppercase;
}

.peanut-invoice-items-table td {
    padding: 12px 8px;
    border-bottom: 1px solid #f1f5f9;
}

.peanut-invoice-totals-section {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 40px;
}

.peanut-invoice-totals {
    width: 280px;
}

.peanut-invoice-total-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
}

.peanut-invoice-grand-total {
    font-size: 18px;
    font-weight: 700;
    border-bottom: 2px solid #1e293b;
    margin-top: 8px;
    padding-top: 12px;
}

.peanut-invoice-notes {
    padding: 16px;
    background: #f8fafc;
    border-radius: 6px;
    margin-bottom: 20px;
}

.peanut-invoice-footer {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
    color: #64748b;
    font-size: 13px;
}

.peanut-invoice-sidebar .peanut-card h3 {
    margin: 0 0 16px;
    font-size: 14px;
    font-weight: 600;
}

.peanut-detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
}

.peanut-detail-row:last-child {
    border-bottom: none;
}

.peanut-detail-row span:first-child {
    color: #64748b;
}

@media (max-width: 900px) {
    .peanut-invoice-detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var invoiceId = <?php echo (int) $invoice_id; ?>;

    // Send invoice
    $('#send-invoice').on('click', function() {
        if (!confirm('<?php esc_html_e('Send this invoice to the client?', 'peanut-suite'); ?>')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php esc_html_e('Sending...', 'peanut-suite'); ?>');

        $.ajax({
            url: '<?php echo esc_url(rest_url(PEANUT_API_NAMESPACE . '/invoices/')); ?>' + invoiceId + '/send',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function() {
                PeanutAdmin.notify('<?php esc_html_e('Invoice sent!', 'peanut-suite'); ?>', 'success');
                location.reload();
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.error || '<?php esc_html_e('Failed to send', 'peanut-suite'); ?>';
                PeanutAdmin.notify(msg, 'error');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-email" style="margin-top: 3px;"></span> <?php esc_html_e('Send Invoice', 'peanut-suite'); ?>');
            }
        });
    });

    // Mark as paid
    $('#mark-paid').on('click', function() {
        if (!confirm('<?php esc_html_e('Mark this invoice as paid?', 'peanut-suite'); ?>')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php esc_html_e('Updating...', 'peanut-suite'); ?>');

        $.ajax({
            url: '<?php echo esc_url(rest_url(PEANUT_API_NAMESPACE . '/invoices/')); ?>' + invoiceId + '/mark-paid',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function() {
                PeanutAdmin.notify('<?php esc_html_e('Invoice marked as paid!', 'peanut-suite'); ?>', 'success');
                location.reload();
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.error || '<?php esc_html_e('Failed to update', 'peanut-suite'); ?>';
                PeanutAdmin.notify(msg, 'error');
                $btn.prop('disabled', false).text('<?php esc_html_e('Mark as Paid', 'peanut-suite'); ?>');
            }
        });
    });

    // Void invoice
    $('#void-invoice').on('click', function() {
        if (!confirm('<?php esc_html_e('Void this invoice? This cannot be undone.', 'peanut-suite'); ?>')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php esc_html_e('Voiding...', 'peanut-suite'); ?>');

        $.ajax({
            url: '<?php echo esc_url(rest_url(PEANUT_API_NAMESPACE . '/invoices/')); ?>' + invoiceId + '/void',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function() {
                PeanutAdmin.notify('<?php esc_html_e('Invoice voided', 'peanut-suite'); ?>', 'success');
                location.reload();
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.error || '<?php esc_html_e('Failed to void', 'peanut-suite'); ?>';
                PeanutAdmin.notify(msg, 'error');
                $btn.prop('disabled', false).text('<?php esc_html_e('Void Invoice', 'peanut-suite'); ?>');
            }
        });
    });

    // Delete invoice
    $('#delete-invoice').on('click', function() {
        if (!confirm('<?php esc_html_e('Delete this draft invoice? This cannot be undone.', 'peanut-suite'); ?>')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php esc_html_e('Deleting...', 'peanut-suite'); ?>');

        $.ajax({
            url: '<?php echo esc_url(rest_url(PEANUT_API_NAMESPACE . '/invoices/')); ?>' + invoiceId,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function() {
                PeanutAdmin.notify('<?php esc_html_e('Invoice deleted', 'peanut-suite'); ?>', 'success');
                window.location.href = '<?php echo esc_url(admin_url('admin.php?page=peanut-invoicing')); ?>';
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.error || '<?php esc_html_e('Failed to delete', 'peanut-suite'); ?>';
                PeanutAdmin.notify(msg, 'error');
                $btn.prop('disabled', false).text('<?php esc_html_e('Delete', 'peanut-suite'); ?>');
            }
        });
    });

    // Copy payment link
    $('#copy-payment-link').on('click', function() {
        var $input = $(this).prev('input');
        $input.select();
        document.execCommand('copy');
        PeanutAdmin.notify('<?php esc_html_e('Link copied!', 'peanut-suite'); ?>', 'success');
    });
});
</script>

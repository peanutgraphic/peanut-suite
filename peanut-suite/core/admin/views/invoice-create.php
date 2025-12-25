<?php
/**
 * Create Invoice View
 *
 * Form to create a new invoice.
 * Agency feature - requires Agency license.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get contacts for dropdown
require_once PEANUT_PLUGIN_DIR . 'core/database/class-peanut-database.php';
global $wpdb;
$contacts_table = Peanut_Database::contacts_table();
$contacts = $wpdb->get_results(
    "SELECT id, email, first_name, last_name, company FROM $contacts_table ORDER BY email ASC LIMIT 500",
    ARRAY_A
) ?: [];

// Get settings for defaults
$settings = get_option('peanut_settings', []);
$default_currency = $settings['invoice_currency'] ?? 'USD';
$default_due_days = $settings['invoice_due_days'] ?? 30;
$default_footer = $settings['invoice_footer'] ?? '';
?>

<div class="peanut-page-header">
    <div>
        <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-invoicing')); ?>" class="peanut-back-link">
            <span class="dashicons dashicons-arrow-left-alt"></span>
            <?php esc_html_e('Back to Invoices', 'peanut-suite'); ?>
        </a>
        <h1><?php esc_html_e('Create Invoice', 'peanut-suite'); ?></h1>
    </div>
</div>

<form id="peanut-invoice-form" class="peanut-form">
    <div class="peanut-form-grid">
        <!-- Left Column - Client & Items -->
        <div class="peanut-form-main">
            <!-- Client Details -->
            <div class="peanut-card">
                <h3>
                    <span class="dashicons dashicons-businessman"></span>
                    <?php esc_html_e('Client Details', 'peanut-suite'); ?>
                </h3>

                <div class="peanut-form-row">
                    <div class="peanut-form-group" style="flex: 2;">
                        <label for="contact_select"><?php esc_html_e('Select from Contacts', 'peanut-suite'); ?></label>
                        <select id="contact_select" name="contact_id">
                            <option value=""><?php esc_html_e('-- New Client --', 'peanut-suite'); ?></option>
                            <?php foreach ($contacts as $contact): ?>
                                <option value="<?php echo esc_attr($contact['id']); ?>"
                                        data-email="<?php echo esc_attr($contact['email']); ?>"
                                        data-name="<?php echo esc_attr(trim($contact['first_name'] . ' ' . $contact['last_name'])); ?>"
                                        data-company="<?php echo esc_attr($contact['company']); ?>">
                                    <?php echo esc_html($contact['email'] . ' - ' . trim($contact['first_name'] . ' ' . $contact['last_name'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="peanut-form-row">
                    <div class="peanut-form-group">
                        <label for="client_name"><?php esc_html_e('Client Name', 'peanut-suite'); ?> <span class="required">*</span></label>
                        <input type="text" id="client_name" name="client_name" required>
                    </div>
                    <div class="peanut-form-group">
                        <label for="client_email"><?php esc_html_e('Email', 'peanut-suite'); ?> <span class="required">*</span></label>
                        <input type="email" id="client_email" name="client_email" required>
                    </div>
                </div>

                <div class="peanut-form-row">
                    <div class="peanut-form-group">
                        <label for="client_company"><?php esc_html_e('Company', 'peanut-suite'); ?></label>
                        <input type="text" id="client_company" name="client_company">
                    </div>
                </div>

                <div class="peanut-form-group">
                    <label for="client_address"><?php esc_html_e('Address', 'peanut-suite'); ?></label>
                    <textarea id="client_address" name="client_address" rows="2"></textarea>
                </div>
            </div>

            <!-- Line Items -->
            <div class="peanut-card">
                <h3>
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Line Items', 'peanut-suite'); ?>
                </h3>

                <table class="peanut-line-items-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;"><?php esc_html_e('Description', 'peanut-suite'); ?></th>
                            <th style="width: 15%;"><?php esc_html_e('Qty', 'peanut-suite'); ?></th>
                            <th style="width: 20%;"><?php esc_html_e('Unit Price', 'peanut-suite'); ?></th>
                            <th style="width: 15%;"><?php esc_html_e('Amount', 'peanut-suite'); ?></th>
                            <th style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="line-items">
                        <tr class="line-item" data-index="0">
                            <td>
                                <input type="text" name="items[0][description]" placeholder="<?php esc_attr_e('Service description...', 'peanut-suite'); ?>" required>
                            </td>
                            <td>
                                <input type="number" name="items[0][quantity]" value="1" min="0.01" step="0.01" class="item-qty">
                            </td>
                            <td>
                                <input type="number" name="items[0][unit_price]" value="0" min="0" step="0.01" class="item-price">
                            </td>
                            <td class="item-amount">$0.00</td>
                            <td>
                                <button type="button" class="button button-small remove-item" title="<?php esc_attr_e('Remove', 'peanut-suite'); ?>">
                                    <span class="dashicons dashicons-no"></span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5">
                                <button type="button" class="button" id="add-line-item">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    <?php esc_html_e('Add Item', 'peanut-suite'); ?>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Notes -->
            <div class="peanut-card">
                <h3>
                    <span class="dashicons dashicons-edit"></span>
                    <?php esc_html_e('Notes & Terms', 'peanut-suite'); ?>
                </h3>

                <div class="peanut-form-group">
                    <label for="notes"><?php esc_html_e('Notes to Client', 'peanut-suite'); ?></label>
                    <textarea id="notes" name="notes" rows="3" placeholder="<?php esc_attr_e('Any additional notes for the client...', 'peanut-suite'); ?>"></textarea>
                </div>

                <div class="peanut-form-group">
                    <label for="footer"><?php esc_html_e('Invoice Footer', 'peanut-suite'); ?></label>
                    <textarea id="footer" name="footer" rows="2" placeholder="<?php esc_attr_e('Payment terms, thank you message, etc.', 'peanut-suite'); ?>"><?php echo esc_textarea($default_footer); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Right Column - Summary -->
        <div class="peanut-form-sidebar">
            <div class="peanut-card peanut-sticky">
                <h3><?php esc_html_e('Invoice Summary', 'peanut-suite'); ?></h3>

                <div class="peanut-form-group">
                    <label for="currency"><?php esc_html_e('Currency', 'peanut-suite'); ?></label>
                    <select id="currency" name="currency">
                        <option value="USD" <?php selected($default_currency, 'USD'); ?>>USD - US Dollar</option>
                        <option value="EUR" <?php selected($default_currency, 'EUR'); ?>>EUR - Euro</option>
                        <option value="GBP" <?php selected($default_currency, 'GBP'); ?>>GBP - British Pound</option>
                        <option value="CAD" <?php selected($default_currency, 'CAD'); ?>>CAD - Canadian Dollar</option>
                        <option value="AUD" <?php selected($default_currency, 'AUD'); ?>>AUD - Australian Dollar</option>
                    </select>
                </div>

                <div class="peanut-form-group">
                    <label for="days_until_due"><?php esc_html_e('Payment Due', 'peanut-suite'); ?></label>
                    <select id="days_until_due" name="days_until_due">
                        <option value="7" <?php selected($default_due_days, 7); ?>><?php esc_html_e('Net 7 (7 days)', 'peanut-suite'); ?></option>
                        <option value="14" <?php selected($default_due_days, 14); ?>><?php esc_html_e('Net 14 (14 days)', 'peanut-suite'); ?></option>
                        <option value="30" <?php selected($default_due_days, 30); ?>><?php esc_html_e('Net 30 (30 days)', 'peanut-suite'); ?></option>
                        <option value="45" <?php selected($default_due_days, 45); ?>><?php esc_html_e('Net 45 (45 days)', 'peanut-suite'); ?></option>
                        <option value="60" <?php selected($default_due_days, 60); ?>><?php esc_html_e('Net 60 (60 days)', 'peanut-suite'); ?></option>
                    </select>
                </div>

                <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">

                <div class="peanut-invoice-totals">
                    <div class="peanut-total-row">
                        <span><?php esc_html_e('Subtotal', 'peanut-suite'); ?></span>
                        <span id="subtotal">$0.00</span>
                    </div>

                    <div class="peanut-total-row">
                        <span>
                            <?php esc_html_e('Tax', 'peanut-suite'); ?>
                            <input type="number" id="tax_percent" name="tax_percent" value="0" min="0" max="100" step="0.01" style="width: 60px; margin-left: 8px;">%
                        </span>
                        <span id="tax_amount">$0.00</span>
                    </div>

                    <div class="peanut-total-row">
                        <span>
                            <?php esc_html_e('Discount', 'peanut-suite'); ?>
                        </span>
                        <span>
                            -$<input type="number" id="discount_amount" name="discount_amount" value="0" min="0" step="0.01" style="width: 80px;">
                        </span>
                    </div>

                    <div class="peanut-total-row peanut-grand-total">
                        <span><?php esc_html_e('Total', 'peanut-suite'); ?></span>
                        <span id="grand_total">$0.00</span>
                    </div>
                </div>

                <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">

                <button type="submit" class="button button-primary button-large" style="width: 100%;" id="create-invoice-btn">
                    <?php esc_html_e('Create Invoice', 'peanut-suite'); ?>
                </button>

                <p style="text-align: center; margin-top: 12px; font-size: 12px; color: #64748b;">
                    <?php esc_html_e('Invoice will be created as a draft. You can review and send it after.', 'peanut-suite'); ?>
                </p>
            </div>
        </div>
    </div>
</form>

<style>
.peanut-form-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
    align-items: start;
}

.peanut-form-main .peanut-card {
    margin-bottom: 24px;
}

.peanut-form-main .peanut-card h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 15px;
}

.peanut-form-row {
    display: flex;
    gap: 16px;
}

.peanut-form-row .peanut-form-group {
    flex: 1;
}

.peanut-line-items-table {
    width: 100%;
    border-collapse: collapse;
}

.peanut-line-items-table th {
    text-align: left;
    padding: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
}

.peanut-line-items-table td {
    padding: 8px;
    vertical-align: middle;
}

.peanut-line-items-table input[type="text"] {
    width: 100%;
}

.peanut-line-items-table input[type="number"] {
    width: 100%;
    text-align: right;
}

.peanut-line-items-table .item-amount {
    text-align: right;
    font-weight: 600;
}

.peanut-line-items-table .remove-item {
    padding: 2px 6px;
}

.peanut-line-items-table .remove-item .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.peanut-line-items-table tfoot td {
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
}

.peanut-sticky {
    position: sticky;
    top: 32px;
}

.peanut-invoice-totals {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.peanut-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
}

.peanut-grand-total {
    font-size: 18px;
    font-weight: 700;
    padding-top: 12px;
    border-top: 2px solid #1e293b;
}

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

@media (max-width: 900px) {
    .peanut-form-grid {
        grid-template-columns: 1fr;
    }

    .peanut-sticky {
        position: static;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var itemIndex = 1;

    // Contact select auto-fill
    $('#contact_select').on('change', function() {
        var $option = $(this).find('option:selected');
        if ($option.val()) {
            $('#client_email').val($option.data('email'));
            $('#client_name').val($option.data('name'));
            $('#client_company').val($option.data('company'));
        }
    });

    // Add line item
    $('#add-line-item').on('click', function() {
        var html = '<tr class="line-item" data-index="' + itemIndex + '">' +
            '<td><input type="text" name="items[' + itemIndex + '][description]" placeholder="<?php esc_attr_e('Service description...', 'peanut-suite'); ?>" required></td>' +
            '<td><input type="number" name="items[' + itemIndex + '][quantity]" value="1" min="0.01" step="0.01" class="item-qty"></td>' +
            '<td><input type="number" name="items[' + itemIndex + '][unit_price]" value="0" min="0" step="0.01" class="item-price"></td>' +
            '<td class="item-amount">$0.00</td>' +
            '<td><button type="button" class="button button-small remove-item"><span class="dashicons dashicons-no"></span></button></td>' +
            '</tr>';
        $('#line-items').append(html);
        itemIndex++;
    });

    // Remove line item
    $(document).on('click', '.remove-item', function() {
        if ($('.line-item').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        }
    });

    // Calculate totals
    function calculateTotals() {
        var subtotal = 0;

        $('.line-item').each(function() {
            var qty = parseFloat($(this).find('.item-qty').val()) || 0;
            var price = parseFloat($(this).find('.item-price').val()) || 0;
            var amount = qty * price;
            $(this).find('.item-amount').text('$' + amount.toFixed(2));
            subtotal += amount;
        });

        var taxPercent = parseFloat($('#tax_percent').val()) || 0;
        var taxAmount = subtotal * (taxPercent / 100);
        var discount = parseFloat($('#discount_amount').val()) || 0;
        var total = subtotal + taxAmount - discount;

        $('#subtotal').text('$' + subtotal.toFixed(2));
        $('#tax_amount').text('$' + taxAmount.toFixed(2));
        $('#grand_total').text('$' + total.toFixed(2));
    }

    // Update totals on change
    $(document).on('input', '.item-qty, .item-price, #tax_percent, #discount_amount', calculateTotals);

    // Submit form
    $('#peanut-invoice-form').on('submit', function(e) {
        e.preventDefault();

        var $btn = $('#create-invoice-btn');
        $btn.prop('disabled', true).text('<?php esc_html_e('Creating...', 'peanut-suite'); ?>');

        // Gather items
        var items = [];
        $('.line-item').each(function() {
            items.push({
                description: $(this).find('input[name*="description"]').val(),
                quantity: parseFloat($(this).find('.item-qty').val()) || 1,
                unit_price: parseFloat($(this).find('.item-price').val()) || 0
            });
        });

        var data = {
            client_name: $('#client_name').val(),
            client_email: $('#client_email').val(),
            client_company: $('#client_company').val(),
            client_address: $('#client_address').val(),
            contact_id: $('#contact_select').val(),
            currency: $('#currency').val(),
            days_until_due: parseInt($('#days_until_due').val()),
            tax_percent: parseFloat($('#tax_percent').val()) || 0,
            discount_amount: parseFloat($('#discount_amount').val()) || 0,
            notes: $('#notes').val(),
            footer: $('#footer').val(),
            items: items
        };

        $.ajax({
            url: '<?php echo esc_url(rest_url(PEANUT_API_NAMESPACE . '/invoices')); ?>',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                PeanutAdmin.notify('<?php esc_html_e('Invoice created successfully!', 'peanut-suite'); ?>', 'success');
                window.location.href = '<?php echo esc_url(admin_url('admin.php?page=peanut-invoicing&view=detail&id=')); ?>' + response.id;
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.error || '<?php esc_html_e('Failed to create invoice', 'peanut-suite'); ?>';
                PeanutAdmin.notify(msg, 'error');
                $btn.prop('disabled', false).text('<?php esc_html_e('Create Invoice', 'peanut-suite'); ?>');
            }
        });
    });

    // Initial calculation
    calculateTotals();
});
</script>

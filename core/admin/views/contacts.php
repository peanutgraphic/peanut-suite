<?php
/**
 * Contacts Management Page
 *
 * Manage leads and contacts in a simple CRM.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load the list table class
require_once PEANUT_PLUGIN_DIR . 'core/admin/tables/class-contacts-list-table.php';

// Initialize and prepare list table
$list_table = new Peanut_Contacts_List_Table();
$list_table->prepare_items();

// Get stats
global $wpdb;
$table_name = $wpdb->prefix . 'peanut_contacts';
$stats = [
    'total' => 0,
    'leads' => 0,
    'prospects' => 0,
    'customers' => 0,
];

if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
    $stats['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $stats['leads'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'lead'");
    $stats['prospects'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'prospect'");
    $stats['customers'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'customer'");
}
?>

<div class="peanut-contacts-page">

    <!-- Stats Cards -->
    <div class="peanut-stats-row">
        <div class="peanut-stat-card">
            <div class="peanut-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['total']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Total Contacts', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: rgba(0, 160, 210, 0.1);">
                <span class="dashicons dashicons-welcome-learn-more" style="color: #00a0d2;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['leads']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Leads', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: rgba(255, 186, 0, 0.1);">
                <span class="dashicons dashicons-star-filled" style="color: #ffba00;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['prospects']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Prospects', 'peanut-suite'); ?></span>
            </div>
        </div>

        <div class="peanut-stat-card">
            <div class="peanut-stat-icon" style="background: rgba(70, 180, 80, 0.1);">
                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
            </div>
            <div class="peanut-stat-content">
                <span class="peanut-stat-value"><?php echo number_format_i18n($stats['customers']); ?></span>
                <span class="peanut-stat-label"><?php esc_html_e('Customers', 'peanut-suite'); ?></span>
            </div>
        </div>
    </div>

    <!-- Contact Lifecycle Explanation -->
    <div class="peanut-info-banner peanut-lifecycle-banner">
        <span class="dashicons dashicons-chart-line"></span>
        <div class="peanut-info-content">
            <strong><?php esc_html_e('Contact Lifecycle', 'peanut-suite'); ?></strong>
            <div class="peanut-lifecycle-flow">
                <div class="peanut-lifecycle-stage">
                    <span class="peanut-badge peanut-badge-info"><?php esc_html_e('Lead', 'peanut-suite'); ?></span>
                    <small><?php esc_html_e('New contact, just captured', 'peanut-suite'); ?></small>
                </div>
                <span class="peanut-lifecycle-arrow">&rarr;</span>
                <div class="peanut-lifecycle-stage">
                    <span class="peanut-badge peanut-badge-warning"><?php esc_html_e('Prospect', 'peanut-suite'); ?></span>
                    <small><?php esc_html_e('Qualified, showing interest', 'peanut-suite'); ?></small>
                </div>
                <span class="peanut-lifecycle-arrow">&rarr;</span>
                <div class="peanut-lifecycle-stage">
                    <span class="peanut-badge peanut-badge-success"><?php esc_html_e('Customer', 'peanut-suite'); ?></span>
                    <small><?php esc_html_e('Made a purchase', 'peanut-suite'); ?></small>
                </div>
            </div>
        </div>
        <button type="button" class="peanut-dismiss-banner" aria-label="<?php esc_attr_e('Dismiss', 'peanut-suite'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>

    <!-- Contacts Table -->
    <div class="peanut-card">
        <form method="get">
            <input type="hidden" name="page" value="peanut-contacts" />
            <?php
            $list_table->search_box(__('Search Contacts', 'peanut-suite'), 'contact');
            $list_table->display();
            ?>
        </form>
    </div>

    <!-- Ways to Add Contacts -->
    <div class="peanut-card">
        <h3><?php esc_html_e('Ways to Add Contacts', 'peanut-suite'); ?></h3>
        <div class="peanut-ways-grid">
            <div class="peanut-way-card">
                <span class="dashicons dashicons-edit"></span>
                <h4><?php esc_html_e('Manual Entry', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Click "Add Contact" to manually enter contact details.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-way-card">
                <span class="dashicons dashicons-upload"></span>
                <h4><?php esc_html_e('CSV Import', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Import contacts in bulk from a CSV file with email, name, company columns.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-way-card">
                <span class="dashicons dashicons-rest-api"></span>
                <h4><?php esc_html_e('Webhook Integration', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Connect forms from other platforms via webhook to auto-capture contacts.', 'peanut-suite'); ?></p>
            </div>
            <div class="peanut-way-card">
                <span class="dashicons dashicons-admin-generic"></span>
                <h4><?php esc_html_e('API Access', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Use the REST API to programmatically create contacts from your applications.', 'peanut-suite'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Contact Modal -->
<div id="peanut-contact-modal" class="peanut-modal">
    <div class="peanut-modal-content">
        <div class="peanut-modal-header">
            <h2 id="peanut-contact-modal-title"><?php esc_html_e('Add Contact', 'peanut-suite'); ?></h2>
            <button type="button" class="peanut-modal-close">&times;</button>
        </div>
        <div class="peanut-modal-body">
            <form id="peanut-contact-form">
                <?php wp_nonce_field('peanut_admin_nonce', 'peanut_nonce'); ?>
                <input type="hidden" id="contact-id" name="id" value="">

                <div class="peanut-form-group">
                    <label for="contact-email">
                        <?php esc_html_e('Email Address', 'peanut-suite'); ?>
                        <span class="required">*</span>
                        <?php echo peanut_tooltip(__('Primary email address for the contact. Must be unique.', 'peanut-suite')); ?>
                    </label>
                    <input type="email" id="contact-email" name="email" required>
                </div>

                <div class="peanut-form-row">
                    <div class="peanut-form-group peanut-form-half">
                        <label for="contact-first-name">
                            <?php esc_html_e('First Name', 'peanut-suite'); ?>
                        </label>
                        <input type="text" id="contact-first-name" name="first_name">
                    </div>
                    <div class="peanut-form-group peanut-form-half">
                        <label for="contact-last-name">
                            <?php esc_html_e('Last Name', 'peanut-suite'); ?>
                        </label>
                        <input type="text" id="contact-last-name" name="last_name">
                    </div>
                </div>

                <div class="peanut-form-group">
                    <label for="contact-company">
                        <?php esc_html_e('Company', 'peanut-suite'); ?>
                    </label>
                    <input type="text" id="contact-company" name="company">
                </div>

                <div class="peanut-form-row">
                    <div class="peanut-form-group peanut-form-half">
                        <label for="contact-phone">
                            <?php esc_html_e('Phone', 'peanut-suite'); ?>
                        </label>
                        <input type="tel" id="contact-phone" name="phone">
                    </div>
                    <div class="peanut-form-group peanut-form-half">
                        <label for="contact-status">
                            <?php esc_html_e('Status', 'peanut-suite'); ?>
                            <?php echo peanut_tooltip(__('Categorize where this contact is in your sales pipeline.', 'peanut-suite')); ?>
                        </label>
                        <select id="contact-status" name="status">
                            <option value="lead"><?php esc_html_e('Lead', 'peanut-suite'); ?></option>
                            <option value="prospect"><?php esc_html_e('Prospect', 'peanut-suite'); ?></option>
                            <option value="customer"><?php esc_html_e('Customer', 'peanut-suite'); ?></option>
                            <option value="churned"><?php esc_html_e('Churned', 'peanut-suite'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="peanut-form-group">
                    <label for="contact-tags">
                        <?php esc_html_e('Tags', 'peanut-suite'); ?>
                        <?php echo peanut_tooltip(__('Add comma-separated tags to organize and segment contacts.', 'peanut-suite')); ?>
                    </label>
                    <input type="text" id="contact-tags" name="tags" placeholder="<?php esc_attr_e('newsletter, webinar-attendee, 2024', 'peanut-suite'); ?>">
                    <?php echo peanut_field_help(__('Separate multiple tags with commas', 'peanut-suite')); ?>
                </div>

                <div class="peanut-form-group">
                    <label for="contact-notes">
                        <?php esc_html_e('Notes', 'peanut-suite'); ?>
                    </label>
                    <textarea id="contact-notes" name="notes" rows="3" placeholder="<?php esc_attr_e('Any additional information about this contact...', 'peanut-suite'); ?>"></textarea>
                </div>

                <!-- UTM Attribution (if available) -->
                <div class="peanut-form-section peanut-attribution-section" style="display: none;">
                    <h4><?php esc_html_e('Attribution Data', 'peanut-suite'); ?></h4>
                    <div class="peanut-attribution-fields">
                        <div class="peanut-attribution-field">
                            <span class="peanut-attr-label"><?php esc_html_e('Source:', 'peanut-suite'); ?></span>
                            <span class="peanut-attr-value" id="contact-utm-source">-</span>
                        </div>
                        <div class="peanut-attribution-field">
                            <span class="peanut-attr-label"><?php esc_html_e('Medium:', 'peanut-suite'); ?></span>
                            <span class="peanut-attr-value" id="contact-utm-medium">-</span>
                        </div>
                        <div class="peanut-attribution-field">
                            <span class="peanut-attr-label"><?php esc_html_e('Campaign:', 'peanut-suite'); ?></span>
                            <span class="peanut-attr-value" id="contact-utm-campaign">-</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="peanut-modal-footer">
            <button type="button" class="button" data-dismiss="modal"><?php esc_html_e('Cancel', 'peanut-suite'); ?></button>
            <button type="submit" form="peanut-contact-form" class="button button-primary">
                <?php esc_html_e('Save Contact', 'peanut-suite'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="peanut-import-modal" class="peanut-modal">
    <div class="peanut-modal-content">
        <div class="peanut-modal-header">
            <h2><?php esc_html_e('Import Contacts', 'peanut-suite'); ?></h2>
            <button type="button" class="peanut-modal-close">&times;</button>
        </div>
        <div class="peanut-modal-body">
            <div class="peanut-import-instructions">
                <h4><?php esc_html_e('CSV Format Requirements', 'peanut-suite'); ?></h4>
                <p><?php esc_html_e('Your CSV file should have a header row with column names. Supported columns:', 'peanut-suite'); ?></p>
                <ul>
                    <li><strong>email</strong> - <?php esc_html_e('Required. The contact\'s email address.', 'peanut-suite'); ?></li>
                    <li><strong>first_name</strong> - <?php esc_html_e('Optional. First name.', 'peanut-suite'); ?></li>
                    <li><strong>last_name</strong> - <?php esc_html_e('Optional. Last name.', 'peanut-suite'); ?></li>
                    <li><strong>company</strong> - <?php esc_html_e('Optional. Company name.', 'peanut-suite'); ?></li>
                    <li><strong>phone</strong> - <?php esc_html_e('Optional. Phone number.', 'peanut-suite'); ?></li>
                    <li><strong>status</strong> - <?php esc_html_e('Optional. lead, prospect, customer, or churned.', 'peanut-suite'); ?></li>
                    <li><strong>tags</strong> - <?php esc_html_e('Optional. Comma-separated tags.', 'peanut-suite'); ?></li>
                </ul>

                <div class="peanut-sample-csv">
                    <strong><?php esc_html_e('Example CSV:', 'peanut-suite'); ?></strong>
                    <pre>email,first_name,last_name,company,status
john@example.com,John,Doe,Acme Inc,lead
jane@example.com,Jane,Smith,Tech Corp,customer</pre>
                </div>
            </div>

            <form id="peanut-import-form" enctype="multipart/form-data">
                <?php wp_nonce_field('peanut_admin_nonce', 'peanut_nonce'); ?>
                <div class="peanut-form-group">
                    <label for="import-file"><?php esc_html_e('Select CSV File', 'peanut-suite'); ?></label>
                    <input type="file" id="import-file" name="file" accept=".csv" required>
                </div>
                <div class="peanut-form-group">
                    <label>
                        <input type="checkbox" name="skip_duplicates" value="1" checked>
                        <?php esc_html_e('Skip duplicate emails (recommended)', 'peanut-suite'); ?>
                    </label>
                </div>
            </form>

            <div id="peanut-import-progress" style="display: none;">
                <div class="peanut-progress-bar">
                    <div class="peanut-progress-fill"></div>
                </div>
                <p class="peanut-progress-text"><?php esc_html_e('Importing...', 'peanut-suite'); ?></p>
            </div>
        </div>
        <div class="peanut-modal-footer">
            <button type="button" class="button" data-dismiss="modal"><?php esc_html_e('Cancel', 'peanut-suite'); ?></button>
            <button type="submit" form="peanut-import-form" class="button button-primary">
                <span class="dashicons dashicons-upload"></span>
                <?php esc_html_e('Import', 'peanut-suite'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div id="peanut-export-modal" class="peanut-modal">
    <div class="peanut-modal-content peanut-modal-sm">
        <div class="peanut-modal-header">
            <h2><?php esc_html_e('Export Contacts', 'peanut-suite'); ?></h2>
            <button type="button" class="peanut-modal-close">&times;</button>
        </div>
        <div class="peanut-modal-body">
            <form id="peanut-export-form">
                <div class="peanut-form-group">
                    <label><?php esc_html_e('Export Format', 'peanut-suite'); ?></label>
                    <div class="peanut-radio-group">
                        <label>
                            <input type="radio" name="format" value="csv" checked>
                            <?php esc_html_e('CSV (Excel, Google Sheets)', 'peanut-suite'); ?>
                        </label>
                        <label>
                            <input type="radio" name="format" value="json">
                            <?php esc_html_e('JSON (Developers)', 'peanut-suite'); ?>
                        </label>
                    </div>
                </div>
                <div class="peanut-form-group">
                    <label><?php esc_html_e('Filter by Status', 'peanut-suite'); ?></label>
                    <select name="status">
                        <option value=""><?php esc_html_e('All Contacts', 'peanut-suite'); ?></option>
                        <option value="lead"><?php esc_html_e('Leads Only', 'peanut-suite'); ?></option>
                        <option value="prospect"><?php esc_html_e('Prospects Only', 'peanut-suite'); ?></option>
                        <option value="customer"><?php esc_html_e('Customers Only', 'peanut-suite'); ?></option>
                    </select>
                </div>
            </form>
        </div>
        <div class="peanut-modal-footer">
            <button type="button" class="button" data-dismiss="modal"><?php esc_html_e('Cancel', 'peanut-suite'); ?></button>
            <button type="button" class="button button-primary" id="peanut-do-export">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Download', 'peanut-suite'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var isEditing = false;

    // Add contact button
    $('#peanut-add-contact').on('click', function() {
        isEditing = false;
        $('#peanut-contact-modal-title').text('<?php esc_html_e('Add Contact', 'peanut-suite'); ?>');
        $('#peanut-contact-form')[0].reset();
        $('#contact-id').val('');
        $('.peanut-attribution-section').hide();
        PeanutAdmin.openModal('#peanut-contact-modal');
    });

    // Edit contact
    $(document).on('click', '.peanut-edit-contact', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        isEditing = true;

        $('#peanut-contact-modal-title').text('<?php esc_html_e('Edit Contact', 'peanut-suite'); ?>');

        // Fetch contact data
        $.ajax({
            url: peanutAdmin.apiUrl + '/contacts/' + id,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', peanutAdmin.nonce);
            },
            success: function(contact) {
                $('#contact-id').val(contact.id);
                $('#contact-email').val(contact.email);
                $('#contact-first-name').val(contact.first_name || '');
                $('#contact-last-name').val(contact.last_name || '');
                $('#contact-company').val(contact.company || '');
                $('#contact-phone').val(contact.phone || '');
                $('#contact-status').val(contact.status || 'lead');
                $('#contact-tags').val(contact.tags || '');
                $('#contact-notes').val(contact.notes || '');

                // Show attribution if available
                if (contact.utm_source || contact.utm_medium || contact.utm_campaign) {
                    $('#contact-utm-source').text(contact.utm_source || '-');
                    $('#contact-utm-medium').text(contact.utm_medium || '-');
                    $('#contact-utm-campaign').text(contact.utm_campaign || '-');
                    $('.peanut-attribution-section').show();
                } else {
                    $('.peanut-attribution-section').hide();
                }

                PeanutAdmin.openModal('#peanut-contact-modal');
            },
            error: function() {
                PeanutAdmin.notify('<?php esc_html_e('Failed to load contact', 'peanut-suite'); ?>', 'error');
            }
        });
    });

    // Save contact
    $('#peanut-contact-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $form.closest('.peanut-modal-content').find('button[type="submit"]');
        var id = $('#contact-id').val();

        $submitBtn.prop('disabled', true).addClass('updating-message');

        var data = {
            email: $('#contact-email').val(),
            first_name: $('#contact-first-name').val(),
            last_name: $('#contact-last-name').val(),
            company: $('#contact-company').val(),
            phone: $('#contact-phone').val(),
            status: $('#contact-status').val(),
            tags: $('#contact-tags').val(),
            notes: $('#contact-notes').val()
        };

        var url = peanutAdmin.apiUrl + '/contacts';
        var method = 'POST';

        if (id) {
            url += '/' + id;
            method = 'PUT';
        }

        $.ajax({
            url: url,
            method: method,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', peanutAdmin.nonce);
            },
            data: data,
            success: function() {
                PeanutAdmin.closeModal('#peanut-contact-modal');
                PeanutAdmin.notify(
                    id ? '<?php esc_html_e('Contact updated!', 'peanut-suite'); ?>' : '<?php esc_html_e('Contact added!', 'peanut-suite'); ?>',
                    'success'
                );
                location.reload();
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || '<?php esc_html_e('Failed to save contact', 'peanut-suite'); ?>';
                PeanutAdmin.notify(message, 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).removeClass('updating-message');
            }
        });
    });

    // Delete contact
    $(document).on('click', '.peanut-delete-contact', function(e) {
        e.preventDefault();

        var $link = $(this);
        var id = $link.data('id');
        var confirmMsg = $link.data('confirm');

        if (!confirm(confirmMsg)) return;

        $.ajax({
            url: peanutAdmin.apiUrl + '/contacts/' + id,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', peanutAdmin.nonce);
            },
            success: function() {
                PeanutAdmin.notify('<?php esc_html_e('Contact deleted', 'peanut-suite'); ?>', 'success');
                $link.closest('tr').fadeOut(function() {
                    $(this).remove();
                });
            },
            error: function() {
                PeanutAdmin.notify('<?php esc_html_e('Failed to delete contact', 'peanut-suite'); ?>', 'error');
            }
        });
    });

    // Import modal
    $('#peanut-import-contacts').on('click', function() {
        PeanutAdmin.openModal('#peanut-import-modal');
    });

    // Export modal
    $('#peanut-export-contacts').on('click', function() {
        PeanutAdmin.openModal('#peanut-export-modal');
    });

    // Do export
    $('#peanut-do-export').on('click', function() {
        var format = $('input[name="format"]:checked').val();
        var status = $('#peanut-export-form select[name="status"]').val();

        var url = peanutAdmin.apiUrl + '/contacts/export?format=' + format;
        if (status) url += '&status=' + status;

        window.location.href = url;
        PeanutAdmin.closeModal('#peanut-export-modal');
    });

    // Dismiss banner
    $('.peanut-dismiss-banner').on('click', function() {
        $(this).closest('.peanut-info-banner').slideUp();
    });
});
</script>


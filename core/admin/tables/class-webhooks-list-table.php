<?php
/**
 * Webhooks List Table
 *
 * Displays incoming webhook events in a WordPress admin table.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Peanut_Webhooks_List_Table extends WP_List_Table {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'webhook',
            'plural' => 'webhooks',
            'ajax' => false,
        ]);
    }

    /**
     * Get columns
     */
    public function get_columns(): array {
        return [
            'cb' => '<input type="checkbox" />',
            'source' => __('Source', 'peanut-suite'),
            'event_type' => __('Event', 'peanut-suite'),
            'status' => __('Status', 'peanut-suite'),
            'contact' => __('Contact', 'peanut-suite'),
            'received_at' => __('Received', 'peanut-suite'),
            'actions' => __('Actions', 'peanut-suite'),
        ];
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns(): array {
        return [
            'source' => ['source', false],
            'event_type' => ['event_type', false],
            'status' => ['status', false],
            'received_at' => ['received_at', true],
        ];
    }

    /**
     * Default column handler
     */
    public function column_default($item, $column_name) {
        return $item[$column_name] ?? '-';
    }

    /**
     * Checkbox column
     */
    public function column_cb($item): string {
        return sprintf(
            '<input type="checkbox" name="webhook_ids[]" value="%d" />',
            $item['id']
        );
    }

    /**
     * Source column
     */
    public function column_source($item): string {
        $source = $item['source'] ?? 'unknown';

        $source_icons = [
            'formflow' => 'dashicons-feedback',
            'gravity_forms' => 'dashicons-forms',
            'wpforms' => 'dashicons-feedback',
            'contact_form_7' => 'dashicons-email-alt',
            'mailchimp' => 'dashicons-email',
            'convertkit' => 'dashicons-megaphone',
            'zapier' => 'dashicons-randomize',
            'make' => 'dashicons-admin-generic',
            'custom' => 'dashicons-rest-api',
        ];

        $icon = $source_icons[$source] ?? 'dashicons-rest-api';
        $label = ucwords(str_replace('_', ' ', $source));

        return sprintf(
            '<span class="peanut-webhook-source">
                <span class="dashicons %s"></span>
                %s
            </span>',
            esc_attr($icon),
            esc_html($label)
        );
    }

    /**
     * Event type column
     */
    public function column_event_type($item): string {
        $event = $item['event_type'] ?? 'unknown';

        $event_labels = [
            'form_submission' => __('Form Submission', 'peanut-suite'),
            'contact_created' => __('Contact Created', 'peanut-suite'),
            'contact_updated' => __('Contact Updated', 'peanut-suite'),
            'subscriber_added' => __('Subscriber Added', 'peanut-suite'),
            'purchase' => __('Purchase', 'peanut-suite'),
            'custom' => __('Custom Event', 'peanut-suite'),
        ];

        $label = $event_labels[$event] ?? ucwords(str_replace('_', ' ', $event));

        return sprintf(
            '<code class="peanut-event-type">%s</code>',
            esc_html($label)
        );
    }

    /**
     * Status column
     */
    public function column_status($item): string {
        $status = $item['status'] ?? 'pending';

        $statuses = [
            'processed' => ['label' => __('Processed', 'peanut-suite'), 'class' => 'peanut-badge-success'],
            'pending' => ['label' => __('Pending', 'peanut-suite'), 'class' => 'peanut-badge-warning'],
            'failed' => ['label' => __('Failed', 'peanut-suite'), 'class' => 'peanut-badge-error'],
            'ignored' => ['label' => __('Ignored', 'peanut-suite'), 'class' => 'peanut-badge-neutral'],
        ];

        $config = $statuses[$status] ?? $statuses['pending'];

        $output = sprintf(
            '<span class="peanut-badge %s">%s</span>',
            esc_attr($config['class']),
            esc_html($config['label'])
        );

        // Add error message if failed
        if ($status === 'failed' && !empty($item['error_message'])) {
            $output .= sprintf(
                '<span class="peanut-error-hint" title="%s">
                    <span class="dashicons dashicons-warning"></span>
                </span>',
                esc_attr($item['error_message'])
            );
        }

        return $output;
    }

    /**
     * Contact column
     */
    public function column_contact($item): string {
        $contact_id = $item['contact_id'] ?? null;

        if (!$contact_id) {
            return '<span class="peanut-text-muted">-</span>';
        }

        // Try to get contact email from payload
        $payload = json_decode($item['payload'] ?? '{}', true);
        $email = $payload['email'] ?? $payload['contact_email'] ?? null;

        if ($email) {
            return sprintf(
                '<a href="%s" class="peanut-contact-link">%s</a>',
                esc_url(admin_url('admin.php?page=peanut-contacts&s=' . urlencode($email))),
                esc_html($email)
            );
        }

        return sprintf(
            '<a href="%s">#%d</a>',
            esc_url(admin_url('admin.php?page=peanut-contacts')),
            $contact_id
        );
    }

    /**
     * Received at column
     */
    public function column_received_at($item): string {
        $date = $item['received_at'] ?? $item['created_at'] ?? '';
        if (!$date) return '-';

        return sprintf(
            '<span title="%s">%s</span>',
            esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($date))),
            esc_html(human_time_diff(strtotime($date), current_time('timestamp')) . ' ' . __('ago', 'peanut-suite'))
        );
    }

    /**
     * Actions column
     */
    public function column_actions($item): string {
        $actions = [];

        // View payload
        $actions[] = sprintf(
            '<a href="#" class="peanut-view-payload" data-id="%d" title="%s">
                <span class="dashicons dashicons-visibility"></span>
            </a>',
            $item['id'],
            esc_attr__('View Payload', 'peanut-suite')
        );

        // Reprocess (if failed or pending)
        if (in_array($item['status'] ?? '', ['failed', 'pending'])) {
            $actions[] = sprintf(
                '<a href="#" class="peanut-reprocess-webhook" data-id="%d" title="%s">
                    <span class="dashicons dashicons-update"></span>
                </a>',
                $item['id'],
                esc_attr__('Reprocess', 'peanut-suite')
            );
        }

        // Delete
        $actions[] = sprintf(
            '<a href="#" class="peanut-delete-webhook" data-id="%d" data-confirm="%s" title="%s">
                <span class="dashicons dashicons-trash"></span>
            </a>',
            $item['id'],
            esc_attr__('Are you sure you want to delete this webhook?', 'peanut-suite'),
            esc_attr__('Delete', 'peanut-suite')
        );

        return '<div class="peanut-row-actions">' . implode('', $actions) . '</div>';
    }

    /**
     * Get bulk actions
     */
    public function get_bulk_actions(): array {
        return [
            'delete' => __('Delete', 'peanut-suite'),
            'reprocess' => __('Reprocess', 'peanut-suite'),
            'mark_processed' => __('Mark as Processed', 'peanut-suite'),
        ];
    }

    /**
     * Prepare items
     */
    public function prepare_items(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'peanut_webhooks';

        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
            $this->items = [];
            return;
        }

        $per_page = 20;
        $current_page = $this->get_pagenum();

        // Build query
        $where = '1=1';

        // Search
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        if ($search) {
            $where .= $wpdb->prepare(
                " AND (source LIKE %s OR event_type LIKE %s OR payload LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        // Status filter
        $status_filter = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
        if ($status_filter) {
            $where .= $wpdb->prepare(" AND status = %s", $status_filter);
        }

        // Source filter
        $source_filter = isset($_REQUEST['source']) ? sanitize_text_field($_REQUEST['source']) : '';
        if ($source_filter) {
            $where .= $wpdb->prepare(" AND source = %s", $source_filter);
        }

        // Sorting
        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'received_at';
        if (!$orderby) $orderby = 'received_at';
        $order = isset($_REQUEST['order']) && strtoupper($_REQUEST['order']) === 'ASC' ? 'ASC' : 'DESC';

        // Get total count
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $where");

        // Get items
        $offset = ($current_page - 1) * $per_page;
        $this->items = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE $where ORDER BY $orderby $order LIMIT $offset, $per_page",
            ARRAY_A
        ) ?: [];

        // Set up pagination
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);

        // Set column headers
        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns(),
        ];
    }

    /**
     * Message when no items
     */
    public function no_items(): void {
        ?>
        <div class="peanut-empty-state">
            <div class="peanut-empty-state-icon">
                <span class="dashicons dashicons-rest-api"></span>
            </div>
            <h3><?php esc_html_e('No webhooks received yet', 'peanut-suite'); ?></h3>
            <p><?php esc_html_e('Webhooks will appear here when external services send data to your site. Connect a form service or use the API to send test data.', 'peanut-suite'); ?></p>
            <button type="button" class="button button-primary" id="peanut-show-webhook-url">
                <?php esc_html_e('Get Webhook URL', 'peanut-suite'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Extra table navigation (filters)
     */
    public function extra_tablenav($which): void {
        if ($which !== 'top') return;

        global $wpdb;
        $table_name = $wpdb->prefix . 'peanut_webhooks';

        // Get unique sources
        $sources = [];
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
            $sources = $wpdb->get_col("SELECT DISTINCT source FROM $table_name WHERE source IS NOT NULL ORDER BY source");
        }
        ?>
        <div class="alignleft actions">
            <?php
            $current_status = sanitize_text_field($_REQUEST['status'] ?? '');
            $current_source = sanitize_text_field($_REQUEST['source'] ?? '');
            ?>
            <select name="status">
                <option value=""><?php esc_html_e('All Statuses', 'peanut-suite'); ?></option>
                <option value="processed" <?php selected($current_status, 'processed'); ?>><?php esc_html_e('Processed', 'peanut-suite'); ?></option>
                <option value="pending" <?php selected($current_status, 'pending'); ?>><?php esc_html_e('Pending', 'peanut-suite'); ?></option>
                <option value="failed" <?php selected($current_status, 'failed'); ?>><?php esc_html_e('Failed', 'peanut-suite'); ?></option>
                <option value="ignored" <?php selected($current_status, 'ignored'); ?>><?php esc_html_e('Ignored', 'peanut-suite'); ?></option>
            </select>
            <?php if (!empty($sources)): ?>
            <select name="source">
                <option value=""><?php esc_html_e('All Sources', 'peanut-suite'); ?></option>
                <?php foreach ($sources as $source): ?>
                    <option value="<?php echo esc_attr($source); ?>" <?php selected($current_source, $source); ?>>
                        <?php echo esc_html(ucwords(str_replace('_', ' ', $source))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <?php submit_button(__('Filter', 'peanut-suite'), '', 'filter_action', false); ?>
        </div>
        <?php
    }
}

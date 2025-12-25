<?php
/**
 * Contacts List Table
 *
 * Displays contacts/leads in a WordPress admin table.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Peanut_Contacts_List_Table extends WP_List_Table {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'contact',
            'plural' => 'contacts',
            'ajax' => false,
        ]);
    }

    /**
     * Get columns
     */
    public function get_columns(): array {
        return [
            'cb' => '<input type="checkbox" />',
            'email' => __('Email', 'peanut-suite'),
            'name' => __('Name', 'peanut-suite'),
            'company' => __('Company', 'peanut-suite'),
            'status' => __('Status', 'peanut-suite'),
            'source' => __('Source', 'peanut-suite'),
            'tags' => __('Tags', 'peanut-suite'),
            'created_at' => __('Added', 'peanut-suite'),
        ];
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns(): array {
        return [
            'email' => ['email', false],
            'name' => ['first_name', false],
            'company' => ['company', false],
            'status' => ['status', false],
            'created_at' => ['created_at', true],
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
            '<input type="checkbox" name="contact_ids[]" value="%d" />',
            $item['id']
        );
    }

    /**
     * Email column with actions
     */
    public function column_email($item): string {
        $actions = [
            'edit' => sprintf(
                '<a href="#" class="peanut-edit-contact" data-id="%d">%s</a>',
                $item['id'],
                __('Edit', 'peanut-suite')
            ),
            'view' => sprintf(
                '<a href="mailto:%s">%s</a>',
                esc_attr($item['email']),
                __('Email', 'peanut-suite')
            ),
            'delete' => sprintf(
                '<a href="#" class="peanut-delete-contact" data-id="%d" data-confirm="%s">%s</a>',
                $item['id'],
                esc_attr__('Are you sure you want to delete this contact?', 'peanut-suite'),
                __('Delete', 'peanut-suite')
            ),
        ];

        return sprintf(
            '<div class="peanut-contact-email">
                <strong><a href="#" class="peanut-edit-contact" data-id="%d">%s</a></strong>
            </div>%s',
            $item['id'],
            esc_html($item['email']),
            $this->row_actions($actions)
        );
    }

    /**
     * Name column
     */
    public function column_name($item): string {
        $first = $item['first_name'] ?? '';
        $last = $item['last_name'] ?? '';
        $full = trim("$first $last");

        if (!$full) {
            return '<span class="peanut-text-muted">-</span>';
        }

        return esc_html($full);
    }

    /**
     * Company column
     */
    public function column_company($item): string {
        $company = $item['company'] ?? '';

        if (!$company) {
            return '<span class="peanut-text-muted">-</span>';
        }

        return esc_html($company);
    }

    /**
     * Status column
     */
    public function column_status($item): string {
        $status = $item['status'] ?? 'lead';
        $statuses = [
            'lead' => ['label' => __('Lead', 'peanut-suite'), 'class' => 'peanut-badge-info'],
            'prospect' => ['label' => __('Prospect', 'peanut-suite'), 'class' => 'peanut-badge-warning'],
            'customer' => ['label' => __('Customer', 'peanut-suite'), 'class' => 'peanut-badge-success'],
            'churned' => ['label' => __('Churned', 'peanut-suite'), 'class' => 'peanut-badge-neutral'],
        ];

        $config = $statuses[$status] ?? $statuses['lead'];

        return sprintf(
            '<span class="peanut-badge %s">%s</span>',
            esc_attr($config['class']),
            esc_html($config['label'])
        );
    }

    /**
     * Source column
     */
    public function column_source($item): string {
        $source = $item['source'] ?? '';

        if (!$source) {
            return '<span class="peanut-text-muted">' . __('Unknown', 'peanut-suite') . '</span>';
        }

        // Format source nicely
        $source_labels = [
            'form' => __('Form', 'peanut-suite'),
            'webhook' => __('Webhook', 'peanut-suite'),
            'import' => __('Import', 'peanut-suite'),
            'manual' => __('Manual', 'peanut-suite'),
            'api' => __('API', 'peanut-suite'),
        ];

        $label = $source_labels[$source] ?? ucfirst($source);

        return sprintf(
            '<span class="peanut-source-tag">%s</span>',
            esc_html($label)
        );
    }

    /**
     * Tags column
     */
    public function column_tags($item): string {
        $tags = $item['tags'] ?? '';

        if (!$tags) {
            return '<span class="peanut-text-muted">-</span>';
        }

        // Tags might be comma-separated or JSON array
        if (is_string($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }

        if (empty($tags)) {
            return '<span class="peanut-text-muted">-</span>';
        }

        $output = '';
        foreach (array_slice($tags, 0, 3) as $tag) {
            $output .= sprintf('<span class="peanut-tag">%s</span>', esc_html($tag));
        }

        if (count($tags) > 3) {
            $output .= sprintf(
                '<span class="peanut-tag peanut-tag-more">+%d</span>',
                count($tags) - 3
            );
        }

        return $output;
    }

    /**
     * Created at column
     */
    public function column_created_at($item): string {
        $date = $item['created_at'] ?? '';
        if (!$date) return '-';

        return sprintf(
            '<span title="%s">%s</span>',
            esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($date))),
            esc_html(human_time_diff(strtotime($date), current_time('timestamp')) . ' ' . __('ago', 'peanut-suite'))
        );
    }

    /**
     * Get bulk actions
     */
    public function get_bulk_actions(): array {
        return [
            'delete' => __('Delete', 'peanut-suite'),
            'status_lead' => __('Set Status: Lead', 'peanut-suite'),
            'status_prospect' => __('Set Status: Prospect', 'peanut-suite'),
            'status_customer' => __('Set Status: Customer', 'peanut-suite'),
            'export' => __('Export Selected', 'peanut-suite'),
        ];
    }

    /**
     * Prepare items
     */
    public function prepare_items(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'peanut_contacts';

        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
            $this->items = [];
            return;
        }

        $per_page = 20;
        $current_page = $this->get_pagenum();

        // Build query
        $where = '1=1';
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

        if ($search) {
            $where .= $wpdb->prepare(
                " AND (email LIKE %s OR first_name LIKE %s OR last_name LIKE %s OR company LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
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
        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'created_at';
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
                <span class="dashicons dashicons-groups"></span>
            </div>
            <h3><?php esc_html_e('No contacts yet', 'peanut-suite'); ?></h3>
            <p><?php esc_html_e('Start building your contact list by adding contacts manually, importing from a CSV, or connecting a form webhook.', 'peanut-suite'); ?></p>
            <div class="peanut-empty-actions">
                <button type="button" class="button button-primary" id="peanut-add-contact">
                    <?php esc_html_e('Add Your First Contact', 'peanut-suite'); ?>
                </button>
                <button type="button" class="button" id="peanut-import-contacts">
                    <?php esc_html_e('Import from CSV', 'peanut-suite'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Extra table navigation (filters)
     */
    public function extra_tablenav($which): void {
        if ($which !== 'top') return;
        ?>
        <div class="alignleft actions">
            <select name="status">
                <option value=""><?php esc_html_e('All Statuses', 'peanut-suite'); ?></option>
                <option value="lead" <?php selected($_REQUEST['status'] ?? '', 'lead'); ?>><?php esc_html_e('Lead', 'peanut-suite'); ?></option>
                <option value="prospect" <?php selected($_REQUEST['status'] ?? '', 'prospect'); ?>><?php esc_html_e('Prospect', 'peanut-suite'); ?></option>
                <option value="customer" <?php selected($_REQUEST['status'] ?? '', 'customer'); ?>><?php esc_html_e('Customer', 'peanut-suite'); ?></option>
                <option value="churned" <?php selected($_REQUEST['status'] ?? '', 'churned'); ?>><?php esc_html_e('Churned', 'peanut-suite'); ?></option>
            </select>
            <select name="source">
                <option value=""><?php esc_html_e('All Sources', 'peanut-suite'); ?></option>
                <option value="form" <?php selected($_REQUEST['source'] ?? '', 'form'); ?>><?php esc_html_e('Form', 'peanut-suite'); ?></option>
                <option value="webhook" <?php selected($_REQUEST['source'] ?? '', 'webhook'); ?>><?php esc_html_e('Webhook', 'peanut-suite'); ?></option>
                <option value="import" <?php selected($_REQUEST['source'] ?? '', 'import'); ?>><?php esc_html_e('Import', 'peanut-suite'); ?></option>
                <option value="manual" <?php selected($_REQUEST['source'] ?? '', 'manual'); ?>><?php esc_html_e('Manual', 'peanut-suite'); ?></option>
            </select>
            <?php submit_button(__('Filter', 'peanut-suite'), '', 'filter_action', false); ?>
        </div>
        <?php
    }
}

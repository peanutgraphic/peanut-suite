<?php
/**
 * Popups List Table
 *
 * Displays popups in a WordPress admin table.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Peanut_Popups_List_Table extends WP_List_Table {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'popup',
            'plural' => 'popups',
            'ajax' => false,
        ]);
    }

    /**
     * Get columns
     */
    public function get_columns(): array {
        return [
            'cb' => '<input type="checkbox" />',
            'name' => __('Popup', 'peanut-suite'),
            'type' => __('Type', 'peanut-suite'),
            'status' => __('Status', 'peanut-suite'),
            'views' => __('Views', 'peanut-suite'),
            'conversions' => __('Conversions', 'peanut-suite'),
            'rate' => __('Rate', 'peanut-suite'),
            'created_at' => __('Created', 'peanut-suite'),
        ];
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns(): array {
        return [
            'name' => ['name', false],
            'type' => ['type', false],
            'status' => ['status', false],
            'views' => ['views', true],
            'conversions' => ['conversions', true],
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
            '<input type="checkbox" name="popup_ids[]" value="%d" />',
            $item['id']
        );
    }

    /**
     * Name column with actions
     */
    public function column_name($item): string {
        $edit_url = admin_url('admin.php?page=peanut-popup-builder&id=' . $item['id']);

        $actions = [
            'edit' => sprintf(
                '<a href="%s">%s</a>',
                esc_url($edit_url),
                __('Edit', 'peanut-suite')
            ),
            'duplicate' => sprintf(
                '<a href="#" class="peanut-duplicate-popup" data-id="%d">%s</a>',
                $item['id'],
                __('Duplicate', 'peanut-suite')
            ),
            'preview' => sprintf(
                '<a href="#" class="peanut-preview-popup" data-id="%d">%s</a>',
                $item['id'],
                __('Preview', 'peanut-suite')
            ),
            'delete' => sprintf(
                '<a href="#" class="peanut-delete-popup" data-id="%d" data-confirm="%s">%s</a>',
                $item['id'],
                esc_attr__('Are you sure you want to delete this popup?', 'peanut-suite'),
                __('Delete', 'peanut-suite')
            ),
        ];

        return sprintf(
            '<div class="peanut-popup-name">
                <strong><a href="%s">%s</a></strong>
            </div>%s',
            esc_url($edit_url),
            esc_html($item['name']),
            $this->row_actions($actions)
        );
    }

    /**
     * Type column
     */
    public function column_type($item): string {
        $types = [
            'modal' => ['label' => __('Modal', 'peanut-suite'), 'icon' => 'dashicons-format-chat'],
            'slide-in' => ['label' => __('Slide-in', 'peanut-suite'), 'icon' => 'dashicons-migrate'],
            'bar' => ['label' => __('Bar', 'peanut-suite'), 'icon' => 'dashicons-minus'],
            'fullscreen' => ['label' => __('Fullscreen', 'peanut-suite'), 'icon' => 'dashicons-fullscreen-alt'],
        ];

        $type = $item['type'] ?? 'modal';
        $type_info = $types[$type] ?? $types['modal'];

        return sprintf(
            '<span class="peanut-popup-type"><span class="dashicons %s"></span> %s</span>',
            esc_attr($type_info['icon']),
            esc_html($type_info['label'])
        );
    }

    /**
     * Status column
     */
    public function column_status($item): string {
        $status = $item['status'] ?? 'draft';
        $classes = [
            'active' => 'peanut-badge-success',
            'paused' => 'peanut-badge-warning',
            'draft' => 'peanut-badge-neutral',
            'archived' => 'peanut-badge-neutral',
        ];

        $labels = [
            'active' => __('Active', 'peanut-suite'),
            'paused' => __('Paused', 'peanut-suite'),
            'draft' => __('Draft', 'peanut-suite'),
            'archived' => __('Archived', 'peanut-suite'),
        ];

        return sprintf(
            '<span class="peanut-badge %s">%s</span>',
            esc_attr($classes[$status] ?? 'peanut-badge-neutral'),
            esc_html($labels[$status] ?? ucfirst($status))
        );
    }

    /**
     * Views column
     */
    public function column_views($item): string {
        return sprintf(
            '<span class="peanut-stat-number">%s</span>',
            number_format_i18n($item['views'] ?? 0)
        );
    }

    /**
     * Conversions column
     */
    public function column_conversions($item): string {
        return sprintf(
            '<span class="peanut-stat-number">%s</span>',
            number_format_i18n($item['conversions'] ?? 0)
        );
    }

    /**
     * Conversion rate column
     */
    public function column_rate($item): string {
        $views = (int) ($item['views'] ?? 0);
        $conversions = (int) ($item['conversions'] ?? 0);

        if ($views === 0) {
            return '<span class="peanut-text-muted">-</span>';
        }

        $rate = ($conversions / $views) * 100;

        $color_class = 'peanut-text-muted';
        if ($rate >= 5) $color_class = 'peanut-text-success';
        elseif ($rate >= 2) $color_class = 'peanut-text-warning';

        return sprintf(
            '<span class="%s"><strong>%s%%</strong></span>',
            esc_attr($color_class),
            number_format($rate, 1)
        );
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
            'activate' => __('Activate', 'peanut-suite'),
            'pause' => __('Pause', 'peanut-suite'),
            'delete' => __('Delete', 'peanut-suite'),
        ];
    }

    /**
     * Prepare items
     */
    public function prepare_items(): void {
        global $wpdb;

        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-database.php';
        $table_name = Popups_Database::popups_table();

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
                " AND (name LIKE %s OR title LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        // Status filter
        $status_filter = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
        if ($status_filter) {
            $where .= $wpdb->prepare(" AND status = %s", $status_filter);
        }

        // Type filter
        $type_filter = isset($_REQUEST['type']) ? sanitize_text_field($_REQUEST['type']) : '';
        if ($type_filter) {
            $where .= $wpdb->prepare(" AND type = %s", $type_filter);
        }

        // Sorting
        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'created_at';
        if (!$orderby) $orderby = 'created_at';
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
                <span class="dashicons dashicons-format-chat"></span>
            </div>
            <h3><?php esc_html_e('No popups yet', 'peanut-suite'); ?></h3>
            <p><?php esc_html_e('Create your first popup to start capturing leads and growing your email list.', 'peanut-suite'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-popup-builder')); ?>" class="button button-primary">
                <?php esc_html_e('Create Your First Popup', 'peanut-suite'); ?>
            </a>
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
                <option value="active" <?php selected($_REQUEST['status'] ?? '', 'active'); ?>><?php esc_html_e('Active', 'peanut-suite'); ?></option>
                <option value="paused" <?php selected($_REQUEST['status'] ?? '', 'paused'); ?>><?php esc_html_e('Paused', 'peanut-suite'); ?></option>
                <option value="draft" <?php selected($_REQUEST['status'] ?? '', 'draft'); ?>><?php esc_html_e('Draft', 'peanut-suite'); ?></option>
                <option value="archived" <?php selected($_REQUEST['status'] ?? '', 'archived'); ?>><?php esc_html_e('Archived', 'peanut-suite'); ?></option>
            </select>
            <select name="type">
                <option value=""><?php esc_html_e('All Types', 'peanut-suite'); ?></option>
                <option value="modal" <?php selected($_REQUEST['type'] ?? '', 'modal'); ?>><?php esc_html_e('Modal', 'peanut-suite'); ?></option>
                <option value="slide-in" <?php selected($_REQUEST['type'] ?? '', 'slide-in'); ?>><?php esc_html_e('Slide-in', 'peanut-suite'); ?></option>
                <option value="bar" <?php selected($_REQUEST['type'] ?? '', 'bar'); ?>><?php esc_html_e('Bar', 'peanut-suite'); ?></option>
                <option value="fullscreen" <?php selected($_REQUEST['type'] ?? '', 'fullscreen'); ?>><?php esc_html_e('Fullscreen', 'peanut-suite'); ?></option>
            </select>
            <?php submit_button(__('Filter', 'peanut-suite'), '', 'filter_action', false); ?>
        </div>
        <?php
    }
}

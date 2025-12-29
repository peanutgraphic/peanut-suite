<?php
/**
 * Links List Table
 *
 * Displays short links in a WordPress admin table.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Peanut_Links_List_Table extends WP_List_Table {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'link',
            'plural' => 'links',
            'ajax' => false,
        ]);
    }

    /**
     * Get columns
     */
    public function get_columns(): array {
        return [
            'cb' => '<input type="checkbox" />',
            'title' => __('Link', 'peanut-suite'),
            'destination' => __('Destination', 'peanut-suite'),
            'clicks' => __('Clicks', 'peanut-suite'),
            'status' => __('Status', 'peanut-suite'),
            'created_at' => __('Created', 'peanut-suite'),
        ];
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns(): array {
        return [
            'title' => ['title', false],
            'clicks' => ['click_count', true],
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
            '<input type="checkbox" name="link_ids[]" value="%d" />',
            $item['id']
        );
    }

    /**
     * Title column with actions
     */
    public function column_title($item): string {
        $short_url = home_url('/go/' . $item['slug']);

        $actions = [
            'copy' => sprintf(
                '<a href="#" class="peanut-copy-link" data-copy="%s">%s</a>',
                esc_url($short_url),
                __('Copy', 'peanut-suite')
            ),
            'qr' => sprintf(
                '<a href="#" class="peanut-qr-link" data-url="%s">%s</a>',
                esc_url($short_url),
                __('QR Code', 'peanut-suite')
            ),
            'visit' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url($short_url),
                __('Visit', 'peanut-suite')
            ),
            'delete' => sprintf(
                '<a href="#" class="peanut-delete-link" data-id="%d" data-confirm="%s">%s</a>',
                $item['id'],
                esc_attr__('Are you sure you want to delete this link?', 'peanut-suite'),
                __('Delete', 'peanut-suite')
            ),
        ];

        $title = !empty($item['title']) ? $item['title'] : $item['slug'];

        return sprintf(
            '<div class="peanut-link-title">
                <strong>%s</strong>
                <div class="peanut-link-url">
                    <code>%s</code>
                </div>
            </div>%s',
            esc_html($title),
            esc_html($short_url),
            $this->row_actions($actions)
        );
    }

    /**
     * Destination column
     */
    public function column_destination($item): string {
        $url = $item['destination_url'] ?? '';
        $display = strlen($url) > 50 ? substr($url, 0, 50) . '...' : $url;

        return sprintf(
            '<a href="%s" target="_blank" title="%s">%s</a>',
            esc_url($url),
            esc_attr($url),
            esc_html($display)
        );
    }

    /**
     * Clicks column
     */
    public function column_clicks($item): string {
        return sprintf(
            '<span class="peanut-click-count">%s</span>',
            number_format_i18n($item['click_count'] ?? 0)
        );
    }

    /**
     * Status column
     */
    public function column_status($item): string {
        // Determine status from is_active and expires_at columns
        $is_active = (int) ($item['is_active'] ?? 1);
        $expires_at = $item['expires_at'] ?? null;

        $status = 'active';
        if (!$is_active) {
            $status = 'inactive';
        } elseif ($expires_at && strtotime($expires_at) < time()) {
            $status = 'expired';
        }

        $classes = [
            'active' => 'peanut-badge-success',
            'inactive' => 'peanut-badge-neutral',
            'expired' => 'peanut-badge-warning',
        ];

        return sprintf(
            '<span class="peanut-badge %s">%s</span>',
            esc_attr($classes[$status] ?? 'peanut-badge-neutral'),
            esc_html(ucfirst($status))
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
            'delete' => __('Delete', 'peanut-suite'),
        ];
    }

    /**
     * Prepare items
     */
    public function prepare_items(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'peanut_links';

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
                " AND (title LIKE %s OR slug LIKE %s OR destination_url LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        // Status filter (based on is_active and expires_at)
        $status_filter = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
        if ($status_filter === 'active') {
            $where .= " AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())";
        } elseif ($status_filter === 'inactive') {
            $where .= " AND is_active = 0";
        } elseif ($status_filter === 'expired') {
            $where .= " AND expires_at IS NOT NULL AND expires_at <= NOW()";
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
                <span class="dashicons dashicons-admin-links"></span>
            </div>
            <h3><?php esc_html_e('No links yet', 'peanut-suite'); ?></h3>
            <p><?php esc_html_e('Create your first short link to start tracking clicks and sharing cleaner URLs.', 'peanut-suite'); ?></p>
            <button type="button" class="button button-primary" id="peanut-add-link">
                <?php esc_html_e('Create Your First Link', 'peanut-suite'); ?>
            </button>
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
                <option value="inactive" <?php selected($_REQUEST['status'] ?? '', 'inactive'); ?>><?php esc_html_e('Inactive', 'peanut-suite'); ?></option>
                <option value="expired" <?php selected($_REQUEST['status'] ?? '', 'expired'); ?>><?php esc_html_e('Expired', 'peanut-suite'); ?></option>
            </select>
            <?php submit_button(__('Filter', 'peanut-suite'), '', 'filter_action', false); ?>
        </div>
        <?php
    }
}

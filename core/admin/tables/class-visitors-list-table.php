<?php
/**
 * Visitors List Table
 *
 * Displays website visitors in a WordPress admin table.
 * Pro feature - requires Pro or Agency license.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Peanut_Visitors_List_Table extends WP_List_Table {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'visitor',
            'plural' => 'visitors',
            'ajax' => false,
        ]);
    }

    /**
     * Get columns
     */
    public function get_columns(): array {
        return [
            'cb' => '<input type="checkbox" />',
            'visitor' => __('Visitor', 'peanut-suite'),
            'status' => __('Status', 'peanut-suite'),
            'source' => __('Source', 'peanut-suite'),
            'visits' => __('Visits', 'peanut-suite'),
            'pageviews' => __('Pageviews', 'peanut-suite'),
            'last_seen' => __('Last Seen', 'peanut-suite'),
            'actions' => __('Actions', 'peanut-suite'),
        ];
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns(): array {
        return [
            'visits' => ['visit_count', true],
            'pageviews' => ['pageview_count', true],
            'last_seen' => ['last_seen_at', true],
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
            '<input type="checkbox" name="visitor_ids[]" value="%s" />',
            $item['visitor_id']
        );
    }

    /**
     * Visitor column
     */
    public function column_visitor($item): string {
        $email = $item['email'] ?? null;
        $visitor_id = $item['visitor_id'] ?? 'Unknown';

        // Determine display info
        if ($email) {
            $display = esc_html($email);
            $avatar = get_avatar($email, 32);
        } else {
            $display = sprintf(__('Anonymous (%s)', 'peanut-suite'), substr($visitor_id, 0, 8));
            $avatar = '<div class="peanut-avatar-placeholder"><span class="dashicons dashicons-admin-users"></span></div>';
        }

        // Location if available
        $location = '';
        if (!empty($item['country'])) {
            $location = '<span class="peanut-visitor-location">';
            $location .= esc_html($item['city'] ?? '') . ', ' . esc_html($item['country']);
            $location .= '</span>';
        }

        return sprintf(
            '<div class="peanut-visitor-cell">
                <div class="peanut-visitor-avatar">%s</div>
                <div class="peanut-visitor-info">
                    <strong>%s</strong>
                    %s
                </div>
            </div>',
            $avatar,
            $display,
            $location
        );
    }

    /**
     * Status column
     */
    public function column_status($item): string {
        $email = $item['email'] ?? null;
        $contact_id = $item['contact_id'] ?? null;

        if ($email && $contact_id) {
            return '<span class="peanut-badge peanut-badge-success">' . __('Identified', 'peanut-suite') . '</span>';
        } elseif ($email) {
            return '<span class="peanut-badge peanut-badge-info">' . __('Known', 'peanut-suite') . '</span>';
        }

        return '<span class="peanut-badge peanut-badge-neutral">' . __('Anonymous', 'peanut-suite') . '</span>';
    }

    /**
     * Source column
     */
    public function column_source($item): string {
        $source = $item['utm_source'] ?? $item['referrer'] ?? '';

        if (!$source) {
            return '<span class="peanut-text-muted">' . __('Direct', 'peanut-suite') . '</span>';
        }

        // Extract domain from referrer if it's a URL
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            $parsed = wp_parse_url($source);
            $source = $parsed['host'] ?? $source;
        }

        return sprintf(
            '<span class="peanut-source-tag">%s</span>',
            esc_html(ucfirst($source))
        );
    }

    /**
     * Visits column
     */
    public function column_visits($item): string {
        return sprintf(
            '<span class="peanut-stat-number">%s</span>',
            number_format_i18n($item['visit_count'] ?? 1)
        );
    }

    /**
     * Pageviews column
     */
    public function column_pageviews($item): string {
        return sprintf(
            '<span class="peanut-stat-number">%s</span>',
            number_format_i18n($item['pageview_count'] ?? 0)
        );
    }

    /**
     * Last seen column
     */
    public function column_last_seen($item): string {
        $date = $item['last_seen_at'] ?? $item['created_at'] ?? '';
        if (!$date) return '-';

        $timestamp = strtotime($date);
        $diff = current_time('timestamp') - $timestamp;

        // Show "Active" if seen in last 5 minutes
        if ($diff < 300) {
            return '<span class="peanut-badge peanut-badge-success peanut-badge-pulse">' . __('Active', 'peanut-suite') . '</span>';
        }

        return sprintf(
            '<span title="%s">%s</span>',
            esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp)),
            esc_html(human_time_diff($timestamp, current_time('timestamp')) . ' ' . __('ago', 'peanut-suite'))
        );
    }

    /**
     * Actions column
     */
    public function column_actions($item): string {
        $visitor_id = $item['visitor_id'];

        $actions = [];

        // View timeline
        $actions[] = sprintf(
            '<a href="%s" class="peanut-action-btn" title="%s">
                <span class="dashicons dashicons-visibility"></span>
            </a>',
            esc_url(admin_url('admin.php?page=peanut-visitor-detail&visitor=' . urlencode($visitor_id))),
            esc_attr__('View Timeline', 'peanut-suite')
        );

        // Convert to contact (if not already)
        if (empty($item['contact_id']) && !empty($item['email'])) {
            $actions[] = sprintf(
                '<a href="#" class="peanut-action-btn peanut-convert-visitor" data-id="%s" data-email="%s" title="%s">
                    <span class="dashicons dashicons-groups"></span>
                </a>',
                esc_attr($visitor_id),
                esc_attr($item['email']),
                esc_attr__('Add to Contacts', 'peanut-suite')
            );
        }

        return '<div class="peanut-row-actions">' . implode('', $actions) . '</div>';
    }

    /**
     * Get bulk actions
     */
    public function get_bulk_actions(): array {
        return [
            'delete' => __('Delete', 'peanut-suite'),
            'export' => __('Export', 'peanut-suite'),
        ];
    }

    /**
     * Prepare items
     */
    public function prepare_items(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'peanut_visitors';

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
                " AND (email LIKE %s OR visitor_id LIKE %s OR country LIKE %s OR city LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        // Status filter
        $status_filter = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
        if ($status_filter === 'identified') {
            $where .= " AND email IS NOT NULL AND contact_id IS NOT NULL";
        } elseif ($status_filter === 'known') {
            $where .= " AND email IS NOT NULL";
        } elseif ($status_filter === 'anonymous') {
            $where .= " AND email IS NULL";
        }

        // Sorting
        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'last_seen_at';
        if (!$orderby) $orderby = 'last_seen_at';
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
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <h3><?php esc_html_e('No visitors tracked yet', 'peanut-suite'); ?></h3>
            <p><?php esc_html_e('Install the tracking snippet on your site to start recording visitor activity. Visitors will appear here as they browse your site.', 'peanut-suite'); ?></p>
            <button type="button" class="button button-primary" id="peanut-get-snippet">
                <?php esc_html_e('Get Tracking Code', 'peanut-suite'); ?>
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
            <?php $current_status = sanitize_text_field($_REQUEST['status'] ?? ''); ?>
            <select name="status">
                <option value=""><?php esc_html_e('All Visitors', 'peanut-suite'); ?></option>
                <option value="identified" <?php selected($current_status, 'identified'); ?>><?php esc_html_e('Identified', 'peanut-suite'); ?></option>
                <option value="known" <?php selected($current_status, 'known'); ?>><?php esc_html_e('Known (Email)', 'peanut-suite'); ?></option>
                <option value="anonymous" <?php selected($current_status, 'anonymous'); ?>><?php esc_html_e('Anonymous', 'peanut-suite'); ?></option>
            </select>
            <?php submit_button(__('Filter', 'peanut-suite'), '', 'filter_action', false); ?>
        </div>
        <?php
    }
}

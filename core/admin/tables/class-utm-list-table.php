<?php
/**
 * UTM List Table
 *
 * Displays UTM campaigns in a WordPress admin table.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Peanut_UTM_List_Table extends WP_List_Table {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'utm',
            'plural' => 'utms',
            'ajax' => false,
        ]);
    }

    /**
     * Get columns
     */
    public function get_columns(): array {
        return [
            'cb' => '<input type="checkbox" />',
            'campaign' => __('Campaign', 'peanut-suite'),
            'url' => __('URL', 'peanut-suite'),
            'source' => __('Source', 'peanut-suite'),
            'medium' => __('Medium', 'peanut-suite'),
            'clicks' => __('Clicks', 'peanut-suite'),
            'created_at' => __('Created', 'peanut-suite'),
        ];
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns(): array {
        return [
            'campaign' => ['utm_campaign', false],
            'source' => ['utm_source', false],
            'medium' => ['utm_medium', false],
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
            '<input type="checkbox" name="utm_ids[]" value="%d" />',
            $item['id']
        );
    }

    /**
     * Campaign column with actions
     */
    public function column_campaign($item): string {
        $campaign = $item['utm_campaign'] ?? __('(no campaign)', 'peanut-suite');
        $full_url = $item['full_url'] ?? '';

        $actions = [
            'copy' => sprintf(
                '<a href="#" class="peanut-copy-utm" data-copy="%s">%s</a>',
                esc_attr($full_url),
                __('Copy URL', 'peanut-suite')
            ),
            'open' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url($full_url),
                __('Open', 'peanut-suite')
            ),
            'edit' => sprintf(
                '<a href="#" class="peanut-edit-utm" data-id="%d">%s</a>',
                $item['id'],
                __('Edit', 'peanut-suite')
            ),
            'delete' => sprintf(
                '<a href="#" class="peanut-delete-utm" data-id="%d" data-confirm="%s">%s</a>',
                $item['id'],
                esc_attr__('Are you sure you want to delete this UTM?', 'peanut-suite'),
                __('Delete', 'peanut-suite')
            ),
        ];

        // Campaign title (or fallback to source/medium)
        $title = $item['title'] ?? $campaign;
        if ($title === $campaign && !empty($item['utm_source'])) {
            $title = ucfirst($item['utm_source']) . ' - ' . $campaign;
        }

        return sprintf(
            '<div class="peanut-utm-campaign">
                <strong>%s</strong>
                <div class="peanut-utm-name">%s</div>
            </div>%s',
            esc_html($title),
            esc_html($campaign),
            $this->row_actions($actions)
        );
    }

    /**
     * URL column
     */
    public function column_url($item): string {
        $base_url = $item['base_url'] ?? '';
        $full_url = $item['full_url'] ?? '';

        // Parse and display just the domain/path
        $parsed = wp_parse_url($base_url);
        $display = ($parsed['host'] ?? '') . ($parsed['path'] ?? '');
        $display = strlen($display) > 40 ? substr($display, 0, 40) . '...' : $display;

        return sprintf(
            '<a href="%s" target="_blank" title="%s" class="peanut-utm-url">%s</a>',
            esc_url($full_url),
            esc_attr($full_url),
            esc_html($display ?: $base_url)
        );
    }

    /**
     * Source column
     */
    public function column_source($item): string {
        $source = $item['utm_source'] ?? '';

        if (!$source) {
            return '<span class="peanut-text-muted">-</span>';
        }

        // Color-code common sources
        $source_colors = [
            'google' => '#4285f4',
            'facebook' => '#1877f2',
            'instagram' => '#e4405f',
            'twitter' => '#1da1f2',
            'linkedin' => '#0a66c2',
            'email' => '#ea4335',
            'newsletter' => '#34a853',
        ];

        $color = $source_colors[strtolower($source)] ?? '#666';

        return sprintf(
            '<span class="peanut-source-badge" style="border-left-color: %s;">%s</span>',
            esc_attr($color),
            esc_html(ucfirst($source))
        );
    }

    /**
     * Medium column
     */
    public function column_medium($item): string {
        $medium = $item['utm_medium'] ?? '';

        if (!$medium) {
            return '<span class="peanut-text-muted">-</span>';
        }

        return sprintf(
            '<span class="peanut-medium-tag">%s</span>',
            esc_html($medium)
        );
    }

    /**
     * Clicks column
     */
    public function column_clicks($item): string {
        $clicks = $item['click_count'] ?? 0;

        return sprintf(
            '<span class="peanut-click-count">%s</span>',
            number_format_i18n($clicks)
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
            'export' => __('Export CSV', 'peanut-suite'),
        ];
    }

    /**
     * Prepare items
     */
    public function prepare_items(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'peanut_utms';

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
                " AND (utm_campaign LIKE %s OR utm_source LIKE %s OR utm_medium LIKE %s OR base_url LIKE %s OR title LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        // Source filter
        $source_filter = isset($_REQUEST['utm_source']) ? sanitize_text_field($_REQUEST['utm_source']) : '';
        if ($source_filter) {
            $where .= $wpdb->prepare(" AND utm_source = %s", $source_filter);
        }

        // Medium filter
        $medium_filter = isset($_REQUEST['utm_medium']) ? sanitize_text_field($_REQUEST['utm_medium']) : '';
        if ($medium_filter) {
            $where .= $wpdb->prepare(" AND utm_medium = %s", $medium_filter);
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
                <span class="dashicons dashicons-tag"></span>
            </div>
            <h3><?php esc_html_e('No UTM campaigns yet', 'peanut-suite'); ?></h3>
            <p><?php esc_html_e('Create your first tracked URL using the UTM Builder. Saved UTMs will appear here.', 'peanut-suite'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-utm-builder')); ?>" class="button button-primary">
                <?php esc_html_e('Create Your First UTM', 'peanut-suite'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Extra table navigation (filters)
     */
    public function extra_tablenav($which): void {
        if ($which !== 'top') return;

        global $wpdb;
        $table_name = $wpdb->prefix . 'peanut_utms';

        // Get unique sources and mediums
        $sources = [];
        $mediums = [];

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
            $sources = $wpdb->get_col("SELECT DISTINCT utm_source FROM $table_name WHERE utm_source IS NOT NULL AND utm_source != '' ORDER BY utm_source");
            $mediums = $wpdb->get_col("SELECT DISTINCT utm_medium FROM $table_name WHERE utm_medium IS NOT NULL AND utm_medium != '' ORDER BY utm_medium");
        }
        ?>
        <div class="alignleft actions">
            <?php if (!empty($sources)): ?>
            <select name="utm_source">
                <option value=""><?php esc_html_e('All Sources', 'peanut-suite'); ?></option>
                <?php foreach ($sources as $source): ?>
                    <option value="<?php echo esc_attr($source); ?>" <?php selected($_REQUEST['utm_source'] ?? '', $source); ?>>
                        <?php echo esc_html(ucfirst($source)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <?php if (!empty($mediums)): ?>
            <select name="utm_medium">
                <option value=""><?php esc_html_e('All Mediums', 'peanut-suite'); ?></option>
                <?php foreach ($mediums as $medium): ?>
                    <option value="<?php echo esc_attr($medium); ?>" <?php selected($_REQUEST['utm_medium'] ?? '', $medium); ?>>
                        <?php echo esc_html($medium); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <?php submit_button(__('Filter', 'peanut-suite'), '', 'filter_action', false); ?>
        </div>
        <?php
    }
}

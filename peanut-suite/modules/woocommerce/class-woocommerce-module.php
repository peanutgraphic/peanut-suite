<?php
/**
 * WooCommerce Revenue Attribution Module
 *
 * Track revenue by marketing campaign and source.
 */

namespace PeanutSuite\WooCommerce;

if (!defined('ABSPATH')) {
    exit;
}

class WooCommerce_Module {

    private static ?self $instance = null;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        if (!$this->is_woocommerce_active()) {
            return;
        }
        $this->init();
    }

    private function is_woocommerce_active(): bool {
        return class_exists('WooCommerce');
    }

    private function init(): void {
        // Track UTM on order creation
        add_action('woocommerce_checkout_order_processed', [$this, 'capture_attribution'], 10, 3);
        add_action('woocommerce_store_api_checkout_order_processed', [$this, 'capture_attribution_block']);

        // Display attribution in admin
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'display_order_attribution']);

        // REST API
        add_action('rest_api_init', [$this, 'register_routes']);

        // Dashboard widget
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
    }

    /**
     * Register REST routes
     */
    public function register_routes(): void {
        register_rest_route(PEANUT_API_NAMESPACE, '/woocommerce/revenue', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'get_revenue_stats'],
            'permission_callback' => [$this, 'admin_permission'],
        ]);

        register_rest_route(PEANUT_API_NAMESPACE, '/woocommerce/attribution', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'get_attribution_report'],
            'permission_callback' => [$this, 'admin_permission'],
        ]);

        register_rest_route(PEANUT_API_NAMESPACE, '/woocommerce/orders', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'get_attributed_orders'],
            'permission_callback' => [$this, 'admin_permission'],
        ]);
    }

    public function admin_permission(): bool {
        return current_user_can('manage_options');
    }

    /**
     * Capture UTM attribution on checkout
     */
    public function capture_attribution(int $order_id, array $posted_data, \WC_Order $order): void {
        $this->save_order_attribution($order);
    }

    /**
     * Capture attribution for block checkout
     */
    public function capture_attribution_block(\WC_Order $order): void {
        $this->save_order_attribution($order);
    }

    /**
     * Save attribution data to order
     */
    private function save_order_attribution(\WC_Order $order): void {
        // Get UTM data from cookies or session
        $utm_data = $this->get_visitor_utm_data();

        if (!empty($utm_data)) {
            $order->update_meta_data('_peanut_utm_source', $utm_data['source'] ?? '');
            $order->update_meta_data('_peanut_utm_medium', $utm_data['medium'] ?? '');
            $order->update_meta_data('_peanut_utm_campaign', $utm_data['campaign'] ?? '');
            $order->update_meta_data('_peanut_utm_term', $utm_data['term'] ?? '');
            $order->update_meta_data('_peanut_utm_content', $utm_data['content'] ?? '');
            $order->update_meta_data('_peanut_first_visit', $utm_data['first_visit'] ?? '');
            $order->update_meta_data('_peanut_landing_page', $utm_data['landing_page'] ?? '');
            $order->save();
        }

        // Get referrer
        $referrer = wp_get_referer() ?: ($_COOKIE['peanut_referrer'] ?? '');
        if ($referrer) {
            $order->update_meta_data('_peanut_referrer', $referrer);
            $order->save();
        }

        // Get visitor ID
        $visitor_id = $_COOKIE['peanut_visitor_id'] ?? '';
        if ($visitor_id) {
            $order->update_meta_data('_peanut_visitor_id', $visitor_id);
            $order->save();
        }

        // Store in attribution table
        $this->store_attribution_record($order);
    }

    /**
     * Get visitor UTM data from cookies
     */
    private function get_visitor_utm_data(): array {
        $utm = [];

        // Check cookies first
        $cookie_name = 'peanut_utm_data';
        if (isset($_COOKIE[$cookie_name])) {
            $data = json_decode(stripslashes($_COOKIE[$cookie_name]), true);
            if (is_array($data)) {
                $utm = $data;
            }
        }

        // Fall back to current URL params
        if (empty($utm)) {
            $utm = [
                'source' => sanitize_text_field($_GET['utm_source'] ?? ''),
                'medium' => sanitize_text_field($_GET['utm_medium'] ?? ''),
                'campaign' => sanitize_text_field($_GET['utm_campaign'] ?? ''),
                'term' => sanitize_text_field($_GET['utm_term'] ?? ''),
                'content' => sanitize_text_field($_GET['utm_content'] ?? ''),
            ];
        }

        return array_filter($utm);
    }

    /**
     * Store attribution record
     */
    private function store_attribution_record(\WC_Order $order): void {
        global $wpdb;
        $table = $wpdb->prefix . 'peanut_woo_attribution';

        $wpdb->insert($table, [
            'order_id' => $order->get_id(),
            'customer_id' => $order->get_customer_id(),
            'customer_email' => $order->get_billing_email(),
            'order_total' => $order->get_total(),
            'utm_source' => $order->get_meta('_peanut_utm_source'),
            'utm_medium' => $order->get_meta('_peanut_utm_medium'),
            'utm_campaign' => $order->get_meta('_peanut_utm_campaign'),
            'utm_term' => $order->get_meta('_peanut_utm_term'),
            'utm_content' => $order->get_meta('_peanut_utm_content'),
            'referrer' => $order->get_meta('_peanut_referrer'),
            'visitor_id' => $order->get_meta('_peanut_visitor_id'),
            'landing_page' => $order->get_meta('_peanut_landing_page'),
            'created_at' => current_time('mysql'),
        ]);
    }

    /**
     * Display attribution in order admin
     */
    public function display_order_attribution(\WC_Order $order): void {
        $source = $order->get_meta('_peanut_utm_source');
        $medium = $order->get_meta('_peanut_utm_medium');
        $campaign = $order->get_meta('_peanut_utm_campaign');
        $referrer = $order->get_meta('_peanut_referrer');

        if (!$source && !$referrer) {
            return;
        }

        echo '<div class="peanut-order-attribution" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">';
        echo '<h4 style="margin: 0 0 10px;"><span class="dashicons dashicons-chart-line"></span> Marketing Attribution</h4>';

        if ($source) {
            echo '<p style="margin: 5px 0;"><strong>Source:</strong> ' . esc_html($source) . '</p>';
        }
        if ($medium) {
            echo '<p style="margin: 5px 0;"><strong>Medium:</strong> ' . esc_html($medium) . '</p>';
        }
        if ($campaign) {
            echo '<p style="margin: 5px 0;"><strong>Campaign:</strong> ' . esc_html($campaign) . '</p>';
        }
        if ($referrer) {
            $domain = wp_parse_url($referrer, PHP_URL_HOST);
            echo '<p style="margin: 5px 0;"><strong>Referrer:</strong> ' . esc_html($domain) . '</p>';
        }

        echo '</div>';
    }

    /**
     * Get revenue stats
     */
    public function get_revenue_stats(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'peanut_woo_attribution';

        $days = (int) ($request->get_param('days') ?: 30);
        $date_from = date('Y-m-d', strtotime("-$days days"));

        // Total attributed revenue
        $total_revenue = $wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(order_total), 0)
            FROM $table
            WHERE created_at >= %s
            AND utm_source != ''
        ", $date_from));

        // Revenue by source
        $by_source = $wpdb->get_results($wpdb->prepare("
            SELECT
                utm_source as source,
                COUNT(*) as orders,
                SUM(order_total) as revenue
            FROM $table
            WHERE created_at >= %s
            AND utm_source != ''
            GROUP BY utm_source
            ORDER BY revenue DESC
            LIMIT 10
        ", $date_from), ARRAY_A);

        // Revenue by campaign
        $by_campaign = $wpdb->get_results($wpdb->prepare("
            SELECT
                utm_campaign as campaign,
                utm_source as source,
                COUNT(*) as orders,
                SUM(order_total) as revenue
            FROM $table
            WHERE created_at >= %s
            AND utm_campaign != ''
            GROUP BY utm_campaign, utm_source
            ORDER BY revenue DESC
            LIMIT 10
        ", $date_from), ARRAY_A);

        // Daily revenue
        $daily = $wpdb->get_results($wpdb->prepare("
            SELECT
                DATE(created_at) as date,
                SUM(order_total) as revenue,
                COUNT(*) as orders
            FROM $table
            WHERE created_at >= %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", $date_from), ARRAY_A);

        // Total orders for comparison
        $total_woo_orders = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            AND post_status IN ('wc-completed', 'wc-processing')
            AND post_date >= %s
        ", $date_from));

        $attributed_orders = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM $table
            WHERE created_at >= %s
            AND utm_source != ''
        ", $date_from));

        return new \WP_REST_Response([
            'total_revenue' => (float) $total_revenue,
            'by_source' => $by_source,
            'by_campaign' => $by_campaign,
            'daily' => $daily,
            'attribution_rate' => $total_woo_orders > 0
                ? round(($attributed_orders / $total_woo_orders) * 100, 1)
                : 0,
            'total_orders' => (int) $total_woo_orders,
            'attributed_orders' => (int) $attributed_orders,
        ], 200);
    }

    /**
     * Get attribution report
     */
    public function get_attribution_report(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'peanut_woo_attribution';

        $days = (int) ($request->get_param('days') ?: 30);
        $group_by = $request->get_param('group_by') ?: 'source';
        $date_from = date('Y-m-d', strtotime("-$days days"));

        $group_column = match($group_by) {
            'medium' => 'utm_medium',
            'campaign' => 'utm_campaign',
            default => 'utm_source',
        };

        $report = $wpdb->get_results($wpdb->prepare("
            SELECT
                $group_column as channel,
                COUNT(*) as orders,
                COUNT(DISTINCT customer_email) as customers,
                SUM(order_total) as revenue,
                AVG(order_total) as avg_order_value,
                MIN(created_at) as first_order,
                MAX(created_at) as last_order
            FROM $table
            WHERE created_at >= %s
            AND $group_column != ''
            GROUP BY $group_column
            ORDER BY revenue DESC
        ", $date_from), ARRAY_A);

        return new \WP_REST_Response([
            'report' => $report,
            'group_by' => $group_by,
            'period_days' => $days,
        ], 200);
    }

    /**
     * Get attributed orders
     */
    public function get_attributed_orders(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'peanut_woo_attribution';

        $page = (int) ($request->get_param('page') ?: 1);
        $per_page = (int) ($request->get_param('per_page') ?: 20);
        $offset = ($page - 1) * $per_page;

        $source_filter = sanitize_text_field($request->get_param('source') ?: '');
        $campaign_filter = sanitize_text_field($request->get_param('campaign') ?: '');

        $where = "1=1";
        if ($source_filter) {
            $where .= $wpdb->prepare(" AND utm_source = %s", $source_filter);
        }
        if ($campaign_filter) {
            $where .= $wpdb->prepare(" AND utm_campaign = %s", $campaign_filter);
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where is built with $wpdb->prepare()
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where");

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where is built with $wpdb->prepare()
        $orders = $wpdb->get_results($wpdb->prepare("
            SELECT *
            FROM $table
            WHERE $where
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d
        ", $per_page, $offset), ARRAY_A);

        return new \WP_REST_Response([
            'orders' => $orders,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page),
        ], 200);
    }

    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_add_dashboard_widget(
            'peanut_woo_attribution',
            'Revenue Attribution (Last 7 Days)',
            [$this, 'render_dashboard_widget']
        );
    }

    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'peanut_woo_attribution';
        $date_from = date('Y-m-d', strtotime('-7 days'));

        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT
                COUNT(*) as orders,
                COALESCE(SUM(order_total), 0) as revenue
            FROM $table
            WHERE created_at >= %s
            AND utm_source != ''
        ", $date_from));

        $top_source = $wpdb->get_row($wpdb->prepare("
            SELECT utm_source, SUM(order_total) as revenue
            FROM $table
            WHERE created_at >= %s AND utm_source != ''
            GROUP BY utm_source ORDER BY revenue DESC LIMIT 1
        ", $date_from));

        echo '<div style="padding: 10px 0;">';
        echo '<p><strong>Attributed Revenue:</strong> ' . wc_price($stats->revenue ?? 0) . '</p>';
        echo '<p><strong>Orders with UTM:</strong> ' . intval($stats->orders ?? 0) . '</p>';
        if ($top_source) {
            echo '<p><strong>Top Source:</strong> ' . esc_html($top_source->utm_source) .
                 ' (' . wc_price($top_source->revenue) . ')</p>';
        }
        echo '<p style="margin-top: 15px;"><a href="' . admin_url('admin.php?page=peanut-woocommerce') .
             '" class="button">View Full Report</a></p>';
        echo '</div>';
    }

    /**
     * Create database tables
     */
    public static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'peanut_woo_attribution';

        $sql = "
        CREATE TABLE $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            customer_id bigint(20) UNSIGNED DEFAULT 0,
            customer_email varchar(200) DEFAULT '',
            order_total decimal(10,2) DEFAULT 0,
            utm_source varchar(100) DEFAULT '',
            utm_medium varchar(100) DEFAULT '',
            utm_campaign varchar(200) DEFAULT '',
            utm_term varchar(200) DEFAULT '',
            utm_content varchar(200) DEFAULT '',
            referrer varchar(500) DEFAULT '',
            visitor_id varchar(100) DEFAULT '',
            landing_page varchar(500) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY utm_source (utm_source),
            KEY utm_campaign (utm_campaign(100)),
            KEY created_at (created_at)
        ) $charset;
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}

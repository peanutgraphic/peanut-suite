<?php
/**
 * Invoicing Database
 *
 * Handles invoice data storage and retrieval.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Invoicing_Database {

    /**
     * Get invoices table name
     */
    public static function invoices_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_invoices';
    }

    /**
     * Get invoice items table name
     */
    public static function invoice_items_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_invoice_items';
    }

    /**
     * Create database tables
     */
    public static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Invoices table
        $sql = "CREATE TABLE " . self::invoices_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            stripe_invoice_id varchar(255) DEFAULT NULL,
            stripe_customer_id varchar(255) DEFAULT NULL,
            invoice_number varchar(50) DEFAULT NULL,
            contact_id bigint(20) UNSIGNED DEFAULT NULL,
            client_name varchar(255) NOT NULL,
            client_email varchar(255) NOT NULL,
            client_company varchar(255) DEFAULT NULL,
            client_address text DEFAULT NULL,
            subtotal decimal(10,2) DEFAULT 0,
            tax_amount decimal(10,2) DEFAULT 0,
            tax_percent decimal(5,2) DEFAULT 0,
            discount_amount decimal(10,2) DEFAULT 0,
            total decimal(10,2) DEFAULT 0,
            currency varchar(3) DEFAULT 'USD',
            status varchar(50) DEFAULT 'draft',
            due_date date DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            paid_at datetime DEFAULT NULL,
            payment_url varchar(500) DEFAULT NULL,
            pdf_url varchar(500) DEFAULT NULL,
            notes text DEFAULT NULL,
            footer text DEFAULT NULL,
            metadata text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY stripe_invoice_id (stripe_invoice_id),
            KEY contact_id (contact_id),
            KEY status (status),
            KEY due_date (due_date),
            KEY created_at (created_at)
        ) $charset;";
        dbDelta($sql);

        // Invoice items table
        $sql = "CREATE TABLE " . self::invoice_items_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id bigint(20) UNSIGNED NOT NULL,
            stripe_item_id varchar(255) DEFAULT NULL,
            description varchar(500) NOT NULL,
            quantity decimal(10,2) DEFAULT 1,
            unit_price decimal(10,2) NOT NULL,
            amount decimal(10,2) NOT NULL,
            sort_order int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY invoice_id (invoice_id)
        ) $charset;";
        dbDelta($sql);
    }

    /**
     * Get all invoices
     */
    public static function get_all(array $args = []): array {
        global $wpdb;
        $table = self::invoices_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'status' => '',
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);
        $where = ['user_id = %d'];
        $params = [$args['user_id']];

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }

        if (!empty($args['search'])) {
            $where[] = '(client_name LIKE %s OR client_email LIKE %s OR invoice_number LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $where_sql = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']) ?: 'created_at DESC';

        $sql = $wpdb->prepare(
            "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby LIMIT %d OFFSET %d",
            array_merge($params, [$args['limit'], $args['offset']])
        );

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Count invoices
     */
    public static function count(array $args = []): int {
        global $wpdb;
        $table = self::invoices_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'status' => '',
        ];

        $args = wp_parse_args($args, $defaults);
        $where = ['user_id = %d'];
        $params = [$args['user_id']];

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }

        $where_sql = implode(' AND ', $where);

        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE $where_sql", $params)
        );
    }

    /**
     * Get invoice by ID
     */
    public static function get(int $id): ?array {
        global $wpdb;
        $table = self::invoices_table();

        $invoice = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id),
            ARRAY_A
        );

        if ($invoice) {
            $invoice['items'] = self::get_items($id);
        }

        return $invoice;
    }

    /**
     * Get invoice by Stripe ID
     */
    public static function get_by_stripe_id(string $stripe_id): ?array {
        global $wpdb;
        $table = self::invoices_table();

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE stripe_invoice_id = %s", $stripe_id),
            ARRAY_A
        );
    }

    /**
     * Create invoice
     */
    public static function create(array $data): ?int {
        global $wpdb;
        $table = self::invoices_table();

        $data['user_id'] = $data['user_id'] ?? get_current_user_id();
        $data['created_at'] = current_time('mysql');

        $wpdb->insert($table, $data);

        return $wpdb->insert_id ?: null;
    }

    /**
     * Update invoice
     */
    public static function update(int $id, array $data): bool {
        global $wpdb;
        $table = self::invoices_table();

        $data['updated_at'] = current_time('mysql');

        return $wpdb->update($table, $data, ['id' => $id]) !== false;
    }

    /**
     * Update invoice by Stripe ID
     */
    public static function update_by_stripe_id(string $stripe_id, array $data): bool {
        global $wpdb;
        $table = self::invoices_table();

        $data['updated_at'] = current_time('mysql');

        return $wpdb->update($table, $data, ['stripe_invoice_id' => $stripe_id]) !== false;
    }

    /**
     * Delete invoice
     */
    public static function delete(int $id): bool {
        global $wpdb;

        // Delete items first
        $wpdb->delete(self::invoice_items_table(), ['invoice_id' => $id]);

        // Delete invoice
        return $wpdb->delete(self::invoices_table(), ['id' => $id]) !== false;
    }

    /**
     * Get invoice items
     */
    public static function get_items(int $invoice_id): array {
        global $wpdb;
        $table = self::invoice_items_table();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE invoice_id = %d ORDER BY sort_order ASC",
                $invoice_id
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Add invoice item
     */
    public static function add_item(int $invoice_id, array $item): ?int {
        global $wpdb;
        $table = self::invoice_items_table();

        $item['invoice_id'] = $invoice_id;
        $item['amount'] = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
        $item['created_at'] = current_time('mysql');

        $wpdb->insert($table, $item);

        return $wpdb->insert_id ?: null;
    }

    /**
     * Clear invoice items
     */
    public static function clear_items(int $invoice_id): bool {
        global $wpdb;
        return $wpdb->delete(self::invoice_items_table(), ['invoice_id' => $invoice_id]) !== false;
    }

    /**
     * Get invoice stats
     */
    public static function get_stats(int $user_id = 0): array {
        global $wpdb;
        $table = self::invoices_table();
        $user_id = $user_id ?: get_current_user_id();

        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
                    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
                    SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END) as total_paid,
                    SUM(CASE WHEN status IN ('sent', 'overdue') THEN total ELSE 0 END) as total_outstanding
                FROM $table WHERE user_id = %d",
                $user_id
            ),
            ARRAY_A
        );

        return $stats ?: [
            'total' => 0,
            'draft' => 0,
            'sent' => 0,
            'paid' => 0,
            'overdue' => 0,
            'total_paid' => 0,
            'total_outstanding' => 0,
        ];
    }

    /**
     * Generate next invoice number
     */
    public static function generate_invoice_number(): string {
        global $wpdb;
        $table = self::invoices_table();

        $year = date('Y');
        $prefix = 'INV-' . $year . '-';

        $last_number = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT invoice_number FROM $table WHERE invoice_number LIKE %s ORDER BY id DESC LIMIT 1",
                $prefix . '%'
            )
        );

        if ($last_number) {
            $num = (int) substr($last_number, strlen($prefix));
            $next = $num + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Mark overdue invoices
     */
    public static function mark_overdue(): int {
        global $wpdb;
        $table = self::invoices_table();

        return $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table SET status = 'overdue'
                WHERE status = 'sent' AND due_date < %s",
                date('Y-m-d')
            )
        );
    }
}

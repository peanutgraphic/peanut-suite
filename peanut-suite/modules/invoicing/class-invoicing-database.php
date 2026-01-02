<?php
/**
 * Invoicing Database
 *
 * Handles invoice data storage and retrieval for invoices, quotes, expenses,
 * recurring invoices, and payments.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Invoicing_Database {

    /**
     * Database version for migrations
     */
    private const DB_VERSION = '2.0.0';

    /**
     * Table name helpers
     */
    public static function invoices_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_invoices';
    }

    public static function invoice_items_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_invoice_items';
    }

    public static function quotes_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_quotes';
    }

    public static function quote_items_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_quote_items';
    }

    public static function expenses_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_expenses';
    }

    public static function recurring_invoices_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_recurring_invoices';
    }

    public static function recurring_invoice_items_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_recurring_invoice_items';
    }

    public static function payments_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'peanut_payments';
    }

    /**
     * Create all database tables
     */
    public static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ===== Invoices Table =====
        $sql = "CREATE TABLE " . self::invoices_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            account_id bigint(20) UNSIGNED DEFAULT NULL,
            project_id bigint(20) UNSIGNED NOT NULL,
            invoice_number varchar(50) NOT NULL,
            contact_id bigint(20) UNSIGNED DEFAULT NULL,
            client_name varchar(255) NOT NULL,
            client_email varchar(255) NOT NULL,
            client_company varchar(255) DEFAULT NULL,
            client_address text DEFAULT NULL,
            subtotal decimal(12,2) DEFAULT 0,
            tax_amount decimal(12,2) DEFAULT 0,
            tax_percent decimal(5,2) DEFAULT 0,
            discount_amount decimal(12,2) DEFAULT 0,
            discount_type varchar(10) DEFAULT 'fixed',
            total decimal(12,2) DEFAULT 0,
            amount_paid decimal(12,2) DEFAULT 0,
            balance_due decimal(12,2) DEFAULT 0,
            currency varchar(3) DEFAULT 'USD',
            status varchar(20) DEFAULT 'draft',
            issue_date date DEFAULT NULL,
            due_date date DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            paid_at datetime DEFAULT NULL,
            payment_terms varchar(100) DEFAULT NULL,
            notes text DEFAULT NULL,
            client_notes text DEFAULT NULL,
            footer text DEFAULT NULL,
            source varchar(20) DEFAULT 'manual',
            source_id bigint(20) UNSIGNED DEFAULT NULL,
            stripe_invoice_id varchar(255) DEFAULT NULL,
            stripe_customer_id varchar(255) DEFAULT NULL,
            payment_url varchar(500) DEFAULT NULL,
            pdf_url varchar(500) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY account_id (account_id),
            KEY project_id (project_id),
            KEY contact_id (contact_id),
            KEY status (status),
            KEY due_date (due_date),
            KEY issue_date (issue_date),
            KEY created_at (created_at)
        ) $charset;";
        dbDelta($sql);

        // ===== Invoice Items Table =====
        $sql = "CREATE TABLE " . self::invoice_items_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id bigint(20) UNSIGNED NOT NULL,
            item_type varchar(20) DEFAULT 'service',
            description varchar(500) NOT NULL,
            quantity decimal(10,2) DEFAULT 1,
            hours decimal(10,2) DEFAULT NULL,
            rate decimal(12,2) DEFAULT NULL,
            unit_price decimal(12,2) NOT NULL,
            amount decimal(12,2) NOT NULL,
            taxable tinyint(1) DEFAULT 1,
            sort_order int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY invoice_id (invoice_id)
        ) $charset;";
        dbDelta($sql);

        // ===== Quotes Table =====
        $sql = "CREATE TABLE " . self::quotes_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            account_id bigint(20) UNSIGNED DEFAULT NULL,
            project_id bigint(20) UNSIGNED NOT NULL,
            quote_number varchar(50) NOT NULL,
            contact_id bigint(20) UNSIGNED DEFAULT NULL,
            client_name varchar(255) NOT NULL,
            client_email varchar(255) NOT NULL,
            client_company varchar(255) DEFAULT NULL,
            client_address text DEFAULT NULL,
            subtotal decimal(12,2) DEFAULT 0,
            tax_amount decimal(12,2) DEFAULT 0,
            tax_percent decimal(5,2) DEFAULT 0,
            discount_amount decimal(12,2) DEFAULT 0,
            discount_type varchar(10) DEFAULT 'fixed',
            total decimal(12,2) DEFAULT 0,
            currency varchar(3) DEFAULT 'USD',
            status varchar(20) DEFAULT 'draft',
            valid_until date DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            accepted_at datetime DEFAULT NULL,
            declined_at datetime DEFAULT NULL,
            converted_invoice_id bigint(20) UNSIGNED DEFAULT NULL,
            notes text DEFAULT NULL,
            client_notes text DEFAULT NULL,
            terms text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY account_id (account_id),
            KEY project_id (project_id),
            KEY contact_id (contact_id),
            KEY status (status),
            KEY valid_until (valid_until),
            KEY created_at (created_at)
        ) $charset;";
        dbDelta($sql);

        // ===== Quote Items Table =====
        $sql = "CREATE TABLE " . self::quote_items_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            quote_id bigint(20) UNSIGNED NOT NULL,
            item_type varchar(20) DEFAULT 'service',
            description varchar(500) NOT NULL,
            quantity decimal(10,2) DEFAULT 1,
            hours decimal(10,2) DEFAULT NULL,
            rate decimal(12,2) DEFAULT NULL,
            unit_price decimal(12,2) NOT NULL,
            amount decimal(12,2) NOT NULL,
            taxable tinyint(1) DEFAULT 1,
            optional tinyint(1) DEFAULT 0,
            sort_order int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY quote_id (quote_id)
        ) $charset;";
        dbDelta($sql);

        // ===== Expenses Table =====
        $sql = "CREATE TABLE " . self::expenses_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            account_id bigint(20) UNSIGNED DEFAULT NULL,
            project_id bigint(20) UNSIGNED DEFAULT NULL,
            vendor varchar(255) DEFAULT NULL,
            category varchar(100) DEFAULT NULL,
            description text DEFAULT NULL,
            amount decimal(12,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            expense_date date NOT NULL,
            receipt_url varchar(500) DEFAULT NULL,
            billable tinyint(1) DEFAULT 0,
            invoiced tinyint(1) DEFAULT 0,
            invoice_id bigint(20) UNSIGNED DEFAULT NULL,
            payment_method varchar(50) DEFAULT NULL,
            reference varchar(100) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY account_id (account_id),
            KEY project_id (project_id),
            KEY category (category),
            KEY expense_date (expense_date),
            KEY billable (billable),
            KEY invoiced (invoiced)
        ) $charset;";
        dbDelta($sql);

        // ===== Recurring Invoices Table =====
        $sql = "CREATE TABLE " . self::recurring_invoices_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            account_id bigint(20) UNSIGNED DEFAULT NULL,
            project_id bigint(20) UNSIGNED NOT NULL,
            template_name varchar(255) NOT NULL,
            contact_id bigint(20) UNSIGNED DEFAULT NULL,
            client_name varchar(255) NOT NULL,
            client_email varchar(255) NOT NULL,
            client_company varchar(255) DEFAULT NULL,
            client_address text DEFAULT NULL,
            frequency varchar(20) DEFAULT 'monthly',
            day_of_week tinyint DEFAULT NULL,
            day_of_month tinyint DEFAULT NULL,
            start_date date NOT NULL,
            end_date date DEFAULT NULL,
            next_invoice_date date NOT NULL,
            last_invoice_date date DEFAULT NULL,
            invoices_generated int DEFAULT 0,
            subtotal decimal(12,2) DEFAULT 0,
            tax_amount decimal(12,2) DEFAULT 0,
            tax_percent decimal(5,2) DEFAULT 0,
            discount_amount decimal(12,2) DEFAULT 0,
            total decimal(12,2) DEFAULT 0,
            currency varchar(3) DEFAULT 'USD',
            due_days int DEFAULT 30,
            status varchar(20) DEFAULT 'active',
            auto_send tinyint(1) DEFAULT 0,
            notes text DEFAULT NULL,
            client_notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY account_id (account_id),
            KEY project_id (project_id),
            KEY contact_id (contact_id),
            KEY next_invoice_date (next_invoice_date),
            KEY status (status)
        ) $charset;";
        dbDelta($sql);

        // ===== Recurring Invoice Items Table =====
        $sql = "CREATE TABLE " . self::recurring_invoice_items_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            recurring_invoice_id bigint(20) UNSIGNED NOT NULL,
            item_type varchar(20) DEFAULT 'service',
            description varchar(500) NOT NULL,
            quantity decimal(10,2) DEFAULT 1,
            hours decimal(10,2) DEFAULT NULL,
            rate decimal(12,2) DEFAULT NULL,
            unit_price decimal(12,2) NOT NULL,
            amount decimal(12,2) NOT NULL,
            taxable tinyint(1) DEFAULT 1,
            sort_order int DEFAULT 0,
            PRIMARY KEY (id),
            KEY recurring_invoice_id (recurring_invoice_id)
        ) $charset;";
        dbDelta($sql);

        // ===== Payments Table =====
        $sql = "CREATE TABLE " . self::payments_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            account_id bigint(20) UNSIGNED DEFAULT NULL,
            invoice_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(12,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            payment_method varchar(30) DEFAULT 'bank_transfer',
            payment_date date NOT NULL,
            reference varchar(255) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY account_id (account_id),
            KEY invoice_id (invoice_id),
            KEY payment_date (payment_date)
        ) $charset;";
        dbDelta($sql);

        // Run migrations
        self::run_migrations();

        update_option('peanut_invoicing_db_version', self::DB_VERSION);
    }

    /**
     * Run migrations for existing data
     */
    private static function run_migrations(): void {
        global $wpdb;

        $current_version = get_option('peanut_invoicing_db_version', '0');

        // Migration to 2.0.0: Add new columns to existing invoices table
        if (version_compare($current_version, '2.0.0', '<')) {
            self::migrate_to_2_0_0();
        }
    }

    /**
     * Migration to 2.0.0
     * Add new columns to existing invoices and invoice_items tables
     */
    private static function migrate_to_2_0_0(): void {
        global $wpdb;

        $invoices_table = self::invoices_table();
        $items_table = self::invoice_items_table();

        // Add new columns to invoices table if they don't exist
        $columns_to_add = [
            'account_id' => "ALTER TABLE $invoices_table ADD COLUMN account_id bigint(20) UNSIGNED DEFAULT NULL AFTER user_id",
            'project_id' => "ALTER TABLE $invoices_table ADD COLUMN project_id bigint(20) UNSIGNED DEFAULT NULL AFTER account_id",
            'discount_type' => "ALTER TABLE $invoices_table ADD COLUMN discount_type varchar(10) DEFAULT 'fixed' AFTER discount_amount",
            'amount_paid' => "ALTER TABLE $invoices_table ADD COLUMN amount_paid decimal(12,2) DEFAULT 0 AFTER total",
            'balance_due' => "ALTER TABLE $invoices_table ADD COLUMN balance_due decimal(12,2) DEFAULT 0 AFTER amount_paid",
            'issue_date' => "ALTER TABLE $invoices_table ADD COLUMN issue_date date DEFAULT NULL AFTER status",
            'payment_terms' => "ALTER TABLE $invoices_table ADD COLUMN payment_terms varchar(100) DEFAULT NULL AFTER paid_at",
            'client_notes' => "ALTER TABLE $invoices_table ADD COLUMN client_notes text DEFAULT NULL AFTER notes",
            'source' => "ALTER TABLE $invoices_table ADD COLUMN source varchar(20) DEFAULT 'manual' AFTER footer",
            'source_id' => "ALTER TABLE $invoices_table ADD COLUMN source_id bigint(20) UNSIGNED DEFAULT NULL AFTER source",
        ];

        foreach ($columns_to_add as $column => $sql) {
            $exists = $wpdb->get_results("SHOW COLUMNS FROM $invoices_table LIKE '$column'");
            if (empty($exists)) {
                $wpdb->query($sql);
            }
        }

        // Add new columns to invoice_items table
        $item_columns = [
            'item_type' => "ALTER TABLE $items_table ADD COLUMN item_type varchar(20) DEFAULT 'service' AFTER invoice_id",
            'hours' => "ALTER TABLE $items_table ADD COLUMN hours decimal(10,2) DEFAULT NULL AFTER quantity",
            'rate' => "ALTER TABLE $items_table ADD COLUMN rate decimal(12,2) DEFAULT NULL AFTER hours",
            'taxable' => "ALTER TABLE $items_table ADD COLUMN taxable tinyint(1) DEFAULT 1 AFTER amount",
        ];

        foreach ($item_columns as $column => $sql) {
            $exists = $wpdb->get_results("SHOW COLUMNS FROM $items_table LIKE '$column'");
            if (empty($exists)) {
                $wpdb->query($sql);
            }
        }

        // Set balance_due = total for existing unpaid invoices
        $wpdb->query("UPDATE $invoices_table SET balance_due = total WHERE status NOT IN ('paid', 'cancelled') AND balance_due = 0");

        // Add indexes if they don't exist
        $wpdb->query("ALTER TABLE $invoices_table ADD KEY account_id (account_id)");
        $wpdb->query("ALTER TABLE $invoices_table ADD KEY project_id (project_id)");
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

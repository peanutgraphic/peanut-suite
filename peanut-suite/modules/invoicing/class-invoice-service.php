<?php
/**
 * Invoice Service
 *
 * Handles invoice business logic, CRUD operations, and calculations.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Invoice_Service {

    /**
     * Get all invoices with filters
     */
    public static function get_all(array $args = []): array {
        global $wpdb;
        $table = Invoicing_Database::invoices_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'project_id' => null,
            'contact_id' => null,
            'status' => '',
            'source' => '',
            'search' => '',
            'date_from' => '',
            'date_to' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);
        $where = ['1=1'];
        $params = [];

        // User filter
        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $params[] = $args['user_id'];
        }

        // Account filter (multi-tenancy)
        if (!empty($args['account_id'])) {
            $where[] = 'account_id = %d';
            $params[] = $args['account_id'];
        }

        // Project filter
        if (!empty($args['project_id'])) {
            $where[] = 'project_id = %d';
            $params[] = $args['project_id'];
        }

        // Contact filter
        if (!empty($args['contact_id'])) {
            $where[] = 'contact_id = %d';
            $params[] = $args['contact_id'];
        }

        // Status filter
        if (!empty($args['status'])) {
            if (is_array($args['status'])) {
                $placeholders = implode(',', array_fill(0, count($args['status']), '%s'));
                $where[] = "status IN ($placeholders)";
                $params = array_merge($params, $args['status']);
            } else {
                $where[] = 'status = %s';
                $params[] = $args['status'];
            }
        }

        // Source filter
        if (!empty($args['source'])) {
            $where[] = 'source = %s';
            $params[] = $args['source'];
        }

        // Date range
        if (!empty($args['date_from'])) {
            $where[] = 'issue_date >= %s';
            $params[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'issue_date <= %s';
            $params[] = $args['date_to'];
        }

        // Search
        if (!empty($args['search'])) {
            $where[] = '(client_name LIKE %s OR client_email LIKE %s OR invoice_number LIKE %s OR client_company LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $where_sql = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']) ?: 'created_at DESC';

        $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Count invoices
     */
    public static function count(array $args = []): int {
        global $wpdb;
        $table = Invoicing_Database::invoices_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'project_id' => null,
            'status' => '',
        ];

        $args = wp_parse_args($args, $defaults);
        $where = ['1=1'];
        $params = [];

        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $params[] = $args['user_id'];
        }

        if (!empty($args['account_id'])) {
            $where[] = 'account_id = %d';
            $params[] = $args['account_id'];
        }

        if (!empty($args['project_id'])) {
            $where[] = 'project_id = %d';
            $params[] = $args['project_id'];
        }

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }

        $where_sql = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM $table WHERE $where_sql";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Get invoice by ID
     */
    public static function get(int $id): ?array {
        global $wpdb;
        $table = Invoicing_Database::invoices_table();

        $invoice = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id),
            ARRAY_A
        );

        if ($invoice) {
            $invoice['items'] = self::get_items($id);
            $invoice['payments'] = Payment_Service::get_by_invoice($id);
        }

        return $invoice;
    }

    /**
     * Create invoice
     */
    public static function create(array $data): ?int {
        global $wpdb;
        $table = Invoicing_Database::invoices_table();

        // Set defaults
        $data['user_id'] = $data['user_id'] ?? get_current_user_id();
        $data['invoice_number'] = $data['invoice_number'] ?? self::generate_number();
        $data['issue_date'] = $data['issue_date'] ?? date('Y-m-d');
        $data['status'] = $data['status'] ?? 'draft';
        $data['source'] = $data['source'] ?? 'manual';
        $data['created_at'] = current_time('mysql');

        // Extract items before insert
        $items = $data['items'] ?? [];
        unset($data['items']);

        // Calculate totals if items provided
        if (!empty($items)) {
            $totals = self::calculate_totals($items, $data['tax_percent'] ?? 0, $data['discount_amount'] ?? 0, $data['discount_type'] ?? 'fixed');
            $data = array_merge($data, $totals);
        }

        // Set balance_due
        $data['balance_due'] = ($data['total'] ?? 0) - ($data['amount_paid'] ?? 0);

        $wpdb->insert($table, $data);
        $invoice_id = $wpdb->insert_id;

        if (!$invoice_id) {
            return null;
        }

        // Add items
        if (!empty($items)) {
            self::set_items($invoice_id, $items);
        }

        return $invoice_id;
    }

    /**
     * Update invoice
     */
    public static function update(int $id, array $data): bool {
        global $wpdb;
        $table = Invoicing_Database::invoices_table();

        $data['updated_at'] = current_time('mysql');

        // Handle items update
        if (isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);
            self::set_items($id, $items);

            // Recalculate totals
            $invoice = self::get($id);
            $totals = self::calculate_totals(
                $items,
                $data['tax_percent'] ?? $invoice['tax_percent'] ?? 0,
                $data['discount_amount'] ?? $invoice['discount_amount'] ?? 0,
                $data['discount_type'] ?? $invoice['discount_type'] ?? 'fixed'
            );
            $data = array_merge($data, $totals);
        }

        // Update balance_due
        if (isset($data['total']) || isset($data['amount_paid'])) {
            $invoice = $invoice ?? self::get($id);
            $total = $data['total'] ?? $invoice['total'];
            $amount_paid = $data['amount_paid'] ?? $invoice['amount_paid'];
            $data['balance_due'] = $total - $amount_paid;
        }

        return $wpdb->update($table, $data, ['id' => $id]) !== false;
    }

    /**
     * Delete invoice
     */
    public static function delete(int $id): bool {
        global $wpdb;

        // Delete items
        $wpdb->delete(Invoicing_Database::invoice_items_table(), ['invoice_id' => $id]);

        // Delete payments
        $wpdb->delete(Invoicing_Database::payments_table(), ['invoice_id' => $id]);

        // Delete invoice
        return $wpdb->delete(Invoicing_Database::invoices_table(), ['id' => $id]) !== false;
    }

    /**
     * Get invoice items
     */
    public static function get_items(int $invoice_id): array {
        global $wpdb;
        $table = Invoicing_Database::invoice_items_table();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE invoice_id = %d ORDER BY sort_order ASC, id ASC",
                $invoice_id
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Set invoice items (replaces all items)
     */
    public static function set_items(int $invoice_id, array $items): void {
        global $wpdb;
        $table = Invoicing_Database::invoice_items_table();

        // Clear existing items
        $wpdb->delete($table, ['invoice_id' => $invoice_id]);

        // Insert new items
        foreach ($items as $index => $item) {
            $item['invoice_id'] = $invoice_id;
            $item['sort_order'] = $item['sort_order'] ?? $index;
            $item['created_at'] = current_time('mysql');

            // Calculate amount if not set
            if (!isset($item['amount'])) {
                if (!empty($item['hours']) && !empty($item['rate'])) {
                    $item['amount'] = $item['hours'] * $item['rate'];
                    $item['unit_price'] = $item['rate'];
                    $item['quantity'] = $item['hours'];
                } else {
                    $item['amount'] = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
                }
            }

            $wpdb->insert($table, $item);
        }
    }

    /**
     * Calculate invoice totals
     */
    public static function calculate_totals(array $items, float $tax_percent = 0, float $discount_amount = 0, string $discount_type = 'fixed'): array {
        $subtotal = 0;
        $taxable_subtotal = 0;

        foreach ($items as $item) {
            $amount = $item['amount'] ?? (($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0));
            $subtotal += $amount;

            if ($item['taxable'] ?? true) {
                $taxable_subtotal += $amount;
            }
        }

        // Apply discount
        $discount = 0;
        if ($discount_amount > 0) {
            if ($discount_type === 'percent') {
                $discount = $subtotal * ($discount_amount / 100);
            } else {
                $discount = $discount_amount;
            }
        }

        $after_discount = $subtotal - $discount;

        // Calculate tax on taxable amount (after proportional discount)
        $discount_ratio = $subtotal > 0 ? ($after_discount / $subtotal) : 1;
        $taxable_after_discount = $taxable_subtotal * $discount_ratio;
        $tax_amount = $taxable_after_discount * ($tax_percent / 100);

        $total = $after_discount + $tax_amount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discount, 2),
            'tax_amount' => round($tax_amount, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Generate next invoice number
     */
    public static function generate_number(string $prefix = 'INV'): string {
        global $wpdb;
        $table = Invoicing_Database::invoices_table();

        $year = date('Y');
        $full_prefix = $prefix . '-' . $year . '-';

        $last_number = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT invoice_number FROM $table WHERE invoice_number LIKE %s ORDER BY id DESC LIMIT 1",
                $full_prefix . '%'
            )
        );

        if ($last_number) {
            $num = (int) substr($last_number, strlen($full_prefix));
            $next = $num + 1;
        } else {
            $next = 1;
        }

        return $full_prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Mark invoice as sent
     */
    public static function mark_sent(int $id): bool {
        return self::update($id, [
            'status' => 'sent',
            'sent_at' => current_time('mysql'),
        ]);
    }

    /**
     * Mark invoice as paid
     */
    public static function mark_paid(int $id): bool {
        $invoice = self::get($id);
        if (!$invoice) {
            return false;
        }

        return self::update($id, [
            'status' => 'paid',
            'paid_at' => current_time('mysql'),
            'amount_paid' => $invoice['total'],
            'balance_due' => 0,
        ]);
    }

    /**
     * Cancel invoice
     */
    public static function cancel(int $id): bool {
        return self::update($id, ['status' => 'cancelled']);
    }

    /**
     * Create invoice from quote
     */
    public static function create_from_quote(int $quote_id): ?int {
        $quote = Quote_Service::get($quote_id);
        if (!$quote) {
            return null;
        }

        // Prepare invoice data from quote
        $invoice_data = [
            'user_id' => $quote['user_id'],
            'account_id' => $quote['account_id'],
            'project_id' => $quote['project_id'],
            'contact_id' => $quote['contact_id'],
            'client_name' => $quote['client_name'],
            'client_email' => $quote['client_email'],
            'client_company' => $quote['client_company'],
            'client_address' => $quote['client_address'],
            'subtotal' => $quote['subtotal'],
            'tax_amount' => $quote['tax_amount'],
            'tax_percent' => $quote['tax_percent'],
            'discount_amount' => $quote['discount_amount'],
            'discount_type' => $quote['discount_type'],
            'total' => $quote['total'],
            'currency' => $quote['currency'],
            'notes' => $quote['notes'],
            'client_notes' => $quote['client_notes'],
            'source' => 'quote',
            'source_id' => $quote_id,
        ];

        // Copy non-optional items
        $items = [];
        foreach ($quote['items'] as $item) {
            if (empty($item['optional'])) {
                unset($item['id'], $item['quote_id'], $item['optional']);
                $items[] = $item;
            }
        }
        $invoice_data['items'] = $items;

        $invoice_id = self::create($invoice_data);

        if ($invoice_id) {
            // Update quote with converted invoice ID
            Quote_Service::update($quote_id, [
                'status' => 'converted',
                'converted_invoice_id' => $invoice_id,
            ]);
        }

        return $invoice_id;
    }

    /**
     * Check if invoice limit reached for tier
     */
    public static function check_limit(int $user_id = 0): array {
        $user_id = $user_id ?: get_current_user_id();

        // Get tier limits
        $license = peanut_get_license();
        $tier = $license['tier'] ?? 'free';
        $limits = [
            'free' => 10,
            'pro' => 100,
            'agency' => PHP_INT_MAX,
        ];
        $limit = $limits[$tier] ?? 10;

        // Count invoices this month
        $month_start = date('Y-m-01');
        $count = self::count([
            'user_id' => $user_id,
            'date_from' => $month_start,
        ]);

        return [
            'limit' => $limit,
            'used' => $count,
            'remaining' => max(0, $limit - $count),
            'can_create' => $count < $limit,
        ];
    }

    /**
     * Get invoice stats
     */
    public static function get_stats(array $args = []): array {
        global $wpdb;
        $table = Invoicing_Database::invoices_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'project_id' => null,
        ];

        $args = wp_parse_args($args, $defaults);
        $where = ['1=1'];
        $params = [];

        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $params[] = $args['user_id'];
        }

        if (!empty($args['account_id'])) {
            $where[] = 'account_id = %d';
            $params[] = $args['account_id'];
        }

        if (!empty($args['project_id'])) {
            $where[] = 'project_id = %d';
            $params[] = $args['project_id'];
        }

        $where_sql = implode(' AND ', $where);

        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'viewed' THEN 1 ELSE 0 END) as viewed,
                    SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
                    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    COALESCE(SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END), 0) as total_paid,
                    COALESCE(SUM(CASE WHEN status IN ('sent', 'viewed', 'partial', 'overdue') THEN balance_due ELSE 0 END), 0) as total_outstanding,
                    COALESCE(SUM(total), 0) as total_invoiced
                FROM $table WHERE $where_sql";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $stats = $wpdb->get_row($sql, ARRAY_A);

        return $stats ?: [
            'total' => 0,
            'draft' => 0,
            'sent' => 0,
            'viewed' => 0,
            'partial' => 0,
            'paid' => 0,
            'overdue' => 0,
            'cancelled' => 0,
            'total_paid' => 0,
            'total_outstanding' => 0,
            'total_invoiced' => 0,
        ];
    }

    /**
     * Update overdue status for past-due invoices
     */
    public static function update_overdue(): int {
        global $wpdb;
        $table = Invoicing_Database::invoices_table();

        return $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table SET status = 'overdue'
                WHERE status IN ('sent', 'viewed', 'partial') AND due_date < %s",
                date('Y-m-d')
            )
        );
    }
}

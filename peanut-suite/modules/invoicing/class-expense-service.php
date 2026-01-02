<?php
/**
 * Expense Service
 *
 * Handles expense tracking and reporting.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Expense_Service {

    /**
     * Default expense categories
     */
    public const CATEGORIES = [
        'software' => 'Software & Tools',
        'hosting' => 'Hosting & Servers',
        'advertising' => 'Advertising',
        'contractors' => 'Contractors',
        'office' => 'Office Supplies',
        'travel' => 'Travel',
        'meals' => 'Meals & Entertainment',
        'equipment' => 'Equipment',
        'professional' => 'Professional Services',
        'insurance' => 'Insurance',
        'utilities' => 'Utilities',
        'other' => 'Other',
    ];

    /**
     * Get all expenses with filters
     */
    public static function get_all(array $args = []): array {
        global $wpdb;
        $table = Invoicing_Database::expenses_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'project_id' => null,
            'category' => '',
            'billable' => null,
            'invoiced' => null,
            'search' => '',
            'date_from' => '',
            'date_to' => '',
            'orderby' => 'expense_date',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
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

        if (!empty($args['category'])) {
            $where[] = 'category = %s';
            $params[] = $args['category'];
        }

        if ($args['billable'] !== null) {
            $where[] = 'billable = %d';
            $params[] = (int) $args['billable'];
        }

        if ($args['invoiced'] !== null) {
            $where[] = 'invoiced = %d';
            $params[] = (int) $args['invoiced'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'expense_date >= %s';
            $params[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'expense_date <= %s';
            $params[] = $args['date_to'];
        }

        if (!empty($args['search'])) {
            $where[] = '(vendor LIKE %s OR description LIKE %s OR reference LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $where_sql = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']) ?: 'expense_date DESC';

        $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Count expenses
     */
    public static function count(array $args = []): int {
        global $wpdb;
        $table = Invoicing_Database::expenses_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'project_id' => null,
            'category' => '',
            'billable' => null,
            'invoiced' => null,
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

        if (!empty($args['category'])) {
            $where[] = 'category = %s';
            $params[] = $args['category'];
        }

        if ($args['billable'] !== null) {
            $where[] = 'billable = %d';
            $params[] = (int) $args['billable'];
        }

        if ($args['invoiced'] !== null) {
            $where[] = 'invoiced = %d';
            $params[] = (int) $args['invoiced'];
        }

        $where_sql = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM $table WHERE $where_sql";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Get expense by ID
     */
    public static function get(int $id): ?array {
        global $wpdb;
        $table = Invoicing_Database::expenses_table();

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id),
            ARRAY_A
        );
    }

    /**
     * Create expense
     */
    public static function create(array $data): ?int {
        global $wpdb;
        $table = Invoicing_Database::expenses_table();

        // Set defaults
        $data['user_id'] = $data['user_id'] ?? get_current_user_id();
        $data['expense_date'] = $data['expense_date'] ?? date('Y-m-d');
        $data['category'] = $data['category'] ?? 'other';
        $data['billable'] = $data['billable'] ?? 0;
        $data['invoiced'] = 0;
        $data['created_at'] = current_time('mysql');

        $wpdb->insert($table, $data);

        return $wpdb->insert_id ?: null;
    }

    /**
     * Update expense
     */
    public static function update(int $id, array $data): bool {
        global $wpdb;
        $table = Invoicing_Database::expenses_table();

        $data['updated_at'] = current_time('mysql');

        return $wpdb->update($table, $data, ['id' => $id]) !== false;
    }

    /**
     * Delete expense
     */
    public static function delete(int $id): bool {
        global $wpdb;
        return $wpdb->delete(Invoicing_Database::expenses_table(), ['id' => $id]) !== false;
    }

    /**
     * Get billable expenses not yet invoiced
     */
    public static function get_billable(array $args = []): array {
        $args['billable'] = 1;
        $args['invoiced'] = 0;
        return self::get_all($args);
    }

    /**
     * Add expenses to invoice as line items
     */
    public static function add_to_invoice(int $invoice_id, array $expense_ids): int {
        global $wpdb;
        $table = Invoicing_Database::expenses_table();
        $items_table = Invoicing_Database::invoice_items_table();

        $added = 0;

        foreach ($expense_ids as $expense_id) {
            $expense = self::get($expense_id);
            if (!$expense || $expense['invoiced']) {
                continue;
            }

            // Create invoice line item
            $item = [
                'invoice_id' => $invoice_id,
                'item_type' => 'expense',
                'description' => $expense['description'] ?: ($expense['vendor'] . ' - ' . $expense['category']),
                'quantity' => 1,
                'unit_price' => $expense['amount'],
                'amount' => $expense['amount'],
                'taxable' => 0, // Expenses typically not taxable when passed through
                'created_at' => current_time('mysql'),
            ];

            $wpdb->insert($items_table, $item);

            if ($wpdb->insert_id) {
                // Mark expense as invoiced
                self::update($expense_id, [
                    'invoiced' => 1,
                    'invoice_id' => $invoice_id,
                ]);
                $added++;
            }
        }

        // Recalculate invoice totals
        if ($added > 0) {
            $invoice = Invoice_Service::get($invoice_id);
            $totals = Invoice_Service::calculate_totals(
                $invoice['items'],
                $invoice['tax_percent'],
                $invoice['discount_amount'],
                $invoice['discount_type']
            );
            Invoice_Service::update($invoice_id, $totals);
        }

        return $added;
    }

    /**
     * Get expense stats
     */
    public static function get_stats(array $args = []): array {
        global $wpdb;
        $table = Invoicing_Database::expenses_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'project_id' => null,
            'date_from' => '',
            'date_to' => '',
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

        if (!empty($args['date_from'])) {
            $where[] = 'expense_date >= %s';
            $params[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'expense_date <= %s';
            $params[] = $args['date_to'];
        }

        $where_sql = implode(' AND ', $where);

        $sql = "SELECT
                    COUNT(*) as total_count,
                    COALESCE(SUM(amount), 0) as total,
                    COALESCE(SUM(CASE WHEN billable = 1 THEN amount ELSE 0 END), 0) as billable_total,
                    COALESCE(SUM(CASE WHEN billable = 1 AND invoiced = 0 THEN amount ELSE 0 END), 0) as uninvoiced_billable,
                    COALESCE(AVG(amount), 0) as avg_amount
                FROM $table WHERE $where_sql";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $stats = $wpdb->get_row($sql, ARRAY_A);

        return [
            'total_count' => (int) ($stats['total_count'] ?? 0),
            'total' => (float) ($stats['total'] ?? 0),
            'billable_total' => (float) ($stats['billable_total'] ?? 0),
            'uninvoiced_billable' => (float) ($stats['uninvoiced_billable'] ?? 0),
            'avg_amount' => (float) ($stats['avg_amount'] ?? 0),
        ];
    }

    /**
     * Get expenses by category
     */
    public static function get_by_category(array $args = []): array {
        global $wpdb;
        $table = Invoicing_Database::expenses_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'project_id' => null,
            'date_from' => '',
            'date_to' => '',
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

        if (!empty($args['date_from'])) {
            $where[] = 'expense_date >= %s';
            $params[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'expense_date <= %s';
            $params[] = $args['date_to'];
        }

        $where_sql = implode(' AND ', $where);

        $sql = "SELECT category, SUM(amount) as total, COUNT(*) as count
                FROM $table WHERE $where_sql
                GROUP BY category
                ORDER BY total DESC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Get expenses by project
     */
    public static function get_by_project(array $args = []): array {
        global $wpdb;
        $table = Invoicing_Database::expenses_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'date_from' => '',
            'date_to' => '',
        ];

        $args = wp_parse_args($args, $defaults);
        $where = ['project_id IS NOT NULL'];
        $params = [];

        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $params[] = $args['user_id'];
        }

        if (!empty($args['account_id'])) {
            $where[] = 'account_id = %d';
            $params[] = $args['account_id'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'expense_date >= %s';
            $params[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'expense_date <= %s';
            $params[] = $args['date_to'];
        }

        $where_sql = implode(' AND ', $where);

        $sql = "SELECT project_id, SUM(amount) as total, COUNT(*) as count
                FROM $table WHERE $where_sql
                GROUP BY project_id
                ORDER BY total DESC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Get category list
     */
    public static function get_categories(): array {
        return self::CATEGORIES;
    }

    /**
     * Get total by project
     */
    public static function get_total_by_project(int $project_id): float {
        global $wpdb;
        $table = Invoicing_Database::expenses_table();

        return (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM $table WHERE project_id = %d",
                $project_id
            )
        );
    }
}

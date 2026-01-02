<?php
/**
 * Finance Service
 *
 * Provides financial reports, stats, and profit/loss calculations.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Finance_Service {

    /**
     * Get dashboard overview stats
     */
    public static function get_dashboard(array $args = []): array {
        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'project_id' => null,
        ];

        $args = wp_parse_args($args, $defaults);

        // Get invoice stats
        $invoice_stats = Invoice_Service::get_stats($args);

        // Get expense stats
        $expense_stats = Expense_Service::get_stats($args);

        // Get recent activity
        $recent_invoices = Invoice_Service::get_all(array_merge($args, [
            'limit' => 5,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ]));

        $recent_payments = Payment_Service::get_all(array_merge($args, [
            'limit' => 5,
            'orderby' => 'payment_date',
            'order' => 'DESC',
        ]));

        // Calculate profit/loss
        $profit = (float) $invoice_stats['total_paid'] - (float) $expense_stats['total'];

        return [
            'invoices' => $invoice_stats,
            'expenses' => $expense_stats,
            'payments' => Payment_Service::get_stats($args),
            'profit' => $profit,
            'recent_invoices' => $recent_invoices,
            'recent_payments' => $recent_payments,
            'limits' => Invoice_Service::check_limit($args['user_id']),
        ];
    }

    /**
     * Get revenue by period
     */
    public static function get_revenue(array $args = []): array {
        global $wpdb;
        $invoices_table = Invoicing_Database::invoices_table();
        $payments_table = Invoicing_Database::payments_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'project_id' => null,
            'period' => 'month', // day, week, month, year
            'date_from' => date('Y-01-01'), // Start of year
            'date_to' => date('Y-12-31'),
        ];

        $args = wp_parse_args($args, $defaults);

        // Determine date grouping
        $date_format = match($args['period']) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m',
        };

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

        $where[] = 'payment_date >= %s';
        $params[] = $args['date_from'];

        $where[] = 'payment_date <= %s';
        $params[] = $args['date_to'];

        $where_sql = implode(' AND ', $where);

        $sql = "SELECT
                    DATE_FORMAT(payment_date, '$date_format') as period,
                    SUM(amount) as revenue,
                    COUNT(*) as payment_count
                FROM $payments_table
                WHERE $where_sql
                GROUP BY DATE_FORMAT(payment_date, '$date_format')
                ORDER BY period ASC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $revenue = $wpdb->get_results($sql, ARRAY_A) ?: [];

        // Get total
        $total = array_sum(array_column($revenue, 'revenue'));

        return [
            'data' => $revenue,
            'total' => $total,
            'period' => $args['period'],
            'date_from' => $args['date_from'],
            'date_to' => $args['date_to'],
        ];
    }

    /**
     * Get profit/loss report
     */
    public static function get_profit_loss(array $args = []): array {
        global $wpdb;
        $payments_table = Invoicing_Database::payments_table();
        $expenses_table = Invoicing_Database::expenses_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'project_id' => null,
            'date_from' => date('Y-01-01'),
            'date_to' => date('Y-12-31'),
        ];

        $args = wp_parse_args($args, $defaults);

        // Build where clause for payments (revenue)
        $payment_where = ['1=1'];
        $payment_params = [];

        if (!empty($args['user_id'])) {
            $payment_where[] = 'user_id = %d';
            $payment_params[] = $args['user_id'];
        }

        if (!empty($args['account_id'])) {
            $payment_where[] = 'account_id = %d';
            $payment_params[] = $args['account_id'];
        }

        $payment_where[] = 'payment_date >= %s';
        $payment_params[] = $args['date_from'];

        $payment_where[] = 'payment_date <= %s';
        $payment_params[] = $args['date_to'];

        $payment_where_sql = implode(' AND ', $payment_where);

        // Get total revenue
        $revenue_sql = "SELECT COALESCE(SUM(amount), 0) FROM $payments_table WHERE $payment_where_sql";
        if (!empty($payment_params)) {
            $revenue_sql = $wpdb->prepare($revenue_sql, $payment_params);
        }
        $revenue = (float) $wpdb->get_var($revenue_sql);

        // Build where clause for expenses
        $expense_where = ['1=1'];
        $expense_params = [];

        if (!empty($args['user_id'])) {
            $expense_where[] = 'user_id = %d';
            $expense_params[] = $args['user_id'];
        }

        if (!empty($args['account_id'])) {
            $expense_where[] = 'account_id = %d';
            $expense_params[] = $args['account_id'];
        }

        if (!empty($args['project_id'])) {
            $expense_where[] = 'project_id = %d';
            $expense_params[] = $args['project_id'];
        }

        $expense_where[] = 'expense_date >= %s';
        $expense_params[] = $args['date_from'];

        $expense_where[] = 'expense_date <= %s';
        $expense_params[] = $args['date_to'];

        $expense_where_sql = implode(' AND ', $expense_where);

        // Get total expenses
        $expenses_sql = "SELECT COALESCE(SUM(amount), 0) FROM $expenses_table WHERE $expense_where_sql";
        if (!empty($expense_params)) {
            $expenses_sql = $wpdb->prepare($expenses_sql, $expense_params);
        }
        $expenses = (float) $wpdb->get_var($expenses_sql);

        // Get expenses by category
        $category_sql = "SELECT category, SUM(amount) as total, COUNT(*) as count
                         FROM $expenses_table
                         WHERE $expense_where_sql
                         GROUP BY category
                         ORDER BY total DESC";
        if (!empty($expense_params)) {
            $category_sql = $wpdb->prepare($category_sql, $expense_params);
        }
        $expenses_by_category = $wpdb->get_results($category_sql, ARRAY_A) ?: [];

        // Calculate profit/loss
        $profit = $revenue - $expenses;
        $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'profit' => $profit,
            'margin' => round($margin, 2),
            'expenses_by_category' => $expenses_by_category,
            'date_from' => $args['date_from'],
            'date_to' => $args['date_to'],
        ];
    }

    /**
     * Get outstanding balance (total unpaid invoices)
     */
    public static function get_outstanding(array $args = []): array {
        global $wpdb;
        $table = Invoicing_Database::invoices_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'project_id' => null,
        ];

        $args = wp_parse_args($args, $defaults);
        $where = ["status IN ('sent', 'viewed', 'partial', 'overdue')"];
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
                    COUNT(*) as invoice_count,
                    COALESCE(SUM(balance_due), 0) as total_outstanding,
                    SUM(CASE WHEN status = 'overdue' THEN balance_due ELSE 0 END) as overdue_amount,
                    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count
                FROM $table WHERE $where_sql";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_row($sql, ARRAY_A) ?: [
            'invoice_count' => 0,
            'total_outstanding' => 0,
            'overdue_amount' => 0,
            'overdue_count' => 0,
        ];
    }

    /**
     * Get revenue by project
     */
    public static function get_revenue_by_project(array $args = []): array {
        global $wpdb;
        $payments_table = Invoicing_Database::payments_table();
        $invoices_table = Invoicing_Database::invoices_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'date_from' => date('Y-01-01'),
            'date_to' => date('Y-12-31'),
            'limit' => 10,
        ];

        $args = wp_parse_args($args, $defaults);
        $where = ['1=1'];
        $params = [];

        if (!empty($args['user_id'])) {
            $where[] = 'p.user_id = %d';
            $params[] = $args['user_id'];
        }

        if (!empty($args['account_id'])) {
            $where[] = 'p.account_id = %d';
            $params[] = $args['account_id'];
        }

        $where[] = 'p.payment_date >= %s';
        $params[] = $args['date_from'];

        $where[] = 'p.payment_date <= %s';
        $params[] = $args['date_to'];

        $where_sql = implode(' AND ', $where);
        $params[] = $args['limit'];

        $sql = "SELECT
                    i.project_id,
                    SUM(p.amount) as revenue,
                    COUNT(DISTINCT i.id) as invoice_count
                FROM $payments_table p
                INNER JOIN $invoices_table i ON p.invoice_id = i.id
                WHERE $where_sql AND i.project_id IS NOT NULL
                GROUP BY i.project_id
                ORDER BY revenue DESC
                LIMIT %d";

        $sql = $wpdb->prepare($sql, $params);

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Get revenue by client
     */
    public static function get_revenue_by_client(array $args = []): array {
        global $wpdb;
        $payments_table = Invoicing_Database::payments_table();
        $invoices_table = Invoicing_Database::invoices_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'date_from' => date('Y-01-01'),
            'date_to' => date('Y-12-31'),
            'limit' => 10,
        ];

        $args = wp_parse_args($args, $defaults);
        $where = ['1=1'];
        $params = [];

        if (!empty($args['user_id'])) {
            $where[] = 'p.user_id = %d';
            $params[] = $args['user_id'];
        }

        if (!empty($args['account_id'])) {
            $where[] = 'p.account_id = %d';
            $params[] = $args['account_id'];
        }

        $where[] = 'p.payment_date >= %s';
        $params[] = $args['date_from'];

        $where[] = 'p.payment_date <= %s';
        $params[] = $args['date_to'];

        $where_sql = implode(' AND ', $where);
        $params[] = $args['limit'];

        $sql = "SELECT
                    i.client_email,
                    i.client_name,
                    i.client_company,
                    SUM(p.amount) as revenue,
                    COUNT(DISTINCT i.id) as invoice_count
                FROM $payments_table p
                INNER JOIN $invoices_table i ON p.invoice_id = i.id
                WHERE $where_sql
                GROUP BY i.client_email, i.client_name, i.client_company
                ORDER BY revenue DESC
                LIMIT %d";

        $sql = $wpdb->prepare($sql, $params);

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Get tier limits info
     */
    public static function get_limits(int $user_id = 0): array {
        return Invoice_Service::check_limit($user_id);
    }
}

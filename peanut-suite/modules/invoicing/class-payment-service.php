<?php
/**
 * Payment Service
 *
 * Handles payment recording and invoice balance updates.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Payment_Service {

    /**
     * Payment methods
     */
    public const METHODS = [
        'bank_transfer' => 'Bank Transfer',
        'cash' => 'Cash',
        'check' => 'Check',
        'card' => 'Credit/Debit Card',
        'paypal' => 'PayPal',
        'stripe' => 'Stripe',
        'other' => 'Other',
    ];

    /**
     * Get all payments with filters
     */
    public static function get_all(array $args = []): array {
        global $wpdb;
        $table = Invoicing_Database::payments_table();
        $invoices_table = Invoicing_Database::invoices_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'invoice_id' => null,
            'payment_method' => '',
            'date_from' => '',
            'date_to' => '',
            'orderby' => 'payment_date',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
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

        if (!empty($args['invoice_id'])) {
            $where[] = 'p.invoice_id = %d';
            $params[] = $args['invoice_id'];
        }

        if (!empty($args['payment_method'])) {
            $where[] = 'p.payment_method = %s';
            $params[] = $args['payment_method'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'p.payment_date >= %s';
            $params[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'p.payment_date <= %s';
            $params[] = $args['date_to'];
        }

        $where_sql = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby('p.' . $args['orderby'] . ' ' . $args['order']) ?: 'p.payment_date DESC';

        $sql = "SELECT p.*, i.invoice_number, i.client_name, i.total as invoice_total
                FROM $table p
                LEFT JOIN $invoices_table i ON p.invoice_id = i.id
                WHERE $where_sql
                ORDER BY $orderby
                LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Get payments by invoice
     */
    public static function get_by_invoice(int $invoice_id): array {
        global $wpdb;
        $table = Invoicing_Database::payments_table();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE invoice_id = %d ORDER BY payment_date DESC",
                $invoice_id
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Get payment by ID
     */
    public static function get(int $id): ?array {
        global $wpdb;
        $table = Invoicing_Database::payments_table();

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id),
            ARRAY_A
        );
    }

    /**
     * Record a payment
     */
    public static function record(array $data): ?int {
        global $wpdb;
        $table = Invoicing_Database::payments_table();

        if (empty($data['invoice_id'])) {
            return null;
        }

        // Get invoice to validate and get account_id
        $invoice = Invoice_Service::get($data['invoice_id']);
        if (!$invoice) {
            return null;
        }

        // Set defaults
        $data['user_id'] = $data['user_id'] ?? get_current_user_id();
        $data['account_id'] = $data['account_id'] ?? $invoice['account_id'];
        $data['payment_date'] = $data['payment_date'] ?? date('Y-m-d');
        $data['payment_method'] = $data['payment_method'] ?? 'bank_transfer';
        $data['currency'] = $data['currency'] ?? $invoice['currency'];
        $data['created_at'] = current_time('mysql');

        $wpdb->insert($table, $data);
        $payment_id = $wpdb->insert_id;

        if (!$payment_id) {
            return null;
        }

        // Update invoice totals
        self::update_invoice_balance($data['invoice_id']);

        return $payment_id;
    }

    /**
     * Update payment
     */
    public static function update(int $id, array $data): bool {
        global $wpdb;
        $table = Invoicing_Database::payments_table();

        $payment = self::get($id);
        if (!$payment) {
            return false;
        }

        $result = $wpdb->update($table, $data, ['id' => $id]);

        if ($result !== false) {
            // Update invoice balance
            self::update_invoice_balance($payment['invoice_id']);
        }

        return $result !== false;
    }

    /**
     * Delete payment
     */
    public static function delete(int $id): bool {
        global $wpdb;
        $table = Invoicing_Database::payments_table();

        $payment = self::get($id);
        if (!$payment) {
            return false;
        }

        $result = $wpdb->delete($table, ['id' => $id]);

        if ($result !== false) {
            // Update invoice balance
            self::update_invoice_balance($payment['invoice_id']);
        }

        return $result !== false;
    }

    /**
     * Update invoice balance after payment changes
     */
    public static function update_invoice_balance(int $invoice_id): bool {
        global $wpdb;
        $payments_table = Invoicing_Database::payments_table();
        $invoices_table = Invoicing_Database::invoices_table();

        // Calculate total payments for invoice
        $total_paid = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM $payments_table WHERE invoice_id = %d",
                $invoice_id
            )
        );

        // Get invoice total
        $invoice = $wpdb->get_row(
            $wpdb->prepare("SELECT total, status FROM $invoices_table WHERE id = %d", $invoice_id),
            ARRAY_A
        );

        if (!$invoice) {
            return false;
        }

        $total = (float) $invoice['total'];
        $balance_due = $total - (float) $total_paid;

        // Determine new status
        $new_status = $invoice['status'];
        if ($balance_due <= 0) {
            $new_status = 'paid';
        } elseif ((float) $total_paid > 0 && $balance_due > 0) {
            $new_status = 'partial';
        } elseif (in_array($invoice['status'], ['paid', 'partial'])) {
            // Revert to sent if payments removed
            $new_status = 'sent';
        }

        $update_data = [
            'amount_paid' => $total_paid,
            'balance_due' => max(0, $balance_due),
            'updated_at' => current_time('mysql'),
        ];

        if ($new_status !== $invoice['status']) {
            $update_data['status'] = $new_status;
            if ($new_status === 'paid') {
                $update_data['paid_at'] = current_time('mysql');
            }
        }

        return $wpdb->update($invoices_table, $update_data, ['id' => $invoice_id]) !== false;
    }

    /**
     * Get payment stats
     */
    public static function get_stats(array $args = []): array {
        global $wpdb;
        $table = Invoicing_Database::payments_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
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

        if (!empty($args['date_from'])) {
            $where[] = 'payment_date >= %s';
            $params[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'payment_date <= %s';
            $params[] = $args['date_to'];
        }

        $where_sql = implode(' AND ', $where);

        $sql = "SELECT
                    COUNT(*) as total_count,
                    COALESCE(SUM(amount), 0) as total_amount,
                    COALESCE(AVG(amount), 0) as avg_amount
                FROM $table WHERE $where_sql";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $stats = $wpdb->get_row($sql, ARRAY_A);

        // Get by payment method
        $method_sql = "SELECT payment_method, COUNT(*) as count, SUM(amount) as total
                       FROM $table WHERE $where_sql GROUP BY payment_method";

        if (!empty($params)) {
            $method_sql = $wpdb->prepare($method_sql, $params);
        }

        $by_method = $wpdb->get_results($method_sql, ARRAY_A) ?: [];

        return [
            'total_count' => (int) ($stats['total_count'] ?? 0),
            'total_amount' => (float) ($stats['total_amount'] ?? 0),
            'avg_amount' => (float) ($stats['avg_amount'] ?? 0),
            'by_method' => $by_method,
        ];
    }

    /**
     * Get payment methods list
     */
    public static function get_methods(): array {
        return self::METHODS;
    }

    /**
     * Count payments
     */
    public static function count(array $args = []): int {
        global $wpdb;
        $table = Invoicing_Database::payments_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'invoice_id' => null,
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

        if (!empty($args['invoice_id'])) {
            $where[] = 'invoice_id = %d';
            $params[] = $args['invoice_id'];
        }

        $where_sql = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM $table WHERE $where_sql";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return (int) $wpdb->get_var($sql);
    }
}

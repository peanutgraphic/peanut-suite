<?php
/**
 * Recurring Invoice Service
 *
 * Handles recurring invoice templates and automatic invoice generation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Recurring_Service {

    /**
     * Frequency options
     */
    public const FREQUENCIES = [
        'weekly' => 'Weekly',
        'biweekly' => 'Bi-weekly',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'annually' => 'Annually',
    ];

    /**
     * Get all recurring invoices with filters
     */
    public static function get_all(array $args = []): array {
        global $wpdb;
        $table = Invoicing_Database::recurring_invoices_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'project_id' => null,
            'status' => '',
            'search' => '',
            'orderby' => 'next_invoice_date',
            'order' => 'ASC',
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

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }

        if (!empty($args['search'])) {
            $where[] = '(template_name LIKE %s OR client_name LIKE %s OR client_email LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $where_sql = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']) ?: 'next_invoice_date ASC';

        $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Get recurring invoice by ID
     */
    public static function get(int $id): ?array {
        global $wpdb;
        $table = Invoicing_Database::recurring_invoices_table();

        $recurring = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id),
            ARRAY_A
        );

        if ($recurring) {
            $recurring['items'] = self::get_items($id);
        }

        return $recurring;
    }

    /**
     * Create recurring invoice
     */
    public static function create(array $data): ?int {
        global $wpdb;
        $table = Invoicing_Database::recurring_invoices_table();

        // Set defaults
        $data['user_id'] = $data['user_id'] ?? get_current_user_id();
        $data['frequency'] = $data['frequency'] ?? 'monthly';
        $data['status'] = $data['status'] ?? 'active';
        $data['start_date'] = $data['start_date'] ?? date('Y-m-d');
        $data['next_invoice_date'] = $data['next_invoice_date'] ?? $data['start_date'];
        $data['due_days'] = $data['due_days'] ?? 30;
        $data['auto_send'] = $data['auto_send'] ?? 0;
        $data['invoices_generated'] = 0;
        $data['created_at'] = current_time('mysql');

        // Extract items
        $items = $data['items'] ?? [];
        unset($data['items']);

        // Calculate totals
        if (!empty($items)) {
            $totals = self::calculate_totals($items, $data['tax_percent'] ?? 0, $data['discount_amount'] ?? 0);
            $data = array_merge($data, $totals);
        }

        $wpdb->insert($table, $data);
        $recurring_id = $wpdb->insert_id;

        if (!$recurring_id) {
            return null;
        }

        // Add items
        if (!empty($items)) {
            self::set_items($recurring_id, $items);
        }

        return $recurring_id;
    }

    /**
     * Update recurring invoice
     */
    public static function update(int $id, array $data): bool {
        global $wpdb;
        $table = Invoicing_Database::recurring_invoices_table();

        $data['updated_at'] = current_time('mysql');

        // Handle items update
        if (isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);
            self::set_items($id, $items);

            // Recalculate totals
            $recurring = self::get($id);
            $totals = self::calculate_totals(
                $items,
                $data['tax_percent'] ?? $recurring['tax_percent'] ?? 0,
                $data['discount_amount'] ?? $recurring['discount_amount'] ?? 0
            );
            $data = array_merge($data, $totals);
        }

        return $wpdb->update($table, $data, ['id' => $id]) !== false;
    }

    /**
     * Delete recurring invoice
     */
    public static function delete(int $id): bool {
        global $wpdb;

        // Delete items
        $wpdb->delete(Invoicing_Database::recurring_invoice_items_table(), ['recurring_invoice_id' => $id]);

        // Delete recurring invoice
        return $wpdb->delete(Invoicing_Database::recurring_invoices_table(), ['id' => $id]) !== false;
    }

    /**
     * Get recurring invoice items
     */
    public static function get_items(int $recurring_id): array {
        global $wpdb;
        $table = Invoicing_Database::recurring_invoice_items_table();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE recurring_invoice_id = %d ORDER BY sort_order ASC",
                $recurring_id
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Set recurring invoice items
     */
    public static function set_items(int $recurring_id, array $items): void {
        global $wpdb;
        $table = Invoicing_Database::recurring_invoice_items_table();

        // Clear existing
        $wpdb->delete($table, ['recurring_invoice_id' => $recurring_id]);

        // Insert new
        foreach ($items as $index => $item) {
            $item['recurring_invoice_id'] = $recurring_id;
            $item['sort_order'] = $item['sort_order'] ?? $index;

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
     * Calculate totals
     */
    public static function calculate_totals(array $items, float $tax_percent = 0, float $discount_amount = 0): array {
        $subtotal = 0;
        $taxable_subtotal = 0;

        foreach ($items as $item) {
            $amount = $item['amount'] ?? (($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0));
            $subtotal += $amount;

            if ($item['taxable'] ?? true) {
                $taxable_subtotal += $amount;
            }
        }

        $after_discount = $subtotal - $discount_amount;
        $discount_ratio = $subtotal > 0 ? ($after_discount / $subtotal) : 1;
        $taxable_after = $taxable_subtotal * $discount_ratio;
        $tax_amount = $taxable_after * ($tax_percent / 100);
        $total = $after_discount + $tax_amount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discount_amount, 2),
            'tax_amount' => round($tax_amount, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Pause recurring invoice
     */
    public static function pause(int $id): bool {
        return self::update($id, ['status' => 'paused']);
    }

    /**
     * Resume recurring invoice
     */
    public static function resume(int $id): bool {
        $recurring = self::get($id);
        if (!$recurring) {
            return false;
        }

        // Recalculate next invoice date if in the past
        $next_date = $recurring['next_invoice_date'];
        while (strtotime($next_date) < strtotime('today')) {
            $next_date = self::calculate_next_date($next_date, $recurring['frequency'], $recurring);
        }

        return self::update($id, [
            'status' => 'active',
            'next_invoice_date' => $next_date,
        ]);
    }

    /**
     * Mark as complete
     */
    public static function complete(int $id): bool {
        return self::update($id, ['status' => 'completed']);
    }

    /**
     * Generate invoice from recurring template
     */
    public static function generate_invoice(int $id): ?int {
        $recurring = self::get($id);
        if (!$recurring || $recurring['status'] !== 'active') {
            return null;
        }

        // Check tier limits
        $limits = Invoice_Service::check_limit($recurring['user_id']);
        if (!$limits['can_create']) {
            return null;
        }

        // Create invoice data
        $invoice_data = [
            'user_id' => $recurring['user_id'],
            'account_id' => $recurring['account_id'],
            'project_id' => $recurring['project_id'],
            'contact_id' => $recurring['contact_id'],
            'client_name' => $recurring['client_name'],
            'client_email' => $recurring['client_email'],
            'client_company' => $recurring['client_company'],
            'client_address' => $recurring['client_address'],
            'subtotal' => $recurring['subtotal'],
            'tax_amount' => $recurring['tax_amount'],
            'tax_percent' => $recurring['tax_percent'],
            'discount_amount' => $recurring['discount_amount'],
            'total' => $recurring['total'],
            'currency' => $recurring['currency'],
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+' . $recurring['due_days'] . ' days')),
            'notes' => $recurring['notes'],
            'client_notes' => $recurring['client_notes'],
            'source' => 'recurring',
            'source_id' => $id,
            'status' => $recurring['auto_send'] ? 'sent' : 'draft',
        ];

        // Copy items
        $items = [];
        foreach ($recurring['items'] as $item) {
            unset($item['id'], $item['recurring_invoice_id']);
            $items[] = $item;
        }
        $invoice_data['items'] = $items;

        $invoice_id = Invoice_Service::create($invoice_data);

        if ($invoice_id) {
            // Update recurring invoice
            $next_date = self::calculate_next_date(
                $recurring['next_invoice_date'],
                $recurring['frequency'],
                $recurring
            );

            $update_data = [
                'last_invoice_date' => date('Y-m-d'),
                'next_invoice_date' => $next_date,
                'invoices_generated' => $recurring['invoices_generated'] + 1,
            ];

            // Check if end date reached
            if (!empty($recurring['end_date']) && strtotime($next_date) > strtotime($recurring['end_date'])) {
                $update_data['status'] = 'completed';
            }

            self::update($id, $update_data);

            // Mark as sent if auto_send
            if ($recurring['auto_send']) {
                Invoice_Service::update($invoice_id, ['sent_at' => current_time('mysql')]);
            }
        }

        return $invoice_id;
    }

    /**
     * Calculate next invoice date
     */
    public static function calculate_next_date(string $current_date, string $frequency, array $recurring = []): string {
        $date = strtotime($current_date);

        switch ($frequency) {
            case 'weekly':
                return date('Y-m-d', strtotime('+1 week', $date));

            case 'biweekly':
                return date('Y-m-d', strtotime('+2 weeks', $date));

            case 'monthly':
                $day = !empty($recurring['day_of_month']) ? $recurring['day_of_month'] : (int) date('d', $date);
                $next_month = strtotime('+1 month', $date);
                $year = date('Y', $next_month);
                $month = date('m', $next_month);
                $max_day = date('t', strtotime("$year-$month-01"));
                $day = min($day, $max_day);
                return sprintf('%s-%s-%02d', $year, $month, $day);

            case 'quarterly':
                return date('Y-m-d', strtotime('+3 months', $date));

            case 'annually':
                return date('Y-m-d', strtotime('+1 year', $date));

            default:
                return date('Y-m-d', strtotime('+1 month', $date));
        }
    }

    /**
     * Get due recurring invoices
     */
    public static function get_due(): array {
        global $wpdb;
        $table = Invoicing_Database::recurring_invoices_table();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table
                WHERE status = 'active'
                AND next_invoice_date <= %s
                AND (end_date IS NULL OR end_date >= %s)",
                date('Y-m-d'),
                date('Y-m-d')
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Generate all due invoices (called by cron)
     */
    public static function generate_due_invoices(): array {
        $due = self::get_due();
        $generated = [];

        foreach ($due as $recurring) {
            $invoice_id = self::generate_invoice($recurring['id']);
            if ($invoice_id) {
                $generated[] = [
                    'recurring_id' => $recurring['id'],
                    'invoice_id' => $invoice_id,
                    'template_name' => $recurring['template_name'],
                ];
            }
        }

        return $generated;
    }

    /**
     * Preview next invoice
     */
    public static function preview(int $id): ?array {
        $recurring = self::get($id);
        if (!$recurring) {
            return null;
        }

        return [
            'client_name' => $recurring['client_name'],
            'client_email' => $recurring['client_email'],
            'client_company' => $recurring['client_company'],
            'issue_date' => $recurring['next_invoice_date'],
            'due_date' => date('Y-m-d', strtotime($recurring['next_invoice_date'] . ' +' . $recurring['due_days'] . ' days')),
            'items' => $recurring['items'],
            'subtotal' => $recurring['subtotal'],
            'tax_amount' => $recurring['tax_amount'],
            'discount_amount' => $recurring['discount_amount'],
            'total' => $recurring['total'],
            'currency' => $recurring['currency'],
        ];
    }

    /**
     * Count recurring invoices
     */
    public static function count(array $args = []): int {
        global $wpdb;
        $table = Invoicing_Database::recurring_invoices_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
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
     * Get frequency list
     */
    public static function get_frequencies(): array {
        return self::FREQUENCIES;
    }
}

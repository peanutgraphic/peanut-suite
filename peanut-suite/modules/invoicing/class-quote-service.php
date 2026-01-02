<?php
/**
 * Quote Service
 *
 * Handles quote/estimate business logic, CRUD, and conversion to invoices.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Quote_Service {

    /**
     * Quote statuses
     */
    public const STATUSES = [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'viewed' => 'Viewed',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
        'expired' => 'Expired',
        'converted' => 'Converted to Invoice',
    ];

    /**
     * Get all quotes with filters
     */
    public static function get_all(array $args = []): array {
        global $wpdb;
        $table = Invoicing_Database::quotes_table();

        $defaults = [
            'user_id' => get_current_user_id(),
            'account_id' => null,
            'project_id' => null,
            'contact_id' => null,
            'status' => '',
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
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

        if (!empty($args['contact_id'])) {
            $where[] = 'contact_id = %d';
            $params[] = $args['contact_id'];
        }

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

        if (!empty($args['search'])) {
            $where[] = '(client_name LIKE %s OR client_email LIKE %s OR quote_number LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
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
     * Count quotes
     */
    public static function count(array $args = []): int {
        global $wpdb;
        $table = Invoicing_Database::quotes_table();

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
     * Get quote by ID
     */
    public static function get(int $id): ?array {
        global $wpdb;
        $table = Invoicing_Database::quotes_table();

        $quote = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id),
            ARRAY_A
        );

        if ($quote) {
            $quote['items'] = self::get_items($id);
        }

        return $quote;
    }

    /**
     * Create quote
     */
    public static function create(array $data): ?int {
        global $wpdb;
        $table = Invoicing_Database::quotes_table();

        // Set defaults
        $data['user_id'] = $data['user_id'] ?? get_current_user_id();
        $data['quote_number'] = $data['quote_number'] ?? self::generate_number();
        $data['status'] = $data['status'] ?? 'draft';
        $data['valid_until'] = $data['valid_until'] ?? date('Y-m-d', strtotime('+30 days'));
        $data['created_at'] = current_time('mysql');

        // Extract items
        $items = $data['items'] ?? [];
        unset($data['items']);

        // Calculate totals
        if (!empty($items)) {
            $totals = self::calculate_totals($items, $data['tax_percent'] ?? 0, $data['discount_amount'] ?? 0, $data['discount_type'] ?? 'fixed');
            $data = array_merge($data, $totals);
        }

        $wpdb->insert($table, $data);
        $quote_id = $wpdb->insert_id;

        if (!$quote_id) {
            return null;
        }

        // Add items
        if (!empty($items)) {
            self::set_items($quote_id, $items);
        }

        return $quote_id;
    }

    /**
     * Update quote
     */
    public static function update(int $id, array $data): bool {
        global $wpdb;
        $table = Invoicing_Database::quotes_table();

        $data['updated_at'] = current_time('mysql');

        // Handle items update
        if (isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);
            self::set_items($id, $items);

            // Recalculate totals
            $quote = self::get($id);
            $totals = self::calculate_totals(
                $items,
                $data['tax_percent'] ?? $quote['tax_percent'] ?? 0,
                $data['discount_amount'] ?? $quote['discount_amount'] ?? 0,
                $data['discount_type'] ?? $quote['discount_type'] ?? 'fixed'
            );
            $data = array_merge($data, $totals);
        }

        return $wpdb->update($table, $data, ['id' => $id]) !== false;
    }

    /**
     * Delete quote
     */
    public static function delete(int $id): bool {
        global $wpdb;

        // Delete items
        $wpdb->delete(Invoicing_Database::quote_items_table(), ['quote_id' => $id]);

        // Delete quote
        return $wpdb->delete(Invoicing_Database::quotes_table(), ['id' => $id]) !== false;
    }

    /**
     * Get quote items
     */
    public static function get_items(int $quote_id): array {
        global $wpdb;
        $table = Invoicing_Database::quote_items_table();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE quote_id = %d ORDER BY sort_order ASC, id ASC",
                $quote_id
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Set quote items
     */
    public static function set_items(int $quote_id, array $items): void {
        global $wpdb;
        $table = Invoicing_Database::quote_items_table();

        // Clear existing items
        $wpdb->delete($table, ['quote_id' => $quote_id]);

        // Insert new items
        foreach ($items as $index => $item) {
            $item['quote_id'] = $quote_id;
            $item['sort_order'] = $item['sort_order'] ?? $index;
            $item['created_at'] = current_time('mysql');

            // Calculate amount
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
     * Calculate quote totals
     */
    public static function calculate_totals(array $items, float $tax_percent = 0, float $discount_amount = 0, string $discount_type = 'fixed'): array {
        $subtotal = 0;
        $taxable_subtotal = 0;

        foreach ($items as $item) {
            // Skip optional items unless they're included
            if (!empty($item['optional'])) {
                continue;
            }

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

        // Calculate tax
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
     * Generate quote number
     */
    public static function generate_number(string $prefix = 'QT'): string {
        global $wpdb;
        $table = Invoicing_Database::quotes_table();

        $year = date('Y');
        $full_prefix = $prefix . '-' . $year . '-';

        $last_number = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT quote_number FROM $table WHERE quote_number LIKE %s ORDER BY id DESC LIMIT 1",
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
     * Mark quote as sent
     */
    public static function mark_sent(int $id): bool {
        return self::update($id, [
            'status' => 'sent',
            'sent_at' => current_time('mysql'),
        ]);
    }

    /**
     * Mark quote as accepted
     */
    public static function mark_accepted(int $id): bool {
        return self::update($id, [
            'status' => 'accepted',
            'accepted_at' => current_time('mysql'),
        ]);
    }

    /**
     * Mark quote as declined
     */
    public static function mark_declined(int $id): bool {
        return self::update($id, [
            'status' => 'declined',
            'declined_at' => current_time('mysql'),
        ]);
    }

    /**
     * Convert quote to invoice
     */
    public static function convert_to_invoice(int $id): ?int {
        return Invoice_Service::create_from_quote($id);
    }

    /**
     * Check and update expired quotes
     */
    public static function update_expired(): int {
        global $wpdb;
        $table = Invoicing_Database::quotes_table();

        return $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table SET status = 'expired'
                WHERE status IN ('draft', 'sent', 'viewed') AND valid_until < %s",
                date('Y-m-d')
            )
        );
    }

    /**
     * Get quote stats
     */
    public static function get_stats(array $args = []): array {
        global $wpdb;
        $table = Invoicing_Database::quotes_table();

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
                    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                    SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted,
                    COALESCE(SUM(CASE WHEN status IN ('accepted', 'converted') THEN total ELSE 0 END), 0) as total_accepted,
                    COALESCE(SUM(total), 0) as total_quoted
                FROM $table WHERE $where_sql";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $stats = $wpdb->get_row($sql, ARRAY_A);

        // Calculate conversion rate
        $sent_total = (int) ($stats['sent'] ?? 0) + (int) ($stats['viewed'] ?? 0) +
                      (int) ($stats['accepted'] ?? 0) + (int) ($stats['declined'] ?? 0) +
                      (int) ($stats['expired'] ?? 0) + (int) ($stats['converted'] ?? 0);

        $converted = (int) ($stats['accepted'] ?? 0) + (int) ($stats['converted'] ?? 0);
        $conversion_rate = $sent_total > 0 ? round(($converted / $sent_total) * 100, 1) : 0;

        $stats['conversion_rate'] = $conversion_rate;

        return $stats ?: [
            'total' => 0,
            'draft' => 0,
            'sent' => 0,
            'viewed' => 0,
            'accepted' => 0,
            'declined' => 0,
            'expired' => 0,
            'converted' => 0,
            'total_accepted' => 0,
            'total_quoted' => 0,
            'conversion_rate' => 0,
        ];
    }

    /**
     * Get status list
     */
    public static function get_statuses(): array {
        return self::STATUSES;
    }
}

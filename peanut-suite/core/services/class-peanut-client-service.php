<?php
/**
 * Client Service
 *
 * Handles client management for the hierarchical Client → Project structure.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Client_Service {

    /**
     * Client statuses
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * Contact roles within a client
     */
    public const ROLE_PRIMARY = 'primary';
    public const ROLE_BILLING = 'billing';
    public const ROLE_TECHNICAL = 'technical';
    public const ROLE_PROJECT_MANAGER = 'project_manager';
    public const ROLE_OTHER = 'other';

    /**
     * Client size options
     */
    public const SIZE_SOLO = 'solo';
    public const SIZE_SMALL = 'small';
    public const SIZE_MEDIUM = 'medium';
    public const SIZE_LARGE = 'large';
    public const SIZE_ENTERPRISE = 'enterprise';

    /**
     * Max clients per tier
     */
    private const TIER_CLIENT_LIMITS = [
        'free' => 3,
        'pro' => 50,
        'agency' => -1, // Unlimited
    ];

    /**
     * Get client limit for a tier
     */
    public static function get_client_limit(string $tier): int {
        return self::TIER_CLIENT_LIMITS[$tier] ?? 3;
    }

    /**
     * Check if account can create more clients
     */
    public static function can_create_client(int $account_id): bool {
        $account = Peanut_Account_Service::get_by_id($account_id);
        if (!$account) {
            return false;
        }

        $limit = self::get_client_limit($account['tier'] ?? 'free');
        if ($limit === -1) {
            return true; // Unlimited
        }

        $current_count = self::get_client_count($account_id);
        return $current_count < $limit;
    }

    /**
     * Get client count for an account
     */
    public static function get_client_count(int $account_id): int {
        global $wpdb;
        $table = Peanut_Database::clients_table();

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE account_id = %d AND status != %s",
            $account_id,
            self::STATUS_ARCHIVED
        ));
    }

    /**
     * Get client limits info for an account
     */
    public static function get_limits(int $account_id): array {
        $account = Peanut_Account_Service::get_by_id($account_id);
        $tier = $account['tier'] ?? 'free';
        $limit = self::get_client_limit($tier);
        $current = self::get_client_count($account_id);

        return [
            'current' => $current,
            'max' => $limit,
            'unlimited' => $limit === -1,
            'tier' => $tier,
            'can_create' => $limit === -1 || $current < $limit,
        ];
    }

    /**
     * Get all clients for an account
     */
    public static function get_clients_for_account(int $account_id, array $filters = []): array {
        global $wpdb;
        $table = Peanut_Database::clients_table();

        $where = ['account_id = %d'];
        $params = [$account_id];

        // Status filter
        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = $filters['status'];
        } else {
            // Default: exclude archived
            $where[] = 'status != %s';
            $params[] = self::STATUS_ARCHIVED;
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where[] = '(name LIKE %s OR legal_name LIKE %s OR billing_email LIKE %s)';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $where_clause = implode(' AND ', $where);
        $order_by = $filters['order_by'] ?? 'name';
        $order = strtoupper($filters['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        // Validate order_by column
        $valid_columns = ['name', 'created_at', 'updated_at', 'status'];
        if (!in_array($order_by, $valid_columns, true)) {
            $order_by = 'name';
        }

        $sql = "SELECT * FROM $table WHERE $where_clause ORDER BY $order_by $order";

        // Pagination
        if (isset($filters['per_page'])) {
            $per_page = (int) $filters['per_page'];
            $page = max(1, (int) ($filters['page'] ?? 1));
            $offset = ($page - 1) * $per_page;
            $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);
        }

        return $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A) ?: [];
    }

    /**
     * Get client by ID
     */
    public static function get_by_id(int $client_id): ?array {
        global $wpdb;
        $table = Peanut_Database::clients_table();

        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $client_id
        ), ARRAY_A);

        if ($client) {
            // Parse JSON fields
            $client['custom_fields'] = json_decode($client['custom_fields'] ?? '{}', true);
            $client['settings'] = json_decode($client['settings'] ?? '{}', true);
        }

        return $client ?: null;
    }

    /**
     * Get client by slug within an account
     */
    public static function get_by_slug(int $account_id, string $slug): ?array {
        global $wpdb;
        $table = Peanut_Database::clients_table();

        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE account_id = %d AND slug = %s",
            $account_id,
            $slug
        ), ARRAY_A);

        if ($client) {
            $client['custom_fields'] = json_decode($client['custom_fields'] ?? '{}', true);
            $client['settings'] = json_decode($client['settings'] ?? '{}', true);
        }

        return $client ?: null;
    }

    /**
     * Create a new client
     */
    public static function create(int $account_id, array $data, int $created_by): ?int {
        global $wpdb;
        $table = Peanut_Database::clients_table();

        // Check if can create
        if (!self::can_create_client($account_id)) {
            return null;
        }

        // Generate slug if not provided
        $slug = $data['slug'] ?? sanitize_title($data['name']);
        $slug = self::ensure_unique_slug($account_id, $slug);

        $insert_data = [
            'account_id' => $account_id,
            'name' => sanitize_text_field($data['name']),
            'slug' => $slug,
            'legal_name' => isset($data['legal_name']) ? sanitize_text_field($data['legal_name']) : null,
            'website' => isset($data['website']) ? esc_url_raw($data['website']) : null,
            'industry' => isset($data['industry']) ? sanitize_text_field($data['industry']) : null,
            'size' => isset($data['size']) ? sanitize_text_field($data['size']) : null,
            'billing_email' => isset($data['billing_email']) ? sanitize_email($data['billing_email']) : null,
            'billing_address' => isset($data['billing_address']) ? sanitize_textarea_field($data['billing_address']) : null,
            'billing_city' => isset($data['billing_city']) ? sanitize_text_field($data['billing_city']) : null,
            'billing_state' => isset($data['billing_state']) ? sanitize_text_field($data['billing_state']) : null,
            'billing_postal' => isset($data['billing_postal']) ? sanitize_text_field($data['billing_postal']) : null,
            'billing_country' => isset($data['billing_country']) ? sanitize_text_field($data['billing_country']) : null,
            'tax_id' => isset($data['tax_id']) ? sanitize_text_field($data['tax_id']) : null,
            'currency' => isset($data['currency']) ? sanitize_text_field($data['currency']) : 'USD',
            'payment_terms' => isset($data['payment_terms']) ? (int) $data['payment_terms'] : 30,
            'status' => self::STATUS_ACTIVE,
            'acquisition_source' => isset($data['acquisition_source']) ? sanitize_text_field($data['acquisition_source']) : null,
            'acquired_at' => isset($data['acquired_at']) ? sanitize_text_field($data['acquired_at']) : null,
            'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : null,
            'custom_fields' => isset($data['custom_fields']) ? wp_json_encode($data['custom_fields']) : null,
            'settings' => isset($data['settings']) ? wp_json_encode($data['settings']) : null,
            'created_by' => $created_by,
        ];

        $result = $wpdb->insert($table, $insert_data);

        if (!$result) {
            return null;
        }

        $client_id = (int) $wpdb->insert_id;

        // Log the action
        if (class_exists('Peanut_Audit_Log_Service')) {
            Peanut_Audit_Log_Service::log(
                $account_id,
                'create',
                'client',
                $client_id,
                ['name' => $data['name']]
            );
        }

        return $client_id;
    }

    /**
     * Ensure slug is unique within account
     */
    private static function ensure_unique_slug(int $account_id, string $slug, ?int $exclude_id = null): string {
        global $wpdb;
        $table = Peanut_Database::clients_table();
        $original_slug = $slug;
        $counter = 1;

        while (true) {
            $query = $wpdb->prepare(
                "SELECT id FROM $table WHERE account_id = %d AND slug = %s",
                $account_id,
                $slug
            );

            if ($exclude_id) {
                $query .= $wpdb->prepare(" AND id != %d", $exclude_id);
            }

            $exists = $wpdb->get_var($query);

            if (!$exists) {
                break;
            }

            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Update a client
     */
    public static function update(int $client_id, array $data): bool {
        global $wpdb;
        $table = Peanut_Database::clients_table();

        $client = self::get_by_id($client_id);
        if (!$client) {
            return false;
        }

        $update_data = [];

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }

        if (isset($data['slug'])) {
            $update_data['slug'] = self::ensure_unique_slug(
                $client['account_id'],
                sanitize_title($data['slug']),
                $client_id
            );
        }

        if (isset($data['legal_name'])) {
            $update_data['legal_name'] = sanitize_text_field($data['legal_name']);
        }

        if (isset($data['website'])) {
            $update_data['website'] = esc_url_raw($data['website']);
        }

        if (isset($data['industry'])) {
            $update_data['industry'] = sanitize_text_field($data['industry']);
        }

        if (isset($data['size'])) {
            $update_data['size'] = sanitize_text_field($data['size']);
        }

        if (isset($data['billing_email'])) {
            $update_data['billing_email'] = sanitize_email($data['billing_email']);
        }

        if (isset($data['billing_address'])) {
            $update_data['billing_address'] = sanitize_textarea_field($data['billing_address']);
        }

        if (isset($data['billing_city'])) {
            $update_data['billing_city'] = sanitize_text_field($data['billing_city']);
        }

        if (isset($data['billing_state'])) {
            $update_data['billing_state'] = sanitize_text_field($data['billing_state']);
        }

        if (isset($data['billing_postal'])) {
            $update_data['billing_postal'] = sanitize_text_field($data['billing_postal']);
        }

        if (isset($data['billing_country'])) {
            $update_data['billing_country'] = sanitize_text_field($data['billing_country']);
        }

        if (isset($data['tax_id'])) {
            $update_data['tax_id'] = sanitize_text_field($data['tax_id']);
        }

        if (isset($data['currency'])) {
            $update_data['currency'] = sanitize_text_field($data['currency']);
        }

        if (isset($data['payment_terms'])) {
            $update_data['payment_terms'] = (int) $data['payment_terms'];
        }

        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }

        if (isset($data['acquisition_source'])) {
            $update_data['acquisition_source'] = sanitize_text_field($data['acquisition_source']);
        }

        if (isset($data['acquired_at'])) {
            $update_data['acquired_at'] = sanitize_text_field($data['acquired_at']);
        }

        if (isset($data['notes'])) {
            $update_data['notes'] = sanitize_textarea_field($data['notes']);
        }

        if (isset($data['custom_fields'])) {
            $update_data['custom_fields'] = wp_json_encode($data['custom_fields']);
        }

        if (isset($data['settings'])) {
            $update_data['settings'] = wp_json_encode($data['settings']);
        }

        if (empty($update_data)) {
            return true; // Nothing to update
        }

        $result = $wpdb->update($table, $update_data, ['id' => $client_id]);

        if ($result !== false) {
            // Log the action
            if (class_exists('Peanut_Audit_Log_Service')) {
                Peanut_Audit_Log_Service::log(
                    $client['account_id'],
                    'update',
                    'client',
                    $client_id,
                    ['updated_fields' => array_keys($update_data)]
                );
            }
        }

        return $result !== false;
    }

    /**
     * Delete a client
     */
    public static function delete(int $client_id): bool {
        global $wpdb;

        $client = self::get_by_id($client_id);
        if (!$client) {
            return false;
        }

        // Check if this is the default client
        $settings = $client['settings'] ?? [];
        if (!empty($settings['is_default'])) {
            return false; // Cannot delete default client
        }

        // Check if client has projects
        $project_count = self::get_project_count($client_id);
        if ($project_count > 0) {
            return false; // Cannot delete client with projects
        }

        // Delete client contacts
        $client_contacts_table = Peanut_Database::client_contacts_table();
        $wpdb->delete($client_contacts_table, ['client_id' => $client_id]);

        // Delete the client
        $table = Peanut_Database::clients_table();
        $result = $wpdb->delete($table, ['id' => $client_id]);

        if ($result) {
            // Log the action
            if (class_exists('Peanut_Audit_Log_Service')) {
                Peanut_Audit_Log_Service::log(
                    $client['account_id'],
                    'delete',
                    'client',
                    $client_id,
                    ['name' => $client['name']]
                );
            }
        }

        return (bool) $result;
    }

    /**
     * Archive a client
     */
    public static function archive(int $client_id): bool {
        return self::update($client_id, ['status' => self::STATUS_ARCHIVED]);
    }

    /**
     * Get project count for a client
     */
    public static function get_project_count(int $client_id): int {
        global $wpdb;
        $table = Peanut_Database::projects_table();

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE client_id = %d AND status = %s",
            $client_id,
            'active'
        ));
    }

    /**
     * Get projects for a client
     */
    public static function get_projects(int $client_id): array {
        global $wpdb;
        $table = Peanut_Database::projects_table();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE client_id = %d ORDER BY name ASC",
            $client_id
        ), ARRAY_A) ?: [];
    }

    /**
     * Get billing info for a client
     */
    public static function get_billing_info(int $client_id): ?array {
        $client = self::get_by_id($client_id);
        if (!$client) {
            return null;
        }

        return [
            'client_id' => $client['id'],
            'client_name' => $client['name'],
            'client_email' => $client['billing_email'],
            'client_company' => $client['legal_name'] ?? $client['name'],
            'client_address' => self::format_address($client),
            'tax_id' => $client['tax_id'],
            'currency' => $client['currency'],
            'payment_terms' => $client['payment_terms'],
        ];
    }

    /**
     * Format billing address
     */
    private static function format_address(array $client): string {
        $parts = array_filter([
            $client['billing_address'] ?? '',
            $client['billing_city'] ?? '',
            $client['billing_state'] ?? '',
            $client['billing_postal'] ?? '',
            $client['billing_country'] ?? '',
        ]);

        return implode(', ', $parts);
    }

    // ========== Contact Management ==========

    /**
     * Get contacts for a client
     */
    public static function get_contacts(int $client_id): array {
        global $wpdb;
        $junction_table = Peanut_Database::client_contacts_table();
        $contacts_table = Peanut_Database::contacts_table();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT cc.*, c.email as contact_email, c.first_name, c.last_name, c.phone as contact_phone,
                    CONCAT(c.first_name, ' ', c.last_name) as contact_name
             FROM $junction_table cc
             INNER JOIN $contacts_table c ON cc.contact_id = c.id
             WHERE cc.client_id = %d
             ORDER BY cc.is_primary DESC, cc.role ASC",
            $client_id
        ), ARRAY_A) ?: [];
    }

    /**
     * Add a contact to a client
     */
    public static function add_contact(int $client_id, int $contact_id, string $role, int $assigned_by): bool {
        global $wpdb;
        $table = Peanut_Database::client_contacts_table();

        // Check if already linked
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE client_id = %d AND contact_id = %d",
            $client_id,
            $contact_id
        ));

        if ($exists) {
            return false;
        }

        $result = $wpdb->insert($table, [
            'client_id' => $client_id,
            'contact_id' => $contact_id,
            'role' => $role,
            'is_primary' => 0,
            'assigned_by' => $assigned_by,
        ]);

        return (bool) $result;
    }

    /**
     * Remove a contact from a client
     */
    public static function remove_contact(int $client_id, int $contact_id): bool {
        global $wpdb;
        $table = Peanut_Database::client_contacts_table();

        $result = $wpdb->delete($table, [
            'client_id' => $client_id,
            'contact_id' => $contact_id,
        ]);

        return (bool) $result;
    }

    /**
     * Update contact role
     */
    public static function update_contact_role(int $client_id, int $contact_id, string $role): bool {
        global $wpdb;
        $table = Peanut_Database::client_contacts_table();

        $result = $wpdb->update(
            $table,
            ['role' => $role],
            ['client_id' => $client_id, 'contact_id' => $contact_id]
        );

        return $result !== false;
    }

    /**
     * Set primary contact
     */
    public static function set_primary_contact(int $client_id, int $contact_id): bool {
        global $wpdb;
        $table = Peanut_Database::client_contacts_table();

        // Clear existing primary
        $wpdb->update(
            $table,
            ['is_primary' => 0],
            ['client_id' => $client_id]
        );

        // Set new primary
        $result = $wpdb->update(
            $table,
            ['is_primary' => 1],
            ['client_id' => $client_id, 'contact_id' => $contact_id]
        );

        return $result !== false;
    }

    /**
     * Get primary contact for a client
     */
    public static function get_primary_contact(int $client_id): ?array {
        global $wpdb;
        $junction_table = Peanut_Database::client_contacts_table();
        $contacts_table = Peanut_Database::contacts_table();

        $contact = $wpdb->get_row($wpdb->prepare(
            "SELECT cc.*, c.email as contact_email, c.first_name, c.last_name, c.phone as contact_phone,
                    CONCAT(c.first_name, ' ', c.last_name) as contact_name
             FROM $junction_table cc
             INNER JOIN $contacts_table c ON cc.contact_id = c.id
             WHERE cc.client_id = %d AND cc.is_primary = 1
             LIMIT 1",
            $client_id
        ), ARRAY_A);

        return $contact ?: null;
    }

    // ========== Statistics ==========

    /**
     * Get client statistics
     */
    public static function get_stats(int $client_id): array {
        $client = self::get_by_id($client_id);
        if (!$client) {
            return [];
        }

        return [
            'project_count' => self::get_project_count($client_id),
            'contact_count' => self::get_contact_count($client_id),
            'invoice_count' => self::get_invoice_count($client_id),
            'total_revenue' => self::get_total_revenue($client_id),
            'outstanding_balance' => self::get_outstanding_balance($client_id),
        ];
    }

    /**
     * Get contact count for a client
     */
    public static function get_contact_count(int $client_id): int {
        global $wpdb;
        $table = Peanut_Database::client_contacts_table();

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE client_id = %d",
            $client_id
        ));
    }

    /**
     * Get invoice count for a client
     */
    public static function get_invoice_count(int $client_id): int {
        global $wpdb;

        // Check if invoices table exists
        if (!method_exists('Peanut_Database', 'invoices_table')) {
            return 0;
        }

        $table = Peanut_Database::invoices_table();

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE client_id = %d",
            $client_id
        ));
    }

    /**
     * Get total revenue for a client
     */
    public static function get_total_revenue(int $client_id): float {
        global $wpdb;

        if (!method_exists('Peanut_Database', 'invoices_table')) {
            return 0.0;
        }

        $table = Peanut_Database::invoices_table();

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount_paid), 0) FROM $table WHERE client_id = %d",
            $client_id
        ));

        return (float) $total;
    }

    /**
     * Get outstanding balance for a client
     */
    public static function get_outstanding_balance(int $client_id): float {
        global $wpdb;

        if (!method_exists('Peanut_Database', 'invoices_table')) {
            return 0.0;
        }

        $table = Peanut_Database::invoices_table();

        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(balance_due), 0) FROM $table
             WHERE client_id = %d AND status NOT IN ('paid', 'cancelled')",
            $client_id
        ));

        return (float) $balance;
    }
}

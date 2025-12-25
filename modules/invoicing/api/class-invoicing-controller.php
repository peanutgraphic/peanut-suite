<?php
/**
 * Invoicing REST API Controller
 */

if (!defined('ABSPATH')) {
    exit;
}

class Invoicing_Controller extends WP_REST_Controller {

    protected $namespace = PEANUT_API_NAMESPACE;
    protected $rest_base = 'invoices';

    /**
     * Register routes
     */
    public function register_routes(): void {
        // List invoices
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args' => $this->get_collection_params(),
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
        ]);

        // Single invoice
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ],
        ]);

        // Invoice actions
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/send', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'send_invoice'],
            'permission_callback' => [$this, 'update_item_permissions_check'],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/void', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'void_invoice'],
            'permission_callback' => [$this, 'update_item_permissions_check'],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/mark-paid', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'mark_paid'],
            'permission_callback' => [$this, 'update_item_permissions_check'],
        ]);

        // Stats
        register_rest_route($this->namespace, '/' . $this->rest_base . '/stats', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_stats'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);

        // Test Stripe connection
        register_rest_route($this->namespace, '/' . $this->rest_base . '/test-stripe', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'test_stripe'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
        ]);
    }

    /**
     * Check permissions
     */
    public function get_items_permissions_check($request): bool {
        return current_user_can('manage_options') && peanut_is_agency();
    }

    public function get_item_permissions_check($request): bool {
        return $this->get_items_permissions_check($request);
    }

    public function create_item_permissions_check($request): bool {
        return $this->get_items_permissions_check($request);
    }

    public function update_item_permissions_check($request): bool {
        return $this->get_items_permissions_check($request);
    }

    public function delete_item_permissions_check($request): bool {
        return $this->get_items_permissions_check($request);
    }

    /**
     * Get invoices
     */
    public function get_items($request): WP_REST_Response {
        $invoices = Invoicing_Database::get_all([
            'status' => $request->get_param('status'),
            'search' => $request->get_param('search'),
            'orderby' => $request->get_param('orderby') ?: 'created_at',
            'order' => $request->get_param('order') ?: 'DESC',
            'limit' => $request->get_param('per_page') ?: 20,
            'offset' => (($request->get_param('page') ?: 1) - 1) * ($request->get_param('per_page') ?: 20),
        ]);

        $total = Invoicing_Database::count([
            'status' => $request->get_param('status'),
        ]);

        return new WP_REST_Response([
            'items' => $invoices,
            'total' => $total,
        ], 200);
    }

    /**
     * Get single invoice
     */
    public function get_item($request): WP_REST_Response {
        $invoice = Invoicing_Database::get((int) $request->get_param('id'));

        if (!$invoice) {
            return new WP_REST_Response(['error' => 'Invoice not found'], 404);
        }

        return new WP_REST_Response($invoice, 200);
    }

    /**
     * Create invoice
     */
    public function create_item($request): WP_REST_Response {
        $params = $request->get_json_params();

        // Validate required fields
        if (empty($params['client_email']) || empty($params['client_name'])) {
            return new WP_REST_Response(['error' => 'Client name and email are required'], 400);
        }

        if (empty($params['items']) || !is_array($params['items'])) {
            return new WP_REST_Response(['error' => 'At least one line item is required'], 400);
        }

        $stripe = new Invoicing_Stripe();

        if (!$stripe->is_configured()) {
            return new WP_REST_Response(['error' => 'Stripe is not configured'], 400);
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($params['items'] as $item) {
            $subtotal += ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
        }

        $tax_percent = floatval($params['tax_percent'] ?? 0);
        $tax_amount = $subtotal * ($tax_percent / 100);
        $discount = floatval($params['discount_amount'] ?? 0);
        $total = $subtotal + $tax_amount - $discount;

        // Generate invoice number
        $invoice_number = Invoicing_Database::generate_invoice_number();

        // Create in Stripe
        $stripe_invoice = $stripe->create_invoice([
            'client_email' => sanitize_email($params['client_email']),
            'client_name' => sanitize_text_field($params['client_name']),
            'client_company' => sanitize_text_field($params['client_company'] ?? ''),
            'days_until_due' => intval($params['days_until_due'] ?? 30),
            'description' => sanitize_textarea_field($params['notes'] ?? ''),
            'footer' => sanitize_textarea_field($params['footer'] ?? ''),
            'items' => $params['items'],
            'metadata' => [
                'invoice_number' => $invoice_number,
                'peanut_user_id' => get_current_user_id(),
            ],
        ]);

        if (isset($stripe_invoice['error'])) {
            return new WP_REST_Response(['error' => $stripe_invoice['error']], 400);
        }

        // Save to database
        $invoice_id = Invoicing_Database::create([
            'stripe_invoice_id' => $stripe_invoice['id'],
            'stripe_customer_id' => $stripe_invoice['customer'],
            'invoice_number' => $invoice_number,
            'contact_id' => intval($params['contact_id'] ?? 0) ?: null,
            'client_name' => sanitize_text_field($params['client_name']),
            'client_email' => sanitize_email($params['client_email']),
            'client_company' => sanitize_text_field($params['client_company'] ?? ''),
            'client_address' => sanitize_textarea_field($params['client_address'] ?? ''),
            'subtotal' => $subtotal,
            'tax_amount' => $tax_amount,
            'tax_percent' => $tax_percent,
            'discount_amount' => $discount,
            'total' => $total,
            'currency' => strtoupper($params['currency'] ?? 'USD'),
            'status' => 'draft',
            'due_date' => date('Y-m-d', strtotime('+' . ($params['days_until_due'] ?? 30) . ' days')),
            'notes' => sanitize_textarea_field($params['notes'] ?? ''),
            'footer' => sanitize_textarea_field($params['footer'] ?? ''),
        ]);

        if (!$invoice_id) {
            return new WP_REST_Response(['error' => 'Failed to save invoice'], 500);
        }

        // Save line items
        foreach ($params['items'] as $index => $item) {
            Invoicing_Database::add_item($invoice_id, [
                'description' => sanitize_text_field($item['description']),
                'quantity' => floatval($item['quantity'] ?? 1),
                'unit_price' => floatval($item['unit_price'] ?? 0),
                'sort_order' => $index,
            ]);
        }

        return new WP_REST_Response([
            'id' => $invoice_id,
            'invoice_number' => $invoice_number,
            'stripe_invoice_id' => $stripe_invoice['id'],
        ], 201);
    }

    /**
     * Update invoice
     */
    public function update_item($request): WP_REST_Response {
        $id = (int) $request->get_param('id');
        $invoice = Invoicing_Database::get($id);

        if (!$invoice) {
            return new WP_REST_Response(['error' => 'Invoice not found'], 404);
        }

        if ($invoice['status'] !== 'draft') {
            return new WP_REST_Response(['error' => 'Only draft invoices can be edited'], 400);
        }

        $params = $request->get_json_params();

        // Update local record
        $update_data = [];

        if (isset($params['client_name'])) {
            $update_data['client_name'] = sanitize_text_field($params['client_name']);
        }
        if (isset($params['client_email'])) {
            $update_data['client_email'] = sanitize_email($params['client_email']);
        }
        if (isset($params['client_company'])) {
            $update_data['client_company'] = sanitize_text_field($params['client_company']);
        }
        if (isset($params['notes'])) {
            $update_data['notes'] = sanitize_textarea_field($params['notes']);
        }
        if (isset($params['due_date'])) {
            $update_data['due_date'] = sanitize_text_field($params['due_date']);
        }

        if (!empty($update_data)) {
            Invoicing_Database::update($id, $update_data);
        }

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * Delete invoice
     */
    public function delete_item($request): WP_REST_Response {
        $id = (int) $request->get_param('id');
        $invoice = Invoicing_Database::get($id);

        if (!$invoice) {
            return new WP_REST_Response(['error' => 'Invoice not found'], 404);
        }

        // Delete from Stripe if draft
        if ($invoice['status'] === 'draft' && !empty($invoice['stripe_invoice_id'])) {
            $stripe = new Invoicing_Stripe();
            $stripe->delete_invoice($invoice['stripe_invoice_id']);
        }

        Invoicing_Database::delete($id);

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * Send invoice
     */
    public function send_invoice($request): WP_REST_Response {
        $id = (int) $request->get_param('id');
        $invoice = Invoicing_Database::get($id);

        if (!$invoice) {
            return new WP_REST_Response(['error' => 'Invoice not found'], 404);
        }

        if (!in_array($invoice['status'], ['draft'])) {
            return new WP_REST_Response(['error' => 'Invoice has already been sent'], 400);
        }

        $stripe = new Invoicing_Stripe();

        // Finalize the invoice first
        $finalized = $stripe->finalize_invoice($invoice['stripe_invoice_id']);
        if (isset($finalized['error'])) {
            return new WP_REST_Response(['error' => $finalized['error']], 400);
        }

        // Send it
        $sent = $stripe->send_invoice($invoice['stripe_invoice_id']);
        if (isset($sent['error'])) {
            return new WP_REST_Response(['error' => $sent['error']], 400);
        }

        // Update local record
        Invoicing_Database::update($id, [
            'status' => 'sent',
            'sent_at' => current_time('mysql'),
            'payment_url' => $sent['hosted_invoice_url'] ?? null,
            'pdf_url' => $sent['invoice_pdf'] ?? null,
        ]);

        return new WP_REST_Response([
            'success' => true,
            'payment_url' => $sent['hosted_invoice_url'] ?? null,
            'pdf_url' => $sent['invoice_pdf'] ?? null,
        ], 200);
    }

    /**
     * Void invoice
     */
    public function void_invoice($request): WP_REST_Response {
        $id = (int) $request->get_param('id');
        $invoice = Invoicing_Database::get($id);

        if (!$invoice) {
            return new WP_REST_Response(['error' => 'Invoice not found'], 404);
        }

        if ($invoice['status'] === 'paid') {
            return new WP_REST_Response(['error' => 'Cannot void a paid invoice'], 400);
        }

        $stripe = new Invoicing_Stripe();
        $result = $stripe->void_invoice($invoice['stripe_invoice_id']);

        if (isset($result['error'])) {
            return new WP_REST_Response(['error' => $result['error']], 400);
        }

        Invoicing_Database::update($id, ['status' => 'voided']);

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * Mark invoice as paid
     */
    public function mark_paid($request): WP_REST_Response {
        $id = (int) $request->get_param('id');
        $invoice = Invoicing_Database::get($id);

        if (!$invoice) {
            return new WP_REST_Response(['error' => 'Invoice not found'], 404);
        }

        if ($invoice['status'] === 'paid') {
            return new WP_REST_Response(['error' => 'Invoice is already paid'], 400);
        }

        $stripe = new Invoicing_Stripe();

        // Need to finalize first if still draft
        if ($invoice['status'] === 'draft') {
            $stripe->finalize_invoice($invoice['stripe_invoice_id']);
        }

        $result = $stripe->mark_as_paid($invoice['stripe_invoice_id']);

        if (isset($result['error'])) {
            return new WP_REST_Response(['error' => $result['error']], 400);
        }

        Invoicing_Database::update($id, [
            'status' => 'paid',
            'paid_at' => current_time('mysql'),
        ]);

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * Get stats
     */
    public function get_stats($request): WP_REST_Response {
        $stats = Invoicing_Database::get_stats();
        return new WP_REST_Response($stats, 200);
    }

    /**
     * Test Stripe connection
     */
    public function test_stripe($request): WP_REST_Response {
        $stripe = new Invoicing_Stripe();
        $result = $stripe->test_connection();
        return new WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get collection params
     */
    public function get_collection_params(): array {
        return [
            'status' => [
                'type' => 'string',
                'enum' => ['draft', 'sent', 'paid', 'overdue', 'voided'],
            ],
            'search' => [
                'type' => 'string',
            ],
            'page' => [
                'type' => 'integer',
                'default' => 1,
            ],
            'per_page' => [
                'type' => 'integer',
                'default' => 20,
            ],
            'orderby' => [
                'type' => 'string',
                'default' => 'created_at',
            ],
            'order' => [
                'type' => 'string',
                'enum' => ['ASC', 'DESC'],
                'default' => 'DESC',
            ],
        ];
    }
}

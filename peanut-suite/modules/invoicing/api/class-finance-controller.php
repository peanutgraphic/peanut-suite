<?php
/**
 * Finance REST API Controller
 *
 * Handles invoices, quotes, expenses, recurring invoices, and payments.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Finance_Controller extends WP_REST_Controller {

    protected $namespace = PEANUT_API_NAMESPACE;

    /**
     * Register all finance routes
     */
    public function register_routes(): void {
        $this->register_dashboard_routes();
        $this->register_invoice_routes();
        $this->register_quote_routes();
        $this->register_expense_routes();
        $this->register_recurring_routes();
        $this->register_payment_routes();
    }

    /**
     * Dashboard routes
     */
    private function register_dashboard_routes(): void {
        register_rest_route($this->namespace, '/finance/dashboard', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_dashboard'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/revenue', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_revenue'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/profit-loss', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_profit_loss'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/limits', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_limits'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    /**
     * Invoice routes
     */
    private function register_invoice_routes(): void {
        // List & Create
        register_rest_route($this->namespace, '/finance/invoices', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_invoices'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_invoice'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        // Single
        register_rest_route($this->namespace, '/finance/invoices/(?P<id>[\d]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_invoice'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_invoice'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_invoice'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        // Actions
        register_rest_route($this->namespace, '/finance/invoices/(?P<id>[\d]+)/send', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'send_invoice'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/invoices/(?P<id>[\d]+)/cancel', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'cancel_invoice'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/invoices/stats', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_invoice_stats'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/invoices/next-number', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_next_invoice_number'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    /**
     * Quote routes
     */
    private function register_quote_routes(): void {
        register_rest_route($this->namespace, '/finance/quotes', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_quotes'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_quote'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/finance/quotes/(?P<id>[\d]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_quote'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_quote'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_quote'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/finance/quotes/(?P<id>[\d]+)/send', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'send_quote'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/quotes/(?P<id>[\d]+)/accept', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'accept_quote'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/quotes/(?P<id>[\d]+)/decline', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'decline_quote'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/quotes/(?P<id>[\d]+)/convert', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'convert_quote'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    /**
     * Expense routes
     */
    private function register_expense_routes(): void {
        register_rest_route($this->namespace, '/finance/expenses', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_expenses'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_expense'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/finance/expenses/(?P<id>[\d]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_expense'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_expense'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_expense'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/finance/expenses/categories', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_expense_categories'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/expenses/add-to-invoice', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'add_expenses_to_invoice'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    /**
     * Recurring routes
     */
    private function register_recurring_routes(): void {
        register_rest_route($this->namespace, '/finance/recurring', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_recurring'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_recurring'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/finance/recurring/(?P<id>[\d]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_recurring_item'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_recurring'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_recurring'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/finance/recurring/(?P<id>[\d]+)/pause', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'pause_recurring'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/recurring/(?P<id>[\d]+)/resume', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'resume_recurring'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/recurring/(?P<id>[\d]+)/generate', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'generate_recurring'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/recurring/(?P<id>[\d]+)/preview', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'preview_recurring'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    /**
     * Payment routes
     */
    private function register_payment_routes(): void {
        register_rest_route($this->namespace, '/finance/payments', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_payments'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_payment'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/finance/payments/(?P<id>[\d]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_payment'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_payment'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_payment'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/finance/payments/methods', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_payment_methods'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/finance/invoices/(?P<id>[\d]+)/payments', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_invoice_payments'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    /**
     * Permission check
     */
    public function check_permission(): bool {
        return current_user_can('manage_options') && peanut_is_pro();
    }

    // ========== Dashboard Handlers ==========

    public function get_dashboard($request): WP_REST_Response {
        $args = [
            'project_id' => $request->get_param('project_id'),
        ];

        return new WP_REST_Response(Finance_Service::get_dashboard($args), 200);
    }

    public function get_revenue($request): WP_REST_Response {
        $args = [
            'period' => $request->get_param('period') ?: 'month',
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
            'project_id' => $request->get_param('project_id'),
        ];

        return new WP_REST_Response(Finance_Service::get_revenue($args), 200);
    }

    public function get_profit_loss($request): WP_REST_Response {
        $args = [
            'date_from' => $request->get_param('date_from') ?: date('Y-01-01'),
            'date_to' => $request->get_param('date_to') ?: date('Y-12-31'),
            'project_id' => $request->get_param('project_id'),
        ];

        return new WP_REST_Response(Finance_Service::get_profit_loss($args), 200);
    }

    public function get_limits($request): WP_REST_Response {
        return new WP_REST_Response(Finance_Service::get_limits(), 200);
    }

    // ========== Invoice Handlers ==========

    public function get_invoices($request): WP_REST_Response {
        $args = [
            'project_id' => $request->get_param('project_id'),
            'status' => $request->get_param('status'),
            'search' => $request->get_param('search'),
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
            'orderby' => $request->get_param('orderby') ?: 'created_at',
            'order' => $request->get_param('order') ?: 'DESC',
            'limit' => $request->get_param('per_page') ?: 20,
            'offset' => (($request->get_param('page') ?: 1) - 1) * ($request->get_param('per_page') ?: 20),
        ];

        $invoices = Invoice_Service::get_all($args);
        $total = Invoice_Service::count($args);

        return new WP_REST_Response([
            'items' => $invoices,
            'total' => $total,
        ], 200);
    }

    public function get_invoice($request): WP_REST_Response {
        $invoice = Invoice_Service::get((int) $request->get_param('id'));

        if (!$invoice) {
            return new WP_REST_Response(['error' => 'Invoice not found'], 404);
        }

        return new WP_REST_Response($invoice, 200);
    }

    public function create_invoice($request): WP_REST_Response {
        $params = $request->get_json_params();

        // Check limits
        $limits = Invoice_Service::check_limit();
        if (!$limits['can_create']) {
            return new WP_REST_Response([
                'error' => 'Invoice limit reached for your plan',
                'limits' => $limits,
            ], 403);
        }

        // Validate
        if (empty($params['project_id'])) {
            return new WP_REST_Response(['error' => 'Project is required'], 400);
        }

        if (empty($params['client_name']) || empty($params['client_email'])) {
            return new WP_REST_Response(['error' => 'Client name and email are required'], 400);
        }

        $invoice_id = Invoice_Service::create($this->sanitize_invoice_data($params));

        if (!$invoice_id) {
            return new WP_REST_Response(['error' => 'Failed to create invoice'], 500);
        }

        return new WP_REST_Response([
            'id' => $invoice_id,
            'invoice' => Invoice_Service::get($invoice_id),
        ], 201);
    }

    public function update_invoice($request): WP_REST_Response {
        $id = (int) $request->get_param('id');
        $invoice = Invoice_Service::get($id);

        if (!$invoice) {
            return new WP_REST_Response(['error' => 'Invoice not found'], 404);
        }

        $params = $request->get_json_params();
        $success = Invoice_Service::update($id, $this->sanitize_invoice_data($params));

        if (!$success) {
            return new WP_REST_Response(['error' => 'Failed to update invoice'], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'invoice' => Invoice_Service::get($id),
        ], 200);
    }

    public function delete_invoice($request): WP_REST_Response {
        $id = (int) $request->get_param('id');

        if (!Invoice_Service::get($id)) {
            return new WP_REST_Response(['error' => 'Invoice not found'], 404);
        }

        Invoice_Service::delete($id);

        return new WP_REST_Response(['success' => true], 200);
    }

    public function send_invoice($request): WP_REST_Response {
        $id = (int) $request->get_param('id');

        if (!Invoice_Service::mark_sent($id)) {
            return new WP_REST_Response(['error' => 'Failed to mark invoice as sent'], 500);
        }

        return new WP_REST_Response(['success' => true], 200);
    }

    public function cancel_invoice($request): WP_REST_Response {
        $id = (int) $request->get_param('id');

        if (!Invoice_Service::cancel($id)) {
            return new WP_REST_Response(['error' => 'Failed to cancel invoice'], 500);
        }

        return new WP_REST_Response(['success' => true], 200);
    }

    public function get_invoice_stats($request): WP_REST_Response {
        return new WP_REST_Response(Invoice_Service::get_stats([
            'project_id' => $request->get_param('project_id'),
        ]), 200);
    }

    public function get_next_invoice_number($request): WP_REST_Response {
        return new WP_REST_Response([
            'number' => Invoice_Service::generate_number(),
        ], 200);
    }

    // ========== Quote Handlers ==========

    public function get_quotes($request): WP_REST_Response {
        $args = [
            'project_id' => $request->get_param('project_id'),
            'status' => $request->get_param('status'),
            'search' => $request->get_param('search'),
            'orderby' => $request->get_param('orderby') ?: 'created_at',
            'order' => $request->get_param('order') ?: 'DESC',
            'limit' => $request->get_param('per_page') ?: 20,
            'offset' => (($request->get_param('page') ?: 1) - 1) * ($request->get_param('per_page') ?: 20),
        ];

        return new WP_REST_Response([
            'items' => Quote_Service::get_all($args),
            'total' => Quote_Service::count($args),
        ], 200);
    }

    public function get_quote($request): WP_REST_Response {
        $quote = Quote_Service::get((int) $request->get_param('id'));

        if (!$quote) {
            return new WP_REST_Response(['error' => 'Quote not found'], 404);
        }

        return new WP_REST_Response($quote, 200);
    }

    public function create_quote($request): WP_REST_Response {
        $params = $request->get_json_params();

        if (empty($params['project_id'])) {
            return new WP_REST_Response(['error' => 'Project is required'], 400);
        }

        $quote_id = Quote_Service::create($this->sanitize_quote_data($params));

        if (!$quote_id) {
            return new WP_REST_Response(['error' => 'Failed to create quote'], 500);
        }

        return new WP_REST_Response([
            'id' => $quote_id,
            'quote' => Quote_Service::get($quote_id),
        ], 201);
    }

    public function update_quote($request): WP_REST_Response {
        $id = (int) $request->get_param('id');

        if (!Quote_Service::get($id)) {
            return new WP_REST_Response(['error' => 'Quote not found'], 404);
        }

        $params = $request->get_json_params();
        Quote_Service::update($id, $this->sanitize_quote_data($params));

        return new WP_REST_Response([
            'success' => true,
            'quote' => Quote_Service::get($id),
        ], 200);
    }

    public function delete_quote($request): WP_REST_Response {
        $id = (int) $request->get_param('id');

        if (!Quote_Service::get($id)) {
            return new WP_REST_Response(['error' => 'Quote not found'], 404);
        }

        Quote_Service::delete($id);

        return new WP_REST_Response(['success' => true], 200);
    }

    public function send_quote($request): WP_REST_Response {
        Quote_Service::mark_sent((int) $request->get_param('id'));
        return new WP_REST_Response(['success' => true], 200);
    }

    public function accept_quote($request): WP_REST_Response {
        Quote_Service::mark_accepted((int) $request->get_param('id'));
        return new WP_REST_Response(['success' => true], 200);
    }

    public function decline_quote($request): WP_REST_Response {
        Quote_Service::mark_declined((int) $request->get_param('id'));
        return new WP_REST_Response(['success' => true], 200);
    }

    public function convert_quote($request): WP_REST_Response {
        $invoice_id = Quote_Service::convert_to_invoice((int) $request->get_param('id'));

        if (!$invoice_id) {
            return new WP_REST_Response(['error' => 'Failed to convert quote'], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'invoice_id' => $invoice_id,
            'invoice' => Invoice_Service::get($invoice_id),
        ], 200);
    }

    // ========== Expense Handlers ==========

    public function get_expenses($request): WP_REST_Response {
        $args = [
            'project_id' => $request->get_param('project_id'),
            'category' => $request->get_param('category'),
            'billable' => $request->get_param('billable'),
            'invoiced' => $request->get_param('invoiced'),
            'search' => $request->get_param('search'),
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
            'orderby' => $request->get_param('orderby') ?: 'expense_date',
            'order' => $request->get_param('order') ?: 'DESC',
            'limit' => $request->get_param('per_page') ?: 50,
            'offset' => (($request->get_param('page') ?: 1) - 1) * ($request->get_param('per_page') ?: 50),
        ];

        return new WP_REST_Response([
            'items' => Expense_Service::get_all($args),
            'total' => Expense_Service::count($args),
            'stats' => Expense_Service::get_stats($args),
        ], 200);
    }

    public function get_expense($request): WP_REST_Response {
        $expense = Expense_Service::get((int) $request->get_param('id'));

        if (!$expense) {
            return new WP_REST_Response(['error' => 'Expense not found'], 404);
        }

        return new WP_REST_Response($expense, 200);
    }

    public function create_expense($request): WP_REST_Response {
        $params = $request->get_json_params();

        $expense_id = Expense_Service::create($this->sanitize_expense_data($params));

        if (!$expense_id) {
            return new WP_REST_Response(['error' => 'Failed to create expense'], 500);
        }

        return new WP_REST_Response([
            'id' => $expense_id,
            'expense' => Expense_Service::get($expense_id),
        ], 201);
    }

    public function update_expense($request): WP_REST_Response {
        $id = (int) $request->get_param('id');

        if (!Expense_Service::get($id)) {
            return new WP_REST_Response(['error' => 'Expense not found'], 404);
        }

        $params = $request->get_json_params();
        Expense_Service::update($id, $this->sanitize_expense_data($params));

        return new WP_REST_Response([
            'success' => true,
            'expense' => Expense_Service::get($id),
        ], 200);
    }

    public function delete_expense($request): WP_REST_Response {
        $id = (int) $request->get_param('id');

        if (!Expense_Service::get($id)) {
            return new WP_REST_Response(['error' => 'Expense not found'], 404);
        }

        Expense_Service::delete($id);

        return new WP_REST_Response(['success' => true], 200);
    }

    public function get_expense_categories($request): WP_REST_Response {
        return new WP_REST_Response(Expense_Service::get_categories(), 200);
    }

    public function add_expenses_to_invoice($request): WP_REST_Response {
        $params = $request->get_json_params();

        if (empty($params['invoice_id']) || empty($params['expense_ids'])) {
            return new WP_REST_Response(['error' => 'Invoice ID and expense IDs required'], 400);
        }

        $added = Expense_Service::add_to_invoice(
            (int) $params['invoice_id'],
            array_map('intval', $params['expense_ids'])
        );

        return new WP_REST_Response([
            'success' => true,
            'added' => $added,
        ], 200);
    }

    // ========== Recurring Handlers ==========

    public function get_recurring($request): WP_REST_Response {
        $args = [
            'project_id' => $request->get_param('project_id'),
            'status' => $request->get_param('status'),
            'search' => $request->get_param('search'),
            'orderby' => $request->get_param('orderby') ?: 'next_invoice_date',
            'order' => $request->get_param('order') ?: 'ASC',
            'limit' => $request->get_param('per_page') ?: 50,
            'offset' => (($request->get_param('page') ?: 1) - 1) * ($request->get_param('per_page') ?: 50),
        ];

        return new WP_REST_Response([
            'items' => Recurring_Service::get_all($args),
            'total' => Recurring_Service::count($args),
        ], 200);
    }

    public function get_recurring_item($request): WP_REST_Response {
        $recurring = Recurring_Service::get((int) $request->get_param('id'));

        if (!$recurring) {
            return new WP_REST_Response(['error' => 'Recurring invoice not found'], 404);
        }

        return new WP_REST_Response($recurring, 200);
    }

    public function create_recurring($request): WP_REST_Response {
        $params = $request->get_json_params();

        if (empty($params['project_id'])) {
            return new WP_REST_Response(['error' => 'Project is required'], 400);
        }

        $id = Recurring_Service::create($this->sanitize_recurring_data($params));

        if (!$id) {
            return new WP_REST_Response(['error' => 'Failed to create recurring invoice'], 500);
        }

        return new WP_REST_Response([
            'id' => $id,
            'recurring' => Recurring_Service::get($id),
        ], 201);
    }

    public function update_recurring($request): WP_REST_Response {
        $id = (int) $request->get_param('id');

        if (!Recurring_Service::get($id)) {
            return new WP_REST_Response(['error' => 'Recurring invoice not found'], 404);
        }

        $params = $request->get_json_params();
        Recurring_Service::update($id, $this->sanitize_recurring_data($params));

        return new WP_REST_Response([
            'success' => true,
            'recurring' => Recurring_Service::get($id),
        ], 200);
    }

    public function delete_recurring($request): WP_REST_Response {
        $id = (int) $request->get_param('id');

        if (!Recurring_Service::get($id)) {
            return new WP_REST_Response(['error' => 'Recurring invoice not found'], 404);
        }

        Recurring_Service::delete($id);

        return new WP_REST_Response(['success' => true], 200);
    }

    public function pause_recurring($request): WP_REST_Response {
        Recurring_Service::pause((int) $request->get_param('id'));
        return new WP_REST_Response(['success' => true], 200);
    }

    public function resume_recurring($request): WP_REST_Response {
        Recurring_Service::resume((int) $request->get_param('id'));
        return new WP_REST_Response(['success' => true], 200);
    }

    public function generate_recurring($request): WP_REST_Response {
        $invoice_id = Recurring_Service::generate_invoice((int) $request->get_param('id'));

        if (!$invoice_id) {
            return new WP_REST_Response(['error' => 'Failed to generate invoice'], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'invoice_id' => $invoice_id,
        ], 200);
    }

    public function preview_recurring($request): WP_REST_Response {
        $preview = Recurring_Service::preview((int) $request->get_param('id'));

        if (!$preview) {
            return new WP_REST_Response(['error' => 'Recurring invoice not found'], 404);
        }

        return new WP_REST_Response($preview, 200);
    }

    // ========== Payment Handlers ==========

    public function get_payments($request): WP_REST_Response {
        $args = [
            'invoice_id' => $request->get_param('invoice_id'),
            'payment_method' => $request->get_param('payment_method'),
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
            'orderby' => $request->get_param('orderby') ?: 'payment_date',
            'order' => $request->get_param('order') ?: 'DESC',
            'limit' => $request->get_param('per_page') ?: 50,
            'offset' => (($request->get_param('page') ?: 1) - 1) * ($request->get_param('per_page') ?: 50),
        ];

        return new WP_REST_Response([
            'items' => Payment_Service::get_all($args),
            'total' => Payment_Service::count($args),
        ], 200);
    }

    public function get_payment($request): WP_REST_Response {
        $payment = Payment_Service::get((int) $request->get_param('id'));

        if (!$payment) {
            return new WP_REST_Response(['error' => 'Payment not found'], 404);
        }

        return new WP_REST_Response($payment, 200);
    }

    public function create_payment($request): WP_REST_Response {
        $params = $request->get_json_params();

        if (empty($params['invoice_id'])) {
            return new WP_REST_Response(['error' => 'Invoice ID is required'], 400);
        }

        if (empty($params['amount']) || $params['amount'] <= 0) {
            return new WP_REST_Response(['error' => 'Valid amount is required'], 400);
        }

        $payment_id = Payment_Service::record($this->sanitize_payment_data($params));

        if (!$payment_id) {
            return new WP_REST_Response(['error' => 'Failed to record payment'], 500);
        }

        return new WP_REST_Response([
            'id' => $payment_id,
            'payment' => Payment_Service::get($payment_id),
            'invoice' => Invoice_Service::get((int) $params['invoice_id']),
        ], 201);
    }

    public function update_payment($request): WP_REST_Response {
        $id = (int) $request->get_param('id');

        if (!Payment_Service::get($id)) {
            return new WP_REST_Response(['error' => 'Payment not found'], 404);
        }

        $params = $request->get_json_params();
        Payment_Service::update($id, $this->sanitize_payment_data($params));

        return new WP_REST_Response([
            'success' => true,
            'payment' => Payment_Service::get($id),
        ], 200);
    }

    public function delete_payment($request): WP_REST_Response {
        $id = (int) $request->get_param('id');
        $payment = Payment_Service::get($id);

        if (!$payment) {
            return new WP_REST_Response(['error' => 'Payment not found'], 404);
        }

        Payment_Service::delete($id);

        return new WP_REST_Response([
            'success' => true,
            'invoice' => Invoice_Service::get($payment['invoice_id']),
        ], 200);
    }

    public function get_payment_methods($request): WP_REST_Response {
        return new WP_REST_Response(Payment_Service::get_methods(), 200);
    }

    public function get_invoice_payments($request): WP_REST_Response {
        $payments = Payment_Service::get_by_invoice((int) $request->get_param('id'));
        return new WP_REST_Response($payments, 200);
    }

    // ========== Sanitization Helpers ==========

    private function sanitize_invoice_data(array $params): array {
        $data = [];

        if (isset($params['project_id'])) $data['project_id'] = intval($params['project_id']);
        if (isset($params['contact_id'])) $data['contact_id'] = intval($params['contact_id']) ?: null;
        if (isset($params['client_name'])) $data['client_name'] = sanitize_text_field($params['client_name']);
        if (isset($params['client_email'])) $data['client_email'] = sanitize_email($params['client_email']);
        if (isset($params['client_company'])) $data['client_company'] = sanitize_text_field($params['client_company']);
        if (isset($params['client_address'])) $data['client_address'] = sanitize_textarea_field($params['client_address']);
        if (isset($params['tax_percent'])) $data['tax_percent'] = floatval($params['tax_percent']);
        if (isset($params['discount_amount'])) $data['discount_amount'] = floatval($params['discount_amount']);
        if (isset($params['discount_type'])) $data['discount_type'] = sanitize_text_field($params['discount_type']);
        if (isset($params['currency'])) $data['currency'] = strtoupper(sanitize_text_field($params['currency']));
        if (isset($params['issue_date'])) $data['issue_date'] = sanitize_text_field($params['issue_date']);
        if (isset($params['due_date'])) $data['due_date'] = sanitize_text_field($params['due_date']);
        if (isset($params['payment_terms'])) $data['payment_terms'] = sanitize_text_field($params['payment_terms']);
        if (isset($params['notes'])) $data['notes'] = sanitize_textarea_field($params['notes']);
        if (isset($params['client_notes'])) $data['client_notes'] = sanitize_textarea_field($params['client_notes']);
        if (isset($params['footer'])) $data['footer'] = sanitize_textarea_field($params['footer']);
        if (isset($params['status'])) $data['status'] = sanitize_text_field($params['status']);

        if (isset($params['items']) && is_array($params['items'])) {
            $data['items'] = array_map(function($item) {
                return [
                    'item_type' => sanitize_text_field($item['item_type'] ?? 'service'),
                    'description' => sanitize_text_field($item['description'] ?? ''),
                    'quantity' => floatval($item['quantity'] ?? 1),
                    'hours' => isset($item['hours']) ? floatval($item['hours']) : null,
                    'rate' => isset($item['rate']) ? floatval($item['rate']) : null,
                    'unit_price' => floatval($item['unit_price'] ?? 0),
                    'taxable' => isset($item['taxable']) ? (bool) $item['taxable'] : true,
                    'sort_order' => intval($item['sort_order'] ?? 0),
                ];
            }, $params['items']);
        }

        return $data;
    }

    private function sanitize_quote_data(array $params): array {
        $data = $this->sanitize_invoice_data($params);

        if (isset($params['valid_until'])) $data['valid_until'] = sanitize_text_field($params['valid_until']);
        if (isset($params['terms'])) $data['terms'] = sanitize_textarea_field($params['terms']);

        // Handle optional items
        if (isset($data['items'])) {
            $data['items'] = array_map(function($item) use ($params) {
                if (isset($params['items'])) {
                    foreach ($params['items'] as $p) {
                        if (($p['description'] ?? '') === ($item['description'] ?? '')) {
                            $item['optional'] = isset($p['optional']) ? (bool) $p['optional'] : false;
                            break;
                        }
                    }
                }
                return $item;
            }, $data['items']);
        }

        return $data;
    }

    private function sanitize_expense_data(array $params): array {
        $data = [];

        if (isset($params['project_id'])) $data['project_id'] = intval($params['project_id']) ?: null;
        if (isset($params['vendor'])) $data['vendor'] = sanitize_text_field($params['vendor']);
        if (isset($params['category'])) $data['category'] = sanitize_text_field($params['category']);
        if (isset($params['description'])) $data['description'] = sanitize_textarea_field($params['description']);
        if (isset($params['amount'])) $data['amount'] = floatval($params['amount']);
        if (isset($params['currency'])) $data['currency'] = strtoupper(sanitize_text_field($params['currency']));
        if (isset($params['expense_date'])) $data['expense_date'] = sanitize_text_field($params['expense_date']);
        if (isset($params['receipt_url'])) $data['receipt_url'] = esc_url_raw($params['receipt_url']);
        if (isset($params['billable'])) $data['billable'] = (bool) $params['billable'];
        if (isset($params['payment_method'])) $data['payment_method'] = sanitize_text_field($params['payment_method']);
        if (isset($params['reference'])) $data['reference'] = sanitize_text_field($params['reference']);
        if (isset($params['notes'])) $data['notes'] = sanitize_textarea_field($params['notes']);

        return $data;
    }

    private function sanitize_recurring_data(array $params): array {
        $data = $this->sanitize_invoice_data($params);

        if (isset($params['template_name'])) $data['template_name'] = sanitize_text_field($params['template_name']);
        if (isset($params['frequency'])) $data['frequency'] = sanitize_text_field($params['frequency']);
        if (isset($params['day_of_week'])) $data['day_of_week'] = intval($params['day_of_week']);
        if (isset($params['day_of_month'])) $data['day_of_month'] = intval($params['day_of_month']);
        if (isset($params['start_date'])) $data['start_date'] = sanitize_text_field($params['start_date']);
        if (isset($params['end_date'])) $data['end_date'] = sanitize_text_field($params['end_date']) ?: null;
        if (isset($params['next_invoice_date'])) $data['next_invoice_date'] = sanitize_text_field($params['next_invoice_date']);
        if (isset($params['due_days'])) $data['due_days'] = intval($params['due_days']);
        if (isset($params['auto_send'])) $data['auto_send'] = (bool) $params['auto_send'];

        return $data;
    }

    private function sanitize_payment_data(array $params): array {
        $data = [];

        if (isset($params['invoice_id'])) $data['invoice_id'] = intval($params['invoice_id']);
        if (isset($params['amount'])) $data['amount'] = floatval($params['amount']);
        if (isset($params['currency'])) $data['currency'] = strtoupper(sanitize_text_field($params['currency']));
        if (isset($params['payment_method'])) $data['payment_method'] = sanitize_text_field($params['payment_method']);
        if (isset($params['payment_date'])) $data['payment_date'] = sanitize_text_field($params['payment_date']);
        if (isset($params['reference'])) $data['reference'] = sanitize_text_field($params['reference']);
        if (isset($params['notes'])) $data['notes'] = sanitize_textarea_field($params['notes']);

        return $data;
    }
}

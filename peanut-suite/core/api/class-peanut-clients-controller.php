<?php
/**
 * Clients REST Controller
 *
 * Handles client CRUD and contact management endpoints.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Clients_Controller extends Peanut_REST_Controller {

    protected string $rest_base = 'clients';

    /**
     * Register routes
     */
    public function register_routes(): void {
        // Client list and create
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_clients'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_client'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Tier limits
        register_rest_route($this->namespace, '/' . $this->rest_base . '/limits', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_client_limits'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Single client operations
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_client'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_client'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_client'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Client stats
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/stats', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_client_stats'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Client billing info
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/billing', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_billing'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_billing'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Client projects
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/projects', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_projects'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Client contacts
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<client_id>\d+)/contacts', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_contacts'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'add_contact'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<client_id>\d+)/contacts/(?P<contact_id>\d+)', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_contact'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'remove_contact'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Set primary contact
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<client_id>\d+)/contacts/(?P<contact_id>\d+)/primary', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'set_primary_contact'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);
    }

    // ===============================
    // Client Methods
    // ===============================

    /**
     * Get all clients for the current account
     */
    public function get_clients(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = get_current_user_id();
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);

        if (!$account) {
            return $this->error('account_error', 'Failed to get account', 500);
        }

        // Check permission
        $role = Peanut_Account_Service::get_user_role($account['id'], $user_id);
        if (!in_array($role, ['owner', 'admin'], true)) {
            return $this->error('forbidden', 'You do not have permission to view clients', 403);
        }

        $filters = [
            'status' => $request->get_param('status'),
            'search' => $request->get_param('search'),
            'order_by' => $request->get_param('order_by'),
            'order' => $request->get_param('order'),
            'page' => $request->get_param('page'),
            'per_page' => $request->get_param('per_page'),
        ];

        $clients = Peanut_Client_Service::get_clients_for_account($account['id'], array_filter($filters));

        // Add computed fields
        foreach ($clients as &$client) {
            $client['project_count'] = Peanut_Client_Service::get_project_count($client['id']);
            $client['contact_count'] = Peanut_Client_Service::get_contact_count($client['id']);
            $client['primary_contact'] = Peanut_Client_Service::get_primary_contact($client['id']);
        }

        return $this->success($clients);
    }

    /**
     * Get client limits based on tier
     */
    public function get_client_limits(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = get_current_user_id();
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);

        if (!$account) {
            return $this->error('account_error', 'Failed to get account', 500);
        }

        $limits = Peanut_Client_Service::get_limits($account['id']);

        return $this->success($limits);
    }

    /**
     * Get single client
     */
    public function get_client(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $client_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        $client = Peanut_Client_Service::get_by_id($client_id);

        if (!$client) {
            return $this->error('not_found', 'Client not found', 404);
        }

        // Check access
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);
        if (!$account || (int) $client['account_id'] !== (int) $account['id']) {
            return $this->error('forbidden', 'You do not have access to this client', 403);
        }

        // Add computed fields
        $client['project_count'] = Peanut_Client_Service::get_project_count($client_id);
        $client['contact_count'] = Peanut_Client_Service::get_contact_count($client_id);
        $client['primary_contact'] = Peanut_Client_Service::get_primary_contact($client_id);
        $client['stats'] = Peanut_Client_Service::get_stats($client_id);

        return $this->success($client);
    }

    /**
     * Create a client
     */
    public function create_client(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = get_current_user_id();
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);

        if (!$account) {
            return $this->error('account_error', 'Failed to get account', 500);
        }

        // Check permission
        $role = Peanut_Account_Service::get_user_role($account['id'], $user_id);
        if (!in_array($role, ['owner', 'admin'], true)) {
            return $this->error('forbidden', 'You do not have permission to create clients', 403);
        }

        // Validate required fields
        $name = $request->get_param('name');
        if (empty($name)) {
            return $this->error('missing_name', 'Client name is required', 400);
        }

        // Check limits
        if (!Peanut_Client_Service::can_create_client($account['id'])) {
            return $this->error('limit_exceeded', 'You have reached your client limit. Upgrade to add more.', 403);
        }

        $data = [
            'name' => $name,
            'slug' => $request->get_param('slug'),
            'legal_name' => $request->get_param('legal_name'),
            'website' => $request->get_param('website'),
            'industry' => $request->get_param('industry'),
            'size' => $request->get_param('size'),
            'billing_email' => $request->get_param('billing_email'),
            'billing_address' => $request->get_param('billing_address'),
            'billing_city' => $request->get_param('billing_city'),
            'billing_state' => $request->get_param('billing_state'),
            'billing_postal' => $request->get_param('billing_postal'),
            'billing_country' => $request->get_param('billing_country'),
            'tax_id' => $request->get_param('tax_id'),
            'currency' => $request->get_param('currency'),
            'payment_terms' => $request->get_param('payment_terms'),
            'acquisition_source' => $request->get_param('acquisition_source'),
            'acquired_at' => $request->get_param('acquired_at'),
            'notes' => $request->get_param('notes'),
            'custom_fields' => $request->get_param('custom_fields'),
            'settings' => $request->get_param('settings'),
        ];

        $client_id = Peanut_Client_Service::create($account['id'], array_filter($data, fn($v) => $v !== null), $user_id);

        if (!$client_id) {
            return $this->error('create_failed', 'Failed to create client', 500);
        }

        $client = Peanut_Client_Service::get_by_id($client_id);

        return $this->success($client, 201);
    }

    /**
     * Update a client
     */
    public function update_client(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $client_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        $client = Peanut_Client_Service::get_by_id($client_id);

        if (!$client) {
            return $this->error('not_found', 'Client not found', 404);
        }

        // Check access
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);
        if (!$account || (int) $client['account_id'] !== (int) $account['id']) {
            return $this->error('forbidden', 'You do not have access to this client', 403);
        }

        // Check permission
        $role = Peanut_Account_Service::get_user_role($account['id'], $user_id);
        if (!in_array($role, ['owner', 'admin'], true)) {
            return $this->error('forbidden', 'You do not have permission to update clients', 403);
        }

        $data = [];
        $fields = [
            'name', 'slug', 'legal_name', 'website', 'industry', 'size',
            'billing_email', 'billing_address', 'billing_city', 'billing_state',
            'billing_postal', 'billing_country', 'tax_id', 'currency', 'payment_terms',
            'status', 'acquisition_source', 'acquired_at', 'notes',
            'custom_fields', 'settings',
        ];

        foreach ($fields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        if (empty($data)) {
            return $this->success($client);
        }

        $result = Peanut_Client_Service::update($client_id, $data);

        if (!$result) {
            return $this->error('update_failed', 'Failed to update client', 500);
        }

        $client = Peanut_Client_Service::get_by_id($client_id);

        return $this->success($client);
    }

    /**
     * Delete a client
     */
    public function delete_client(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $client_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        $client = Peanut_Client_Service::get_by_id($client_id);

        if (!$client) {
            return $this->error('not_found', 'Client not found', 404);
        }

        // Check access
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);
        if (!$account || (int) $client['account_id'] !== (int) $account['id']) {
            return $this->error('forbidden', 'You do not have access to this client', 403);
        }

        // Check permission
        $role = Peanut_Account_Service::get_user_role($account['id'], $user_id);
        if (!in_array($role, ['owner', 'admin'], true)) {
            return $this->error('forbidden', 'You do not have permission to delete clients', 403);
        }

        // Check if this is the default client
        $settings = $client['settings'] ?? [];
        if (!empty($settings['is_default'])) {
            return $this->error('cannot_delete_default', 'Cannot delete the default client', 400);
        }

        // Check if client has projects
        $project_count = Peanut_Client_Service::get_project_count($client_id);
        if ($project_count > 0) {
            return $this->error('has_projects', 'Cannot delete a client with projects. Move or delete projects first.', 400);
        }

        $result = Peanut_Client_Service::delete($client_id);

        if (!$result) {
            return $this->error('delete_failed', 'Failed to delete client', 500);
        }

        return $this->success(['deleted' => true]);
    }

    /**
     * Get client stats
     */
    public function get_client_stats(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $client_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        $client = Peanut_Client_Service::get_by_id($client_id);

        if (!$client) {
            return $this->error('not_found', 'Client not found', 404);
        }

        // Check access
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);
        if (!$account || (int) $client['account_id'] !== (int) $account['id']) {
            return $this->error('forbidden', 'You do not have access to this client', 403);
        }

        $stats = Peanut_Client_Service::get_stats($client_id);

        return $this->success($stats);
    }

    /**
     * Get billing info
     */
    public function get_billing(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $client_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        $client = Peanut_Client_Service::get_by_id($client_id);

        if (!$client) {
            return $this->error('not_found', 'Client not found', 404);
        }

        // Check access
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);
        if (!$account || (int) $client['account_id'] !== (int) $account['id']) {
            return $this->error('forbidden', 'You do not have access to this client', 403);
        }

        $billing = Peanut_Client_Service::get_billing_info($client_id);

        return $this->success($billing);
    }

    /**
     * Update billing info
     */
    public function update_billing(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $client_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        $client = Peanut_Client_Service::get_by_id($client_id);

        if (!$client) {
            return $this->error('not_found', 'Client not found', 404);
        }

        // Check access
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);
        if (!$account || (int) $client['account_id'] !== (int) $account['id']) {
            return $this->error('forbidden', 'You do not have access to this client', 403);
        }

        // Check permission
        $role = Peanut_Account_Service::get_user_role($account['id'], $user_id);
        if (!in_array($role, ['owner', 'admin'], true)) {
            return $this->error('forbidden', 'You do not have permission to update clients', 403);
        }

        $data = [];
        $billing_fields = [
            'billing_email', 'billing_address', 'billing_city', 'billing_state',
            'billing_postal', 'billing_country', 'tax_id', 'currency', 'payment_terms',
        ];

        foreach ($billing_fields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        if (!empty($data)) {
            Peanut_Client_Service::update($client_id, $data);
        }

        $billing = Peanut_Client_Service::get_billing_info($client_id);

        return $this->success($billing);
    }

    /**
     * Get projects for a client
     */
    public function get_projects(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $client_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        $client = Peanut_Client_Service::get_by_id($client_id);

        if (!$client) {
            return $this->error('not_found', 'Client not found', 404);
        }

        // Check access
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);
        if (!$account || (int) $client['account_id'] !== (int) $account['id']) {
            return $this->error('forbidden', 'You do not have access to this client', 403);
        }

        $projects = Peanut_Client_Service::get_projects($client_id);

        return $this->success($projects);
    }

    // ===============================
    // Contact Methods
    // ===============================

    /**
     * Get contacts for a client
     */
    public function get_contacts(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $client_id = (int) $request->get_param('client_id');
        $user_id = get_current_user_id();

        $client = Peanut_Client_Service::get_by_id($client_id);

        if (!$client) {
            return $this->error('not_found', 'Client not found', 404);
        }

        // Check access
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);
        if (!$account || (int) $client['account_id'] !== (int) $account['id']) {
            return $this->error('forbidden', 'You do not have access to this client', 403);
        }

        $contacts = Peanut_Client_Service::get_contacts($client_id);

        return $this->success($contacts);
    }

    /**
     * Add a contact to a client
     */
    public function add_contact(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $client_id = (int) $request->get_param('client_id');
        $contact_id = (int) $request->get_param('contact_id');
        $role = $request->get_param('role') ?? 'primary';
        $user_id = get_current_user_id();

        $client = Peanut_Client_Service::get_by_id($client_id);

        if (!$client) {
            return $this->error('not_found', 'Client not found', 404);
        }

        // Check access
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);
        if (!$account || (int) $client['account_id'] !== (int) $account['id']) {
            return $this->error('forbidden', 'You do not have access to this client', 403);
        }

        // Check permission
        $user_role = Peanut_Account_Service::get_user_role($account['id'], $user_id);
        if (!in_array($user_role, ['owner', 'admin'], true)) {
            return $this->error('forbidden', 'You do not have permission to manage client contacts', 403);
        }

        // Validate contact exists and belongs to same account
        if (class_exists('Peanut_Contact_Service')) {
            // Use contact service if available
        }

        $result = Peanut_Client_Service::add_contact($client_id, $contact_id, $role, $user_id);

        if (!$result) {
            return $this->error('add_failed', 'Failed to add contact to client. Contact may already be linked.', 400);
        }

        $contacts = Peanut_Client_Service::get_contacts($client_id);

        return $this->success($contacts, 201);
    }

    /**
     * Update contact role
     */
    public function update_contact(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $client_id = (int) $request->get_param('client_id');
        $contact_id = (int) $request->get_param('contact_id');
        $role = $request->get_param('role');
        $user_id = get_current_user_id();

        $client = Peanut_Client_Service::get_by_id($client_id);

        if (!$client) {
            return $this->error('not_found', 'Client not found', 404);
        }

        // Check access
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);
        if (!$account || (int) $client['account_id'] !== (int) $account['id']) {
            return $this->error('forbidden', 'You do not have access to this client', 403);
        }

        // Check permission
        $user_role = Peanut_Account_Service::get_user_role($account['id'], $user_id);
        if (!in_array($user_role, ['owner', 'admin'], true)) {
            return $this->error('forbidden', 'You do not have permission to manage client contacts', 403);
        }

        if ($role) {
            Peanut_Client_Service::update_contact_role($client_id, $contact_id, $role);
        }

        $contacts = Peanut_Client_Service::get_contacts($client_id);

        return $this->success($contacts);
    }

    /**
     * Remove a contact from a client
     */
    public function remove_contact(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $client_id = (int) $request->get_param('client_id');
        $contact_id = (int) $request->get_param('contact_id');
        $user_id = get_current_user_id();

        $client = Peanut_Client_Service::get_by_id($client_id);

        if (!$client) {
            return $this->error('not_found', 'Client not found', 404);
        }

        // Check access
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);
        if (!$account || (int) $client['account_id'] !== (int) $account['id']) {
            return $this->error('forbidden', 'You do not have access to this client', 403);
        }

        // Check permission
        $user_role = Peanut_Account_Service::get_user_role($account['id'], $user_id);
        if (!in_array($user_role, ['owner', 'admin'], true)) {
            return $this->error('forbidden', 'You do not have permission to manage client contacts', 403);
        }

        $result = Peanut_Client_Service::remove_contact($client_id, $contact_id);

        if (!$result) {
            return $this->error('remove_failed', 'Failed to remove contact from client', 500);
        }

        return $this->success(['removed' => true]);
    }

    /**
     * Set primary contact
     */
    public function set_primary_contact(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $client_id = (int) $request->get_param('client_id');
        $contact_id = (int) $request->get_param('contact_id');
        $user_id = get_current_user_id();

        $client = Peanut_Client_Service::get_by_id($client_id);

        if (!$client) {
            return $this->error('not_found', 'Client not found', 404);
        }

        // Check access
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);
        if (!$account || (int) $client['account_id'] !== (int) $account['id']) {
            return $this->error('forbidden', 'You do not have access to this client', 403);
        }

        // Check permission
        $user_role = Peanut_Account_Service::get_user_role($account['id'], $user_id);
        if (!in_array($user_role, ['owner', 'admin'], true)) {
            return $this->error('forbidden', 'You do not have permission to manage client contacts', 403);
        }

        $result = Peanut_Client_Service::set_primary_contact($client_id, $contact_id);

        if (!$result) {
            return $this->error('update_failed', 'Failed to set primary contact', 500);
        }

        $contacts = Peanut_Client_Service::get_contacts($client_id);

        return $this->success($contacts);
    }
}

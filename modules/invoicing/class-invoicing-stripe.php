<?php
/**
 * Stripe Integration
 *
 * Handles all Stripe API communication for invoicing.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Invoicing_Stripe {

    /**
     * Stripe API base URL
     */
    private const API_BASE = 'https://api.stripe.com/v1';

    /**
     * API secret key
     */
    private string $secret_key;

    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option('peanut_settings', []);
        $this->secret_key = $settings['stripe_secret_key'] ?? '';
    }

    /**
     * Check if Stripe is configured
     */
    public function is_configured(): bool {
        return !empty($this->secret_key);
    }

    /**
     * Make API request
     */
    private function request(string $endpoint, string $method = 'GET', array $data = []): array {
        if (!$this->is_configured()) {
            return ['error' => 'Stripe API key not configured'];
        }

        $url = self::API_BASE . $endpoint;

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'timeout' => 30,
        ];

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = $this->build_query($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['error'])) {
            return ['error' => $result['error']['message'] ?? 'Unknown error'];
        }

        return $result;
    }

    /**
     * Build query string for nested arrays (Stripe format)
     */
    private function build_query(array $data, string $prefix = ''): string {
        $result = [];

        foreach ($data as $key => $value) {
            $new_key = $prefix ? "{$prefix}[{$key}]" : $key;

            if (is_array($value)) {
                $result[] = $this->build_query($value, $new_key);
            } else {
                $result[] = urlencode($new_key) . '=' . urlencode($value);
            }
        }

        return implode('&', $result);
    }

    /**
     * Create or get customer
     */
    public function get_or_create_customer(string $email, string $name, array $metadata = []): array {
        // Search for existing customer
        $search = $this->request('/customers/search?query=' . urlencode("email:'{$email}'"));

        if (!isset($search['error']) && !empty($search['data'])) {
            return $search['data'][0];
        }

        // Create new customer
        return $this->request('/customers', 'POST', [
            'email' => $email,
            'name' => $name,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create invoice
     */
    public function create_invoice(array $data): array {
        // First, get or create customer
        $customer = $this->get_or_create_customer(
            $data['client_email'],
            $data['client_name'],
            ['company' => $data['client_company'] ?? '']
        );

        if (isset($customer['error'])) {
            return $customer;
        }

        // Create the invoice
        $invoice_data = [
            'customer' => $customer['id'],
            'collection_method' => 'send_invoice',
            'days_until_due' => $data['days_until_due'] ?? 30,
            'auto_advance' => false, // Don't auto-finalize
        ];

        if (!empty($data['description'])) {
            $invoice_data['description'] = $data['description'];
        }

        if (!empty($data['footer'])) {
            $invoice_data['footer'] = $data['footer'];
        }

        if (!empty($data['metadata'])) {
            $invoice_data['metadata'] = $data['metadata'];
        }

        $invoice = $this->request('/invoices', 'POST', $invoice_data);

        if (isset($invoice['error'])) {
            return $invoice;
        }

        // Add line items
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $item_result = $this->add_invoice_item($invoice['id'], $customer['id'], $item);
                if (isset($item_result['error'])) {
                    // Delete the invoice if items fail
                    $this->delete_invoice($invoice['id']);
                    return $item_result;
                }
            }
        }

        // Refresh invoice to get updated totals
        return $this->get_invoice($invoice['id']);
    }

    /**
     * Add item to invoice
     */
    public function add_invoice_item(string $invoice_id, string $customer_id, array $item): array {
        return $this->request('/invoiceitems', 'POST', [
            'invoice' => $invoice_id,
            'customer' => $customer_id,
            'description' => $item['description'],
            'quantity' => $item['quantity'] ?? 1,
            'unit_amount' => (int) (($item['unit_price'] ?? 0) * 100), // Convert to cents
            'currency' => $item['currency'] ?? 'usd',
        ]);
    }

    /**
     * Get invoice
     */
    public function get_invoice(string $invoice_id): array {
        return $this->request('/invoices/' . $invoice_id);
    }

    /**
     * Finalize invoice (make it ready to send)
     */
    public function finalize_invoice(string $invoice_id): array {
        return $this->request('/invoices/' . $invoice_id . '/finalize', 'POST');
    }

    /**
     * Send invoice to customer
     */
    public function send_invoice(string $invoice_id): array {
        return $this->request('/invoices/' . $invoice_id . '/send', 'POST');
    }

    /**
     * Void invoice
     */
    public function void_invoice(string $invoice_id): array {
        return $this->request('/invoices/' . $invoice_id . '/void', 'POST');
    }

    /**
     * Delete draft invoice
     */
    public function delete_invoice(string $invoice_id): array {
        return $this->request('/invoices/' . $invoice_id, 'DELETE');
    }

    /**
     * Mark invoice as paid (for manual payments)
     */
    public function mark_as_paid(string $invoice_id): array {
        return $this->request('/invoices/' . $invoice_id . '/pay', 'POST', [
            'paid_out_of_band' => true,
        ]);
    }

    /**
     * Get invoice PDF URL
     */
    public function get_invoice_pdf(string $invoice_id): ?string {
        $invoice = $this->get_invoice($invoice_id);
        return $invoice['invoice_pdf'] ?? null;
    }

    /**
     * Get payment link / hosted invoice URL
     */
    public function get_payment_url(string $invoice_id): ?string {
        $invoice = $this->get_invoice($invoice_id);
        return $invoice['hosted_invoice_url'] ?? null;
    }

    /**
     * List invoices
     */
    public function list_invoices(array $params = []): array {
        $query = http_build_query($params);
        return $this->request('/invoices' . ($query ? '?' . $query : ''));
    }

    /**
     * Construct webhook event from payload
     */
    public function construct_webhook_event(string $payload, string $sig_header, string $secret): object {
        // Verify signature
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $sig_header) as $item) {
            $parts = explode('=', $item, 2);
            if (count($parts) === 2) {
                if ($parts[0] === 't') {
                    $timestamp = $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatures[] = $parts[1];
                }
            }
        }

        if (!$timestamp || empty($signatures)) {
            throw new \Exception('Invalid signature header');
        }

        // Check timestamp (within 5 minutes)
        if (abs(time() - (int) $timestamp) > 300) {
            throw new \Exception('Webhook timestamp too old');
        }

        // Compute expected signature
        $signed_payload = $timestamp . '.' . $payload;
        $expected_sig = hash_hmac('sha256', $signed_payload, $secret);

        // Compare signatures
        $valid = false;
        foreach ($signatures as $sig) {
            if (hash_equals($expected_sig, $sig)) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            throw new \Exception('Invalid signature');
        }

        // Parse and return event
        $event = json_decode($payload);
        if (!$event) {
            throw new \Exception('Invalid JSON payload');
        }

        return $event;
    }

    /**
     * Create a Stripe Customer Portal session
     */
    public function create_portal_session(string $customer_id, string $return_url): array {
        return $this->request('/billing_portal/sessions', 'POST', [
            'customer' => $customer_id,
            'return_url' => $return_url,
        ]);
    }

    /**
     * Update invoice
     */
    public function update_invoice(string $invoice_id, array $data): array {
        return $this->request('/invoices/' . $invoice_id, 'POST', $data);
    }

    /**
     * Get customer
     */
    public function get_customer(string $customer_id): array {
        return $this->request('/customers/' . $customer_id);
    }

    /**
     * Update customer
     */
    public function update_customer(string $customer_id, array $data): array {
        return $this->request('/customers/' . $customer_id, 'POST', $data);
    }

    /**
     * Test connection with Stripe
     */
    public function test_connection(): array {
        $result = $this->request('/balance');

        if (isset($result['error'])) {
            return ['success' => false, 'message' => $result['error']];
        }

        return ['success' => true, 'message' => 'Connected to Stripe'];
    }
}

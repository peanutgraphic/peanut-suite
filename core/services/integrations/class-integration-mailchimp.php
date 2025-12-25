<?php
/**
 * Mailchimp Integration
 *
 * Sync contacts with Mailchimp lists.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Integration_Mailchimp {

    /**
     * Settings
     */
    private array $settings;

    /**
     * API base URL
     */
    private string $api_base = '';

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('peanut_settings', []);
        $this->set_api_base();
    }

    /**
     * Set API base from API key
     */
    private function set_api_base(): void {
        $api_key = $this->settings['mailchimp_api_key'] ?? '';
        if (!empty($api_key) && strpos($api_key, '-') !== false) {
            $dc = explode('-', $api_key)[1];
            $this->api_base = "https://{$dc}.api.mailchimp.com/3.0";
        }
    }

    /**
     * Check if configured
     */
    public function is_configured(): bool {
        return !empty($this->settings['mailchimp_api_key'])
            && !empty($this->settings['mailchimp_list_id'])
            && !empty($this->api_base);
    }

    /**
     * Make API request
     */
    private function request(string $method, string $endpoint, array $data = []): array {
        if (empty($this->api_base)) {
            return ['error' => 'Invalid API key format'];
        }

        $url = $this->api_base . $endpoint;
        $api_key = $this->settings['mailchimp_api_key'];

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('peanut:' . $api_key),
                'Content-Type' => 'application/json',
            ],
            'timeout' => 15,
        ];

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $this->log_error('Request failed: ' . $response->get_error_message());
            return ['error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true) ?: [];

        if ($code >= 400) {
            $error = $result['detail'] ?? $result['title'] ?? "HTTP {$code}";
            $this->log_error("API error: {$error}");
            return ['error' => $error, 'code' => $code];
        }

        return $result;
    }

    /**
     * Add subscriber to list
     */
    public function add_subscriber(array $contact): array {
        if (!$this->is_configured()) {
            return ['error' => 'Mailchimp not configured'];
        }

        $list_id = $this->settings['mailchimp_list_id'];
        $email = $contact['email'] ?? '';

        if (empty($email) || !is_email($email)) {
            return ['error' => 'Valid email required'];
        }

        // Generate subscriber hash
        $subscriber_hash = md5(strtolower($email));

        // Build merge fields
        $merge_fields = [];
        if (!empty($contact['first_name'])) {
            $merge_fields['FNAME'] = $contact['first_name'];
        }
        if (!empty($contact['last_name'])) {
            $merge_fields['LNAME'] = $contact['last_name'];
        }
        if (!empty($contact['name'])) {
            $name_parts = explode(' ', $contact['name'], 2);
            if (empty($merge_fields['FNAME'])) {
                $merge_fields['FNAME'] = $name_parts[0];
            }
            if (empty($merge_fields['LNAME']) && isset($name_parts[1])) {
                $merge_fields['LNAME'] = $name_parts[1];
            }
        }
        if (!empty($contact['company'])) {
            $merge_fields['COMPANY'] = $contact['company'];
        }
        if (!empty($contact['phone'])) {
            $merge_fields['PHONE'] = $contact['phone'];
        }

        // Determine status based on settings
        $double_optin = $this->settings['mailchimp_double_optin'] ?? false;
        $status = $double_optin ? 'pending' : 'subscribed';

        // Build subscriber data
        $subscriber_data = [
            'email_address' => $email,
            'status_if_new' => $status,
            'merge_fields' => $merge_fields,
        ];

        // Add tags if configured
        $default_tags = $this->settings['mailchimp_tags'] ?? '';
        if (!empty($default_tags)) {
            $tags = array_map('trim', explode(',', $default_tags));
            $subscriber_data['tags'] = $tags;
        }

        // Add source tag
        if (!empty($contact['source'])) {
            $subscriber_data['tags'][] = 'peanut-' . sanitize_title($contact['source']);
        }

        // Use PUT for upsert (add or update)
        $result = $this->request(
            'PUT',
            "/lists/{$list_id}/members/{$subscriber_hash}",
            $subscriber_data
        );

        if (isset($result['error'])) {
            return $result;
        }

        return [
            'success' => true,
            'id' => $result['id'] ?? null,
            'status' => $result['status'] ?? null,
        ];
    }

    /**
     * Update subscriber
     */
    public function update_subscriber(array $contact): array {
        // Same as add - PUT handles upsert
        return $this->add_subscriber($contact);
    }

    /**
     * Remove subscriber from list
     */
    public function remove_subscriber(string $email): array {
        if (!$this->is_configured()) {
            return ['error' => 'Mailchimp not configured'];
        }

        $list_id = $this->settings['mailchimp_list_id'];
        $subscriber_hash = md5(strtolower($email));

        return $this->request('DELETE', "/lists/{$list_id}/members/{$subscriber_hash}");
    }

    /**
     * Get lists (audiences)
     */
    public function get_lists(): array {
        $result = $this->request('GET', '/lists?count=100');

        if (isset($result['error'])) {
            return $result;
        }

        $lists = [];
        foreach ($result['lists'] ?? [] as $list) {
            $lists[] = [
                'id' => $list['id'],
                'name' => $list['name'],
                'member_count' => $list['stats']['member_count'] ?? 0,
            ];
        }

        return ['lists' => $lists];
    }

    /**
     * Get subscriber info
     */
    public function get_subscriber(string $email): array {
        if (!$this->is_configured()) {
            return ['error' => 'Mailchimp not configured'];
        }

        $list_id = $this->settings['mailchimp_list_id'];
        $subscriber_hash = md5(strtolower($email));

        return $this->request('GET', "/lists/{$list_id}/members/{$subscriber_hash}");
    }

    /**
     * Test connection
     */
    public function test_connection(): array {
        $api_key = $this->settings['mailchimp_api_key'] ?? '';

        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'API key is required',
            ];
        }

        if (strpos($api_key, '-') === false) {
            return [
                'success' => false,
                'message' => 'Invalid API key format. Should end with -usX (e.g., -us19)',
            ];
        }

        // Test by fetching account info
        $result = $this->request('GET', '/');

        if (isset($result['error'])) {
            return [
                'success' => false,
                'message' => $result['error'],
            ];
        }

        $account_name = $result['account_name'] ?? 'Unknown';

        // Verify list access if list ID is set
        $list_id = $this->settings['mailchimp_list_id'] ?? '';
        if (!empty($list_id)) {
            $list_result = $this->request('GET', "/lists/{$list_id}");
            if (isset($list_result['error'])) {
                return [
                    'success' => false,
                    'message' => "Connected to account '{$account_name}', but list ID is invalid: " . $list_result['error'],
                ];
            }
            $list_name = $list_result['name'] ?? 'Unknown';
            return [
                'success' => true,
                'message' => "Connected to '{$account_name}' - List: {$list_name}",
            ];
        }

        return [
            'success' => true,
            'message' => "Connected to account: {$account_name}",
        ];
    }

    /**
     * Log error
     */
    private function log_error(string $message): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Peanut Mailchimp] ' . $message);
        }
    }
}

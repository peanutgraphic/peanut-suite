<?php
/**
 * ConvertKit Integration
 *
 * Sync contacts with ConvertKit.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Integration_ConvertKit {

    /**
     * API base URL
     */
    private const API_BASE = 'https://api.convertkit.com/v3';

    /**
     * Settings
     */
    private array $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('peanut_settings', []);
    }

    /**
     * Check if configured
     */
    public function is_configured(): bool {
        return !empty($this->settings['convertkit_api_key'])
            && !empty($this->settings['convertkit_form_id']);
    }

    /**
     * Get API key
     */
    private function get_api_key(): string {
        return $this->settings['convertkit_api_key'] ?? '';
    }

    /**
     * Get API secret (for some endpoints)
     */
    private function get_api_secret(): string {
        return $this->settings['convertkit_api_secret'] ?? '';
    }

    /**
     * Make API request
     */
    private function request(string $method, string $endpoint, array $data = []): array {
        $url = self::API_BASE . $endpoint;

        // Add API key/secret to data
        if ($method === 'GET') {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'api_key=' . $this->get_api_key();
            if (!empty($this->get_api_secret())) {
                $url .= '&api_secret=' . $this->get_api_secret();
            }
        } else {
            $data['api_key'] = $this->get_api_key();
            if (!empty($this->get_api_secret())) {
                $data['api_secret'] = $this->get_api_secret();
            }
        }

        $args = [
            'method' => $method,
            'headers' => [
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
            $error = $result['error'] ?? $result['message'] ?? "HTTP {$code}";
            $this->log_error("API error: {$error}");
            return ['error' => $error, 'code' => $code];
        }

        return $result;
    }

    /**
     * Add subscriber via form
     */
    public function add_subscriber(array $contact): array {
        if (!$this->is_configured()) {
            return ['error' => 'ConvertKit not configured'];
        }

        $form_id = $this->settings['convertkit_form_id'];
        $email = $contact['email'] ?? '';

        if (empty($email) || !is_email($email)) {
            return ['error' => 'Valid email required'];
        }

        // Build subscriber data
        $subscriber_data = [
            'email' => $email,
        ];

        // Add first name if available
        if (!empty($contact['first_name'])) {
            $subscriber_data['first_name'] = $contact['first_name'];
        } elseif (!empty($contact['name'])) {
            $name_parts = explode(' ', $contact['name'], 2);
            $subscriber_data['first_name'] = $name_parts[0];
        }

        // Add custom fields
        $fields = [];
        if (!empty($contact['last_name'])) {
            $fields['last_name'] = $contact['last_name'];
        } elseif (!empty($contact['name'])) {
            $name_parts = explode(' ', $contact['name'], 2);
            if (isset($name_parts[1])) {
                $fields['last_name'] = $name_parts[1];
            }
        }
        if (!empty($contact['company'])) {
            $fields['company'] = $contact['company'];
        }
        if (!empty($contact['phone'])) {
            $fields['phone'] = $contact['phone'];
        }
        if (!empty($contact['source'])) {
            $fields['source'] = $contact['source'];
        }

        if (!empty($fields)) {
            $subscriber_data['fields'] = $fields;
        }

        // Add tags if configured
        $default_tags = $this->settings['convertkit_tags'] ?? '';
        if (!empty($default_tags)) {
            $tag_ids = array_map('trim', explode(',', $default_tags));
            // Tags are added separately after subscription
        }

        // Subscribe via form
        $result = $this->request('POST', "/forms/{$form_id}/subscribe", $subscriber_data);

        if (isset($result['error'])) {
            return $result;
        }

        $subscriber_id = $result['subscription']['subscriber']['id'] ?? null;

        // Add tags if we have a subscriber ID and tags
        if ($subscriber_id && !empty($default_tags)) {
            $tag_ids = array_map('trim', explode(',', $default_tags));
            foreach ($tag_ids as $tag_id) {
                if (is_numeric($tag_id)) {
                    $this->add_tag_to_subscriber((int) $subscriber_id, (int) $tag_id);
                }
            }
        }

        return [
            'success' => true,
            'subscriber_id' => $subscriber_id,
        ];
    }

    /**
     * Update subscriber
     */
    public function update_subscriber(array $contact): array {
        if (!$this->is_configured()) {
            return ['error' => 'ConvertKit not configured'];
        }

        $email = $contact['email'] ?? '';

        if (empty($email) || !is_email($email)) {
            return ['error' => 'Valid email required'];
        }

        // Get subscriber by email
        $subscriber = $this->get_subscriber_by_email($email);

        if (isset($subscriber['error']) || empty($subscriber['subscribers'])) {
            // Subscriber doesn't exist, add them
            return $this->add_subscriber($contact);
        }

        $subscriber_id = $subscriber['subscribers'][0]['id'];

        // Build update data
        $update_data = [];

        if (!empty($contact['first_name'])) {
            $update_data['first_name'] = $contact['first_name'];
        }

        $fields = [];
        if (!empty($contact['last_name'])) {
            $fields['last_name'] = $contact['last_name'];
        }
        if (!empty($contact['company'])) {
            $fields['company'] = $contact['company'];
        }
        if (!empty($contact['phone'])) {
            $fields['phone'] = $contact['phone'];
        }

        if (!empty($fields)) {
            $update_data['fields'] = $fields;
        }

        if (empty($update_data)) {
            return ['success' => true, 'message' => 'No updates needed'];
        }

        $result = $this->request('PUT', "/subscribers/{$subscriber_id}", $update_data);

        if (isset($result['error'])) {
            return $result;
        }

        return ['success' => true, 'subscriber_id' => $subscriber_id];
    }

    /**
     * Get subscriber by email
     */
    public function get_subscriber_by_email(string $email): array {
        return $this->request('GET', '/subscribers?email_address=' . urlencode($email));
    }

    /**
     * Add tag to subscriber
     */
    public function add_tag_to_subscriber(int $subscriber_id, int $tag_id): array {
        return $this->request('POST', "/tags/{$tag_id}/subscribe", [
            'id' => $subscriber_id,
        ]);
    }

    /**
     * Get forms
     */
    public function get_forms(): array {
        $result = $this->request('GET', '/forms');

        if (isset($result['error'])) {
            return $result;
        }

        $forms = [];
        foreach ($result['forms'] ?? [] as $form) {
            $forms[] = [
                'id' => $form['id'],
                'name' => $form['name'],
                'type' => $form['type'] ?? 'embed',
            ];
        }

        return ['forms' => $forms];
    }

    /**
     * Get tags
     */
    public function get_tags(): array {
        $result = $this->request('GET', '/tags');

        if (isset($result['error'])) {
            return $result;
        }

        $tags = [];
        foreach ($result['tags'] ?? [] as $tag) {
            $tags[] = [
                'id' => $tag['id'],
                'name' => $tag['name'],
            ];
        }

        return ['tags' => $tags];
    }

    /**
     * Unsubscribe email
     */
    public function unsubscribe(string $email): array {
        return $this->request('PUT', '/unsubscribe', [
            'email' => $email,
        ]);
    }

    /**
     * Test connection
     */
    public function test_connection(): array {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'API key is required',
            ];
        }

        // Test by fetching account info
        $result = $this->request('GET', '/account');

        if (isset($result['error'])) {
            return [
                'success' => false,
                'message' => $result['error'],
            ];
        }

        $account_name = $result['name'] ?? 'Unknown';

        // Check form access if form ID is set
        $form_id = $this->settings['convertkit_form_id'] ?? '';
        if (!empty($form_id)) {
            $forms = $this->get_forms();
            if (!isset($forms['error'])) {
                $form_exists = false;
                $form_name = '';
                foreach ($forms['forms'] ?? [] as $form) {
                    if ((string) $form['id'] === (string) $form_id) {
                        $form_exists = true;
                        $form_name = $form['name'];
                        break;
                    }
                }
                if (!$form_exists) {
                    return [
                        'success' => false,
                        'message' => "Connected to '{$account_name}', but Form ID not found",
                    ];
                }
                return [
                    'success' => true,
                    'message' => "Connected to '{$account_name}' - Form: {$form_name}",
                ];
            }
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
            error_log('[Peanut ConvertKit] ' . $message);
        }
    }
}

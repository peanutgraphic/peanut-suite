<?php
/**
 * Google Analytics 4 Integration
 *
 * Send events to GA4 via Measurement Protocol.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Integration_GA4 {

    /**
     * GA4 Measurement Protocol endpoint
     */
    private const ENDPOINT = 'https://www.google-analytics.com/mp/collect';

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
        return !empty($this->settings['ga4_measurement_id'])
            && !empty($this->settings['ga4_api_secret']);
    }

    /**
     * Get Measurement ID
     */
    public function get_measurement_id(): string {
        return $this->settings['ga4_measurement_id'] ?? '';
    }

    /**
     * Send event to GA4
     */
    public function send_event(string $event_name, array $params = []): array {
        if (!$this->is_configured()) {
            return ['error' => 'GA4 not configured'];
        }

        $measurement_id = $this->settings['ga4_measurement_id'];
        $api_secret = $this->settings['ga4_api_secret'];

        // Build the URL
        $url = self::ENDPOINT . '?' . http_build_query([
            'measurement_id' => $measurement_id,
            'api_secret' => $api_secret,
        ]);

        // Generate or retrieve client ID
        $client_id = $this->get_client_id();

        // Build payload
        $payload = [
            'client_id' => $client_id,
            'events' => [
                [
                    'name' => $this->sanitize_event_name($event_name),
                    'params' => $this->prepare_params($params),
                ],
            ],
        ];

        // Add user_id if available
        if (!empty($params['user_id'])) {
            $payload['user_id'] = (string) $params['user_id'];
        }

        // Send request
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($payload),
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            $this->log_error('GA4 request failed: ' . $response->get_error_message());
            return ['error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);

        // GA4 returns 204 No Content on success
        if ($code === 204 || $code === 200) {
            return ['success' => true];
        }

        $body = wp_remote_retrieve_body($response);
        $this->log_error("GA4 request failed with code {$code}: {$body}");

        return ['error' => "Request failed with code {$code}"];
    }

    /**
     * Send page view
     */
    public function send_pageview(string $page_title, string $page_location): array {
        return $this->send_event('page_view', [
            'page_title' => $page_title,
            'page_location' => $page_location,
        ]);
    }

    /**
     * Send conversion event
     */
    public function send_conversion(string $conversion_name, float $value = 0, string $currency = 'USD'): array {
        $params = [
            'currency' => $currency,
        ];

        if ($value > 0) {
            $params['value'] = $value;
        }

        return $this->send_event($conversion_name, $params);
    }

    /**
     * Get or generate client ID
     */
    private function get_client_id(): string {
        // Try to get from cookie if in web context
        if (isset($_COOKIE['_ga'])) {
            // GA cookie format: GA1.1.XXXXXXXXXX.XXXXXXXXXX
            $parts = explode('.', $_COOKIE['_ga']);
            if (count($parts) >= 4) {
                return $parts[2] . '.' . $parts[3];
            }
        }

        // Generate a random client ID for server-side events
        return sprintf('%d.%d', mt_rand(1000000000, 9999999999), time());
    }

    /**
     * Sanitize event name for GA4
     */
    private function sanitize_event_name(string $name): string {
        // GA4 event names: letters, numbers, underscores only, max 40 chars
        $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
        $name = substr($name, 0, 40);
        return $name;
    }

    /**
     * Prepare params for GA4
     */
    private function prepare_params(array $params): array {
        $prepared = [];

        foreach ($params as $key => $value) {
            // Sanitize key
            $key = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
            $key = substr($key, 0, 40);

            // Skip reserved params
            if (in_array($key, ['user_id', 'timestamp_micros'])) {
                continue;
            }

            // Ensure value is string or number
            if (is_array($value)) {
                $value = wp_json_encode($value);
            }

            $prepared[$key] = $value;
        }

        // Add timestamp
        $prepared['engagement_time_msec'] = 100;

        return $prepared;
    }

    /**
     * Test connection
     */
    public function test_connection(): array {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'message' => 'GA4 Measurement ID and API Secret are required',
            ];
        }

        // Send a test event
        $result = $this->send_event('peanut_connection_test', [
            'test' => true,
            'timestamp' => current_time('mysql'),
        ]);

        if (isset($result['error'])) {
            return [
                'success' => false,
                'message' => $result['error'],
            ];
        }

        return [
            'success' => true,
            'message' => 'Connection successful! Test event sent to GA4.',
        ];
    }

    /**
     * Log error
     */
    private function log_error(string $message): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Peanut GA4] ' . $message);
        }
    }
}

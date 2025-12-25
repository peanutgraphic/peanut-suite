<?php
/**
 * Webhooks Signature Verification
 *
 * Handles signature verification for incoming webhooks.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Webhooks_Signature {

    /**
     * Verify webhook signature
     *
     * @param string $payload Raw request body
     * @param string $signature Signature from header
     * @param string $source Webhook source identifier
     * @return bool True if signature is valid
     */
    public static function verify(string $payload, string $signature, string $source): bool {
        // Get secret for this source
        $secret = self::get_secret($source);

        if (empty($secret)) {
            // If no secret configured, allow webhook (for development)
            // In production, you may want to return false
            return true;
        }

        // Different sources may use different signature formats
        return match ($source) {
            'formflow-lite', 'formflow' => self::verify_formflow($payload, $signature, $secret),
            default => self::verify_hmac($payload, $signature, $secret),
        };
    }

    /**
     * Verify FormFlow signature
     *
     * FormFlow uses: sha256=HMAC-SHA256(payload, secret)
     */
    private static function verify_formflow(string $payload, string $signature, string $secret): bool {
        // FormFlow sends signature as "sha256=hash"
        if (str_starts_with($signature, 'sha256=')) {
            $signature = substr($signature, 7);
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Generic HMAC-SHA256 verification
     */
    private static function verify_hmac(string $payload, string $signature, string $secret): bool {
        // Strip any prefix like "sha256="
        if (str_contains($signature, '=')) {
            $parts = explode('=', $signature, 2);
            $signature = $parts[1] ?? $signature;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Get webhook secret for a source
     */
    public static function get_secret(string $source): string {
        $secrets = get_option('peanut_webhook_secrets', []);

        return $secrets[$source] ?? '';
    }

    /**
     * Set webhook secret for a source
     */
    public static function set_secret(string $source, string $secret): bool {
        $secrets = get_option('peanut_webhook_secrets', []);
        $secrets[$source] = $secret;

        return update_option('peanut_webhook_secrets', $secrets);
    }

    /**
     * Delete webhook secret for a source
     */
    public static function delete_secret(string $source): bool {
        $secrets = get_option('peanut_webhook_secrets', []);
        unset($secrets[$source]);

        return update_option('peanut_webhook_secrets', $secrets);
    }

    /**
     * Generate a new webhook secret
     */
    public static function generate_secret(): string {
        return wp_generate_password(32, false, false);
    }

    /**
     * Get signature from request headers
     */
    public static function get_signature_from_headers(): ?string {
        // Check various header formats
        $headers = [
            'HTTP_X_WEBHOOK_SIGNATURE',
            'HTTP_X_SIGNATURE',
            'HTTP_X_HUB_SIGNATURE_256',
            'HTTP_X_HUB_SIGNATURE',
            'HTTP_SIGNATURE',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }

        // Check for WordPress REST API header format
        $signature = isset($_SERVER['HTTP_X_PEANUT_SIGNATURE'])
            ? $_SERVER['HTTP_X_PEANUT_SIGNATURE']
            : null;

        return $signature;
    }

    /**
     * Get source from request headers or body
     */
    public static function get_source_from_request(array $payload): string {
        // Check header first
        if (!empty($_SERVER['HTTP_X_WEBHOOK_SOURCE'])) {
            return sanitize_text_field($_SERVER['HTTP_X_WEBHOOK_SOURCE']);
        }

        // Check payload
        if (!empty($payload['source'])) {
            return sanitize_text_field($payload['source']);
        }

        // Default to unknown
        return 'unknown';
    }
}

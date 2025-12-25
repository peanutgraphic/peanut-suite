<?php
/**
 * Webhooks Processor
 *
 * Processes incoming webhooks and dispatches events.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Webhooks_Processor {

    /**
     * Maximum retries for failed webhooks
     */
    private const MAX_RETRIES = 3;

    /**
     * Process a single webhook
     *
     * @param int $webhook_id Webhook ID to process
     * @return bool True if processed successfully
     */
    public static function process(int $webhook_id): bool {
        $webhook = Webhooks_Database::get($webhook_id);

        if (!$webhook) {
            return false;
        }

        // Mark as processing
        Webhooks_Database::update_status($webhook_id, 'processing');

        try {
            // Decode payload
            $payload = json_decode($webhook['payload'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON payload: ' . json_last_error_msg());
            }

            $source = $webhook['source'];
            $event = $webhook['event'];

            // Fire generic webhook received action
            do_action('peanut_webhook_received', $source, $event, $payload, $webhook_id);

            // Fire source-specific action
            do_action("peanut_webhook_{$source}", $event, $payload, $webhook_id);

            // Fire source and event specific action
            $safe_event = str_replace('.', '_', $event);
            do_action("peanut_webhook_{$source}_{$safe_event}", $payload, $webhook_id);

            // Process based on source
            self::dispatch($source, $event, $payload, $webhook_id);

            // Mark as processed
            Webhooks_Database::update_status($webhook_id, 'processed');

            // Fire success action
            do_action('peanut_webhook_processed', $webhook_id, 'success');

            return true;

        } catch (Exception $e) {
            // Log error and mark as failed
            Webhooks_Database::update_status($webhook_id, 'failed', $e->getMessage());
            Webhooks_Database::increment_retry($webhook_id);

            // Fire failure action
            do_action('peanut_webhook_processed', $webhook_id, 'failed', $e->getMessage());

            return false;
        }
    }

    /**
     * Dispatch webhook to appropriate handler
     */
    private static function dispatch(string $source, string $event, array $payload, int $webhook_id): void {
        // FormFlow event handlers
        if ($source === 'formflow-lite' || $source === 'formflow') {
            self::handle_formflow_event($event, $payload, $webhook_id);
        }

        // Allow custom handlers via filter
        apply_filters('peanut_webhook_dispatch', null, $source, $event, $payload, $webhook_id);
    }

    /**
     * Handle FormFlow events
     */
    private static function handle_formflow_event(string $event, array $payload, int $webhook_id): void {
        switch ($event) {
            case 'form.viewed':
                self::handle_form_viewed($payload);
                break;

            case 'form.step_completed':
                self::handle_form_step_completed($payload);
                break;

            case 'enrollment.submitted':
                self::handle_enrollment_submitted($payload);
                break;

            case 'enrollment.completed':
                self::handle_enrollment_completed($payload);
                break;

            case 'enrollment.failed':
                self::handle_enrollment_failed($payload);
                break;

            case 'appointment.booked':
                self::handle_appointment_booked($payload);
                break;

            case 'appointment.skipped':
                self::handle_appointment_skipped($payload);
                break;
        }
    }

    /**
     * Handle form.viewed event
     */
    private static function handle_form_viewed(array $payload): void {
        // Extract visitor data
        $visitor_id = $payload['submission']['session_id'] ?? null;
        $attribution = $payload['attribution'] ?? [];

        if (!$visitor_id) {
            return;
        }

        // Fire action for Visitor Tracking module to handle
        do_action('peanut_form_viewed', [
            'visitor_id' => $visitor_id,
            'form_id' => $payload['instance']['id'] ?? null,
            'form_slug' => $payload['instance']['slug'] ?? null,
            'utm_source' => $attribution['utm_source'] ?? null,
            'utm_medium' => $attribution['utm_medium'] ?? null,
            'utm_campaign' => $attribution['utm_campaign'] ?? null,
            'utm_term' => $attribution['utm_term'] ?? null,
            'utm_content' => $attribution['utm_content'] ?? null,
            'referrer' => $attribution['referrer'] ?? null,
            'landing_page' => $attribution['landing_page'] ?? null,
            'timestamp' => $payload['timestamp'] ?? current_time('mysql'),
        ]);
    }

    /**
     * Handle form.step_completed event
     */
    private static function handle_form_step_completed(array $payload): void {
        $visitor_id = $payload['submission']['session_id'] ?? null;

        if (!$visitor_id) {
            return;
        }

        do_action('peanut_form_step_completed', [
            'visitor_id' => $visitor_id,
            'form_id' => $payload['instance']['id'] ?? null,
            'step' => $payload['step'] ?? null,
            'step_name' => $payload['step_name'] ?? null,
            'timestamp' => $payload['timestamp'] ?? current_time('mysql'),
        ]);
    }

    /**
     * Handle enrollment.submitted event
     */
    private static function handle_enrollment_submitted(array $payload): void {
        $visitor_id = $payload['submission']['session_id'] ?? null;
        $customer = $payload['customer'] ?? [];

        do_action('peanut_enrollment_submitted', [
            'visitor_id' => $visitor_id,
            'submission_id' => $payload['submission']['id'] ?? null,
            'form_id' => $payload['instance']['id'] ?? null,
            'email' => $customer['email'] ?? null,
            'zip' => $customer['zip'] ?? null,
            'state' => $customer['state'] ?? null,
            'device_type' => $payload['submission']['device_type'] ?? null,
            'timestamp' => $payload['timestamp'] ?? current_time('mysql'),
        ]);
    }

    /**
     * Handle enrollment.completed event
     */
    private static function handle_enrollment_completed(array $payload): void {
        $visitor_id = $payload['submission']['session_id'] ?? null;
        $customer = $payload['customer'] ?? [];
        $attribution = $payload['attribution'] ?? [];

        // This creates a conversion
        do_action('peanut_conversion', [
            'visitor_id' => $visitor_id,
            'conversion_type' => 'enrollment',
            'source' => 'formflow-lite',
            'source_id' => $payload['submission']['id'] ?? null,
            'confirmation_number' => $payload['submission']['confirmation_number'] ?? null,
            'customer_email' => $customer['email'] ?? null,
            'customer_name' => $customer['name'] ?? null,
            'form_id' => $payload['instance']['id'] ?? null,
            'form_slug' => $payload['instance']['slug'] ?? null,
            'utility' => $payload['instance']['utility'] ?? null,
            'device_type' => $payload['submission']['device_type'] ?? null,
            'zip' => $customer['zip'] ?? null,
            'state' => $customer['state'] ?? null,
            'attribution' => $attribution,
            'metadata' => $payload,
            'timestamp' => $payload['timestamp'] ?? current_time('mysql'),
        ]);

        // Also identify visitor with email
        if (!empty($customer['email'])) {
            do_action('peanut_visitor_identify', $visitor_id, $customer['email'], [
                'name' => $customer['name'] ?? null,
                'zip' => $customer['zip'] ?? null,
                'state' => $customer['state'] ?? null,
            ]);
        }
    }

    /**
     * Handle enrollment.failed event
     */
    private static function handle_enrollment_failed(array $payload): void {
        $visitor_id = $payload['submission']['session_id'] ?? null;

        do_action('peanut_enrollment_failed', [
            'visitor_id' => $visitor_id,
            'submission_id' => $payload['submission']['id'] ?? null,
            'form_id' => $payload['instance']['id'] ?? null,
            'error' => $payload['error'] ?? null,
            'timestamp' => $payload['timestamp'] ?? current_time('mysql'),
        ]);
    }

    /**
     * Handle appointment.booked event
     */
    private static function handle_appointment_booked(array $payload): void {
        $visitor_id = $payload['submission']['session_id'] ?? null;
        $scheduling = $payload['scheduling'] ?? [];

        do_action('peanut_appointment_booked', [
            'visitor_id' => $visitor_id,
            'submission_id' => $payload['submission']['id'] ?? null,
            'form_id' => $payload['instance']['id'] ?? null,
            'appointment_date' => $scheduling['appointment_date'] ?? null,
            'appointment_time' => $scheduling['appointment_time'] ?? null,
            'fsr_number' => $scheduling['fsr_number'] ?? null,
            'timestamp' => $payload['timestamp'] ?? current_time('mysql'),
        ]);
    }

    /**
     * Handle appointment.skipped event
     */
    private static function handle_appointment_skipped(array $payload): void {
        $visitor_id = $payload['submission']['session_id'] ?? null;

        do_action('peanut_appointment_skipped', [
            'visitor_id' => $visitor_id,
            'submission_id' => $payload['submission']['id'] ?? null,
            'form_id' => $payload['instance']['id'] ?? null,
            'timestamp' => $payload['timestamp'] ?? current_time('mysql'),
        ]);
    }

    /**
     * Process pending webhooks (called by cron)
     */
    public static function process_pending(): int {
        $pending = Webhooks_Database::get_pending(50);
        $processed = 0;

        foreach ($pending as $webhook) {
            if (self::process((int) $webhook['id'])) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Reprocess a specific webhook
     */
    public static function reprocess(int $webhook_id): bool {
        $webhook = Webhooks_Database::get($webhook_id);

        if (!$webhook) {
            return false;
        }

        // Reset status and retry count
        global $wpdb;
        $wpdb->update(
            Webhooks_Database::webhooks_table(),
            [
                'status' => 'pending',
                'retry_count' => 0,
                'error_message' => null,
                'processed_at' => null,
            ],
            ['id' => $webhook_id]
        );

        return self::process($webhook_id);
    }
}

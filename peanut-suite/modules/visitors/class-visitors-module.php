<?php
/**
 * Visitors Module
 *
 * @package PeanutSuite\Visitors
 */

namespace PeanutSuite\Visitors;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main visitors tracking module.
 */
class Visitors_Module {

    /**
     * Module instance.
     *
     * @var Visitors_Module|null
     */
    private static ?Visitors_Module $instance = null;

    /**
     * REST controller.
     *
     * @var Visitors_Controller|null
     */
    private ?Visitors_Controller $controller = null;

    /**
     * Snippet handler.
     *
     * @var Visitors_Snippet|null
     */
    private ?Visitors_Snippet $snippet = null;

    /**
     * Get module instance.
     *
     * @return Visitors_Module
     */
    public static function instance(): Visitors_Module {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->load_dependencies();
    }

    /**
     * Load module dependencies.
     */
    private function load_dependencies(): void {
        $base_path = PEANUT_PATH . 'modules/visitors/';

        require_once $base_path . 'class-visitors-database.php';
        require_once $base_path . 'class-visitors-tracker.php';
        require_once $base_path . 'class-visitors-snippet.php';
        require_once $base_path . 'api/class-visitors-controller.php';
    }

    /**
     * Initialize the module.
     */
    public function init(): void {
        // Initialize snippet handler
        $this->snippet = new Visitors_Snippet();
        $this->snippet->init();

        // Register REST API routes
        add_action('rest_api_init', [$this, 'register_api_routes']);

        // Register cron jobs
        add_action('peanut_visitors_cleanup', [$this, 'run_cleanup']);

        // Schedule cleanup if not already scheduled
        if (!wp_next_scheduled('peanut_visitors_cleanup')) {
            wp_schedule_event(time(), 'daily', 'peanut_visitors_cleanup');
        }

        // Hook into webhook events (from FormFlow)
        add_action('peanut_webhook_formflow-lite_form.submitted', [$this, 'handle_form_submission'], 10, 1);
        add_action('peanut_webhook_formflow-lite_enrollment.completed', [$this, 'handle_enrollment'], 10, 1);
    }

    /**
     * Register API routes.
     */
    public function register_api_routes(): void {
        $this->controller = new Visitors_Controller();
        $this->controller->register_routes();
    }

    /**
     * Run cleanup task.
     */
    public function run_cleanup(): void {
        $settings = get_option('peanut_settings', []);
        $retention_days = $settings['visitor_retention_days'] ?? 90;

        $deleted = Visitors_Tracker::cleanup_old_events((int) $retention_days);

        if ($deleted > 0) {
            error_log("Peanut Suite: Cleaned up {$deleted} old visitor events");
        }
    }

    /**
     * Handle form submission webhook.
     *
     * @param array $payload Webhook payload.
     */
    public function handle_form_submission(array $payload): void {
        // Extract visitor ID and email if available
        $visitor_id = $payload['visitor_id'] ?? null;
        $email = $payload['email'] ?? ($payload['data']['email'] ?? null);

        if (!$visitor_id) {
            return;
        }

        // Record a form_submit event
        $event_data = [
            'visitor_id' => $visitor_id,
            'event_type' => 'form_submit',
            'page_url' => $payload['page_url'] ?? '',
            'custom_data' => [
                'form_id' => $payload['form_id'] ?? null,
                'source' => 'formflow-lite',
            ],
        ];

        Visitors_Database::record_event($event_data);

        // Identify if email provided
        if ($email && is_email($email)) {
            Visitors_Database::identify($visitor_id, $email);

            do_action('peanut_visitor_identified', $visitor_id, $email, [
                'source' => 'form_submission',
            ]);
        }
    }

    /**
     * Handle enrollment webhook.
     *
     * @param array $payload Webhook payload.
     */
    public function handle_enrollment(array $payload): void {
        $visitor_id = $payload['visitor_id'] ?? null;
        $email = $payload['email'] ?? null;
        $contact_id = $payload['contact_id'] ?? null;

        if (!$visitor_id) {
            return;
        }

        // Record enrollment event
        $event_data = [
            'visitor_id' => $visitor_id,
            'event_type' => 'enrollment',
            'custom_data' => [
                'enrollment_id' => $payload['enrollment_id'] ?? null,
                'workflow_id' => $payload['workflow_id'] ?? null,
                'source' => 'formflow-lite',
            ],
        ];

        Visitors_Database::record_event($event_data);

        // Update visitor with contact ID
        if ($email || $contact_id) {
            $update_data = [];
            if ($contact_id) {
                $update_data['contact_id'] = absint($contact_id);
            }

            if ($email && is_email($email)) {
                Visitors_Database::identify($visitor_id, $email, $update_data);
            } elseif (!empty($update_data)) {
                Visitors_Database::update_visitor($visitor_id, $update_data);
            }
        }
    }

    /**
     * Get module info.
     *
     * @return array
     */
    public static function get_info(): array {
        return [
            'id' => 'visitors',
            'name' => 'Visitor Tracking',
            'description' => 'Track website visitors with cookie-based tracking and JavaScript snippet',
            'icon' => 'users',
            'tier' => 'pro',
            'version' => '1.0.0',
        ];
    }

    /**
     * Activation hook.
     */
    public static function activate(): void {
        Visitors_Database::create_tables();

        // Generate site ID if needed
        $snippet = new Visitors_Snippet();
        $snippet->get_site_id();

        // Add default settings
        $settings = get_option('peanut_settings', []);
        if (!isset($settings['track_visitors'])) {
            $settings['track_visitors'] = true;
            $settings['visitor_retention_days'] = 90;
            update_option('peanut_settings', $settings);
        }
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate(): void {
        // Clear scheduled events
        wp_clear_scheduled_hook('peanut_visitors_cleanup');
    }
}

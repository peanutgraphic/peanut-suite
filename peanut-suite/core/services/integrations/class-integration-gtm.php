<?php
/**
 * Google Tag Manager Integration
 *
 * Inject GTM container and push dataLayer events.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Integration_GTM {

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
        $container_id = $this->settings['gtm_container_id'] ?? '';
        return !empty($container_id) && preg_match('/^GTM-[A-Z0-9]+$/', $container_id);
    }

    /**
     * Get container ID
     */
    public function get_container_id(): string {
        return $this->settings['gtm_container_id'] ?? '';
    }

    /**
     * Get head script
     */
    public function get_head_script(): string {
        if (!$this->is_configured()) {
            return '';
        }

        $container_id = esc_js($this->get_container_id());

        return "<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$container_id}');</script>
<!-- End Google Tag Manager -->";
    }

    /**
     * Get body noscript
     */
    public function get_body_noscript(): string {
        if (!$this->is_configured()) {
            return '';
        }

        $container_id = esc_attr($this->get_container_id());

        return "<!-- Google Tag Manager (noscript) -->
<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id={$container_id}\"
height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->";
    }

    /**
     * Generate dataLayer push script
     */
    public function get_datalayer_push(string $event_name, array $data = []): string {
        $payload = array_merge(['event' => $event_name], $data);
        $json = wp_json_encode($payload);

        return "<script>window.dataLayer = window.dataLayer || []; dataLayer.push({$json});</script>";
    }

    /**
     * Get predefined events configuration
     */
    public function get_tracked_events(): array {
        return [
            'contact_created' => [
                'enabled' => $this->settings['gtm_track_contacts'] ?? true,
                'event_name' => 'peanut_lead_captured',
            ],
            'link_click' => [
                'enabled' => $this->settings['gtm_track_links'] ?? true,
                'event_name' => 'peanut_link_click',
            ],
            'popup_view' => [
                'enabled' => $this->settings['gtm_track_popups'] ?? true,
                'event_name' => 'peanut_popup_view',
            ],
            'popup_conversion' => [
                'enabled' => $this->settings['gtm_track_popups'] ?? true,
                'event_name' => 'peanut_popup_conversion',
            ],
            'utm_click' => [
                'enabled' => $this->settings['gtm_track_utm'] ?? true,
                'event_name' => 'peanut_utm_click',
            ],
        ];
    }

    /**
     * Check if specific event tracking is enabled
     */
    public function is_event_enabled(string $event): bool {
        $events = $this->get_tracked_events();
        return isset($events[$event]) && $events[$event]['enabled'];
    }

    /**
     * Get event name for a Peanut event
     */
    public function get_event_name(string $peanut_event): string {
        $events = $this->get_tracked_events();
        return $events[$peanut_event]['event_name'] ?? 'peanut_' . $peanut_event;
    }

    /**
     * Test connection (validates container ID format)
     */
    public function test_connection(): array {
        $container_id = $this->get_container_id();

        if (empty($container_id)) {
            return [
                'success' => false,
                'message' => 'GTM Container ID is required',
            ];
        }

        if (!preg_match('/^GTM-[A-Z0-9]+$/', $container_id)) {
            return [
                'success' => false,
                'message' => 'Invalid Container ID format. Should be GTM-XXXXXXX',
            ];
        }

        // Verify container exists by checking if GTM JS loads
        $response = wp_remote_get("https://www.googletagmanager.com/gtm.js?id={$container_id}", [
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Could not verify container: ' . $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            return [
                'success' => false,
                'message' => "Container not found or inaccessible (HTTP {$code})",
            ];
        }

        return [
            'success' => true,
            'message' => 'Container ID verified! GTM is ready to use.',
        ];
    }
}

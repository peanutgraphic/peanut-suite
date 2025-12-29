<?php
/**
 * Peanut Integrations Manager
 *
 * Central manager for all third-party integrations.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Integrations {

    /**
     * Singleton instance
     */
    private static ?Peanut_Integrations $instance = null;

    /**
     * Loaded integrations
     */
    private array $integrations = [];

    /**
     * Get singleton instance
     */
    public static function instance(): Peanut_Integrations {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_integrations();
        $this->init_hooks();
    }

    /**
     * Load integration classes
     */
    private function load_integrations(): void {
        $integration_files = [
            'ga4' => PEANUT_PLUGIN_DIR . 'core/services/integrations/class-integration-ga4.php',
            'gtm' => PEANUT_PLUGIN_DIR . 'core/services/integrations/class-integration-gtm.php',
            'mailchimp' => PEANUT_PLUGIN_DIR . 'core/services/integrations/class-integration-mailchimp.php',
            'convertkit' => PEANUT_PLUGIN_DIR . 'core/services/integrations/class-integration-convertkit.php',
        ];

        foreach ($integration_files as $key => $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Initialize hooks for integrations
     */
    private function init_hooks(): void {
        // Contact created - sync to email platforms
        add_action('peanut_contact_created', [$this, 'on_contact_created'], 10, 2);
        add_action('peanut_contact_updated', [$this, 'on_contact_updated'], 10, 2);

        // UTM click - send to analytics
        add_action('peanut_utm_click', [$this, 'on_utm_click'], 10, 2);

        // Link click - send to analytics
        add_action('peanut_link_click', [$this, 'on_link_click'], 10, 2);

        // Popup events - send to analytics
        add_action('peanut_popup_view', [$this, 'on_popup_view'], 10, 2);
        add_action('peanut_popup_conversion', [$this, 'on_popup_conversion'], 10, 2);

        // Frontend scripts for GTM
        add_action('wp_head', [$this, 'output_gtm_head'], 1);
        add_action('wp_body_open', [$this, 'output_gtm_body'], 1);
    }

    /**
     * Check if an integration is enabled
     */
    public function is_enabled(string $integration): bool {
        $settings = get_option('peanut_settings', []);
        return !empty($settings[$integration . '_enabled']);
    }

    /**
     * Get integration setting
     */
    public function get_setting(string $key, $default = '') {
        $settings = get_option('peanut_settings', []);
        return $settings[$key] ?? $default;
    }

    /**
     * Handle contact created
     */
    public function on_contact_created(int $contact_id, array $contact_data): void {
        // Mailchimp
        if ($this->is_enabled('mailchimp')) {
            $mailchimp = new Peanut_Integration_Mailchimp();
            $mailchimp->add_subscriber($contact_data);
        }

        // ConvertKit
        if ($this->is_enabled('convertkit')) {
            $convertkit = new Peanut_Integration_ConvertKit();
            $convertkit->add_subscriber($contact_data);
        }

        // GA4 Event
        if ($this->is_enabled('ga4')) {
            $ga4 = new Peanut_Integration_GA4();
            $ga4->send_event('generate_lead', [
                'contact_id' => $contact_id,
                'source' => $contact_data['source'] ?? 'unknown',
            ]);
        }
    }

    /**
     * Handle contact updated
     */
    public function on_contact_updated(int $contact_id, array $contact_data): void {
        // Mailchimp - update subscriber
        if ($this->is_enabled('mailchimp')) {
            $mailchimp = new Peanut_Integration_Mailchimp();
            $mailchimp->update_subscriber($contact_data);
        }

        // ConvertKit - update subscriber
        if ($this->is_enabled('convertkit')) {
            $convertkit = new Peanut_Integration_ConvertKit();
            $convertkit->update_subscriber($contact_data);
        }
    }

    /**
     * Handle UTM click
     */
    public function on_utm_click(int $utm_id, array $utm_data): void {
        if ($this->is_enabled('ga4')) {
            $ga4 = new Peanut_Integration_GA4();
            $ga4->send_event('utm_click', [
                'utm_id' => $utm_id,
                'campaign' => $utm_data['campaign'] ?? '',
                'source' => $utm_data['source'] ?? '',
                'medium' => $utm_data['medium'] ?? '',
            ]);
        }
    }

    /**
     * Handle link click
     */
    public function on_link_click(int $link_id, array $link_data): void {
        if ($this->is_enabled('ga4')) {
            $ga4 = new Peanut_Integration_GA4();
            $ga4->send_event('link_click', [
                'link_id' => $link_id,
                'short_code' => $link_data['short_code'] ?? '',
                'destination' => $link_data['destination_url'] ?? '',
            ]);
        }
    }

    /**
     * Handle popup view
     */
    public function on_popup_view(int $popup_id, array $popup_data): void {
        if ($this->is_enabled('ga4')) {
            $ga4 = new Peanut_Integration_GA4();
            $ga4->send_event('popup_view', [
                'popup_id' => $popup_id,
                'popup_name' => $popup_data['name'] ?? '',
                'popup_type' => $popup_data['type'] ?? '',
            ]);
        }
    }

    /**
     * Handle popup conversion
     */
    public function on_popup_conversion(int $popup_id, array $popup_data): void {
        if ($this->is_enabled('ga4')) {
            $ga4 = new Peanut_Integration_GA4();
            $ga4->send_event('popup_conversion', [
                'popup_id' => $popup_id,
                'popup_name' => $popup_data['name'] ?? '',
                'popup_type' => $popup_data['type'] ?? '',
            ]);
        }
    }

    /**
     * Output GTM head script
     */
    public function output_gtm_head(): void {
        if (!$this->is_enabled('gtm')) {
            return;
        }

        $container_id = $this->get_setting('gtm_container_id');
        if (empty($container_id)) {
            return;
        }

        // Validate container ID format (GTM-XXXXXX)
        if (!preg_match('/^GTM-[A-Z0-9]+$/', $container_id)) {
            return;
        }
        ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js($container_id); ?>');</script>
<!-- End Google Tag Manager -->
        <?php
    }

    /**
     * Output GTM body noscript
     */
    public function output_gtm_body(): void {
        if (!$this->is_enabled('gtm')) {
            return;
        }

        $container_id = $this->get_setting('gtm_container_id');
        if (empty($container_id)) {
            return;
        }

        // Validate container ID format
        if (!preg_match('/^GTM-[A-Z0-9]+$/', $container_id)) {
            return;
        }
        ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr($container_id); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
        <?php
    }

    /**
     * Push event to GTM dataLayer
     */
    public function push_gtm_event(string $event_name, array $event_data = []): void {
        if (!$this->is_enabled('gtm')) {
            return;
        }

        $data = array_merge(['event' => $event_name], $event_data);

        // This will be output in footer
        add_action('wp_footer', function() use ($data) {
            ?>
            <script>
                window.dataLayer = window.dataLayer || [];
                dataLayer.push(<?php echo wp_json_encode($data); ?>);
            </script>
            <?php
        }, 100);
    }
}

<?php
/**
 * Admin Assets Handler
 *
 * Manages CSS and JavaScript for admin pages.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Admin_Assets {

    /**
     * Initialize assets
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets(string $hook): void {
        // Only load on Peanut Suite pages
        if (!$this->is_peanut_page($hook)) {
            return;
        }

        $this->enqueue_styles();
        $this->enqueue_scripts();
        $this->localize_scripts();
    }

    /**
     * Check if current page is a Peanut Suite page
     */
    private function is_peanut_page(string $hook): bool {
        return strpos($hook, 'peanut') !== false;
    }

    /**
     * Enqueue stylesheets
     */
    private function enqueue_styles(): void {
        // Main admin styles
        wp_enqueue_style(
            'peanut-admin',
            PEANUT_PLUGIN_URL . 'assets/css/admin.css',
            [],
            PEANUT_VERSION
        );

        // Feature tour styles (for v2.4.0 onboarding)
        wp_enqueue_style(
            'peanut-feature-tour',
            PEANUT_PLUGIN_URL . 'assets/css/feature-tour.css',
            [],
            PEANUT_VERSION
        );

        // WordPress core styles we need
        wp_enqueue_style('wp-components');
    }

    /**
     * Enqueue scripts
     */
    private function enqueue_scripts(): void {
        // Chart.js for charts
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            [],
            '4.4.1',
            true
        );

        // Main admin script
        wp_enqueue_script(
            'peanut-admin',
            PEANUT_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-util', 'chartjs'],
            PEANUT_VERSION,
            true
        );

        // Charts helper
        wp_enqueue_script(
            'peanut-charts',
            PEANUT_PLUGIN_URL . 'assets/js/charts.js',
            ['chartjs', 'peanut-admin'],
            PEANUT_VERSION,
            true
        );

        // WordPress Pointer for tours
        wp_enqueue_style('wp-pointer');
        wp_enqueue_script('wp-pointer');

        // Feature tour script (for v2.4.0 onboarding)
        wp_enqueue_script(
            'peanut-feature-tour',
            PEANUT_PLUGIN_URL . 'assets/js/feature-tour.js',
            ['jquery', 'peanut-admin'],
            PEANUT_VERSION,
            true
        );

        // Localize feature tour data
        wp_localize_script('peanut-feature-tour', 'peanutTour', [
            'nonce' => wp_create_nonce('peanut_feature_tour'),
            'version' => PEANUT_VERSION,
        ]);
    }

    /**
     * Localize script data
     */
    private function localize_scripts(): void {
        $license = peanut_get_license();

        wp_localize_script('peanut-admin', 'peanutAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url(PEANUT_API_NAMESPACE),
            'nonce' => wp_create_nonce('wp_rest'),
            'adminUrl' => admin_url(),
            'pluginUrl' => PEANUT_PLUGIN_URL,
            'version' => PEANUT_VERSION,
            'license' => [
                'status' => $license['status'] ?? 'inactive',
                'tier' => $license['tier'] ?? 'free',
                'isPro' => peanut_is_pro(),
                'isAgency' => peanut_is_agency(),
            ],
            'i18n' => $this->get_i18n_strings(),
        ]);
    }

    /**
     * Get translatable strings for JavaScript
     */
    private function get_i18n_strings(): array {
        return [
            'confirm' => __('Are you sure?', 'peanut-suite'),
            'confirmDelete' => __('Are you sure you want to delete this? This action cannot be undone.', 'peanut-suite'),
            'confirmBulkDelete' => __('Are you sure you want to delete the selected items?', 'peanut-suite'),
            'loading' => __('Loading...', 'peanut-suite'),
            'saving' => __('Saving...', 'peanut-suite'),
            'saved' => __('Saved!', 'peanut-suite'),
            'error' => __('An error occurred. Please try again.', 'peanut-suite'),
            'copied' => __('Copied to clipboard!', 'peanut-suite'),
            'copyFailed' => __('Failed to copy. Please copy manually.', 'peanut-suite'),
            'noResults' => __('No results found.', 'peanut-suite'),
            'selectItems' => __('Please select items first.', 'peanut-suite'),
            'processingWebhook' => __('Processing webhook...', 'peanut-suite'),
            'webhookProcessed' => __('Webhook processed successfully!', 'peanut-suite'),
            'exportStarted' => __('Export started. Your download will begin shortly.', 'peanut-suite'),
            'close' => __('Close', 'peanut-suite'),
            'cancel' => __('Cancel', 'peanut-suite'),
            'save' => __('Save', 'peanut-suite'),
            'delete' => __('Delete', 'peanut-suite'),
            'edit' => __('Edit', 'peanut-suite'),
            'view' => __('View', 'peanut-suite'),
            'copy' => __('Copy', 'peanut-suite'),
            'today' => __('Today', 'peanut-suite'),
            'yesterday' => __('Yesterday', 'peanut-suite'),
            'thisWeek' => __('This Week', 'peanut-suite'),
            'thisMonth' => __('This Month', 'peanut-suite'),
            'last7Days' => __('Last 7 Days', 'peanut-suite'),
            'last30Days' => __('Last 30 Days', 'peanut-suite'),
            'last90Days' => __('Last 90 Days', 'peanut-suite'),
        ];
    }

    /**
     * Get inline admin styles
     */
    public function get_inline_styles(): string {
        $colors = $this->get_admin_colors();

        return "
            :root {
                --peanut-primary: {$colors['primary']};
                --peanut-primary-hover: {$colors['primary_hover']};
                --peanut-success: #10b981;
                --peanut-warning: #f59e0b;
                --peanut-error: #ef4444;
                --peanut-info: #3b82f6;
            }
        ";
    }

    /**
     * Get WordPress admin color scheme
     */
    private function get_admin_colors(): array {
        global $_wp_admin_css_colors;

        $scheme = get_user_option('admin_color', get_current_user_id());

        if (empty($scheme) || !isset($_wp_admin_css_colors[$scheme])) {
            $scheme = 'fresh';
        }

        $colors = $_wp_admin_css_colors[$scheme]->colors ?? ['#0073aa', '#005177'];

        return [
            'primary' => $colors[1] ?? '#0073aa',
            'primary_hover' => $colors[0] ?? '#005177',
        ];
    }
}

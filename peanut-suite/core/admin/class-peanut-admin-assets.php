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
        add_action('admin_head', [$this, 'output_inline_styles']);
        add_action('admin_footer', [$this, 'output_inline_scripts']);
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
     * Uses multiple detection methods for robustness
     */
    private function is_peanut_page(string $hook): bool {
        // Method 1: Check hook string
        if (strpos($hook, 'peanut') !== false) {
            return true;
        }

        // Method 2: Check $_GET['page'] parameter
        if (isset($_GET['page']) && strpos($_GET['page'], 'peanut') !== false) {
            return true;
        }

        return false;
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

    /**
     * Output critical inline styles as fallback
     */
    public function output_inline_styles(): void {
        // Only on Peanut Suite pages
        if (!isset($_GET['page']) || strpos($_GET['page'], 'peanut') === false) {
            return;
        }

        echo '<style id="peanut-critical-css">
            /* Critical Base Styles */
            .peanut-wrap { margin: 0 !important; padding: 20px !important; }

            /* Critical Tab Styles */
            .peanut-tabs { display: flex !important; gap: 0 !important; border-bottom: 1px solid #e2e8f0 !important; margin-bottom: 24px !important; background: #fff !important; padding: 0 16px !important; }
            .peanut-tab { display: inline-flex !important; align-items: center !important; gap: 8px !important; padding: 14px 20px !important; text-decoration: none !important; color: #64748b !important; font-weight: 500 !important; border-bottom: 2px solid transparent !important; margin-bottom: -1px !important; }
            .peanut-tab:hover { color: #0073aa !important; }
            .peanut-tab.active { color: #0073aa !important; border-bottom-color: #0073aa !important; }
            .peanut-tab-panel { display: none !important; }
            .peanut-tab-panel.active { display: block !important; }

            /* Critical Dropdown Styles */
            .peanut-dropdown { position: relative !important; display: inline-block !important; }
            .peanut-dropdown-menu { display: none !important; position: absolute !important; top: 100% !important; right: 0 !important; z-index: 1000 !important; background: #fff !important; border: 1px solid #e2e8f0 !important; border-radius: 8px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important; min-width: 160px !important; }
            .peanut-dropdown.is-open .peanut-dropdown-menu, .peanut-dropdown.open .peanut-dropdown-menu { display: block !important; }

            /* Critical Stat Icon Colors */
            .peanut-stat-icon.blue { background: #dbeafe !important; }
            .peanut-stat-icon.blue .dashicons { color: #2563eb !important; }
            .peanut-stat-icon.green { background: #dcfce7 !important; }
            .peanut-stat-icon.green .dashicons { color: #16a34a !important; }
            .peanut-stat-icon.yellow { background: #fef3c7 !important; }
            .peanut-stat-icon.yellow .dashicons { color: #d97706 !important; }
            .peanut-stat-icon.red { background: #fee2e2 !important; }
            .peanut-stat-icon.red .dashicons { color: #dc2626 !important; }

            /* Critical Stat Change Colors */
            .peanut-stat-change { display: flex !important; align-items: center !important; gap: 4px !important; margin-top: 8px !important; font-size: 12px !important; }
            .peanut-stat-change.positive { color: #16a34a !important; }
            .peanut-stat-change.negative { color: #dc2626 !important; }
            .peanut-stat-change.neutral { color: #64748b !important; }

            /* Critical Modal Styles */
            .peanut-modal-backdrop { display: none !important; position: fixed !important; inset: 0 !important; background: rgba(0,0,0,0.5) !important; z-index: 99998 !important; }
            .peanut-modal-backdrop.active { display: flex !important; align-items: center !important; justify-content: center !important; }
            .peanut-modal { display: none !important; }
            .peanut-modal.active { display: block !important; }
        </style>';
    }

    /**
     * Output critical inline scripts as fallback
     */
    public function output_inline_scripts(): void {
        // Only on Peanut Suite pages
        if (!isset($_GET['page']) || strpos($_GET['page'], 'peanut') === false) {
            return;
        }

        ?>
        <script id="peanut-critical-js">
        (function($) {
            // Fallback tab handler if main JS didn't initialize
            if (typeof window.PeanutAdmin === 'undefined' || !window.PeanutAdmin.config) {
                $(document).on('click', '.peanut-tab[data-tab]', function(e) {
                    e.preventDefault();
                    var $tab = $(this);
                    var target = $tab.data('tab');

                    $tab.closest('.peanut-tabs').find('.peanut-tab').removeClass('active');
                    $tab.addClass('active');

                    var $panels = $tab.closest('.peanut-tabs-container').find('.peanut-tab-panel');
                    $panels.removeClass('active');
                    $panels.filter('[data-panel="' + target + '"]').addClass('active');
                });

                // Fallback dropdown handler
                $(document).on('click', '.peanut-dropdown-toggle', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $dropdown = $(this).closest('.peanut-dropdown');
                    $('.peanut-dropdown').not($dropdown).removeClass('is-open open');
                    $dropdown.toggleClass('is-open');
                });

                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.peanut-dropdown').length) {
                        $('.peanut-dropdown').removeClass('is-open open');
                    }
                });

                // Fallback modal handler
                $(document).on('click', '[data-peanut-modal]', function(e) {
                    e.preventDefault();
                    var modalId = $(this).data('peanut-modal');
                    $('#' + modalId).addClass('active');
                    $('body').addClass('peanut-modal-open');
                });

                $(document).on('click', '.peanut-modal-close, .peanut-modal-backdrop', function(e) {
                    if ($(e.target).hasClass('peanut-modal-backdrop') || $(e.target).hasClass('peanut-modal-close')) {
                        $('.peanut-modal, .peanut-modal-backdrop').removeClass('active');
                        $('body').removeClass('peanut-modal-open');
                    }
                });
            }
        })(jQuery);
        </script>
        <?php
    }
}

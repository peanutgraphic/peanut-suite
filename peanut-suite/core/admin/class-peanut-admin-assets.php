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
     * Check if current page is the React app page
     */
    private function is_react_app_page(): bool {
        return isset($_GET['page']) && $_GET['page'] === 'peanut-app';
    }

    /**
     * Enqueue stylesheets
     */
    private function enqueue_styles(): void {
        // React app page - only load React styles
        if ($this->is_react_app_page()) {
            $react_css = PEANUT_PLUGIN_DIR . 'assets/dist/css/main.css';
            if (file_exists($react_css)) {
                wp_enqueue_style(
                    'peanut-react-app',
                    PEANUT_PLUGIN_URL . 'assets/dist/css/main.css',
                    [],
                    PEANUT_VERSION
                );
            }
            return; // Don't load other styles for React app
        }

        // Legacy PHP pages - load admin styles
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
        // React app page - load React bundle
        if ($this->is_react_app_page()) {
            $react_js = PEANUT_PLUGIN_DIR . 'assets/dist/js/main.js';
            if (file_exists($react_js)) {
                // Load React app as ES module
                // Dependencies include wp-element to ensure WordPress's React is loaded first
                // Our bundle uses WordPress's React via externals to prevent duplicate instances
                wp_enqueue_script(
                    'peanut-react-app',
                    PEANUT_PLUGIN_URL . 'assets/dist/js/main.js',
                    ['wp-element'], // Ensures WordPress React is loaded before our app
                    PEANUT_VERSION,
                    true
                );

                // Add module type and import map for ES modules
                add_filter('script_loader_tag', function($tag, $handle) {
                    if ($handle === 'peanut-react-app' && is_string($tag)) {
                        // Add type="module" to the script tag
                        $tag = str_replace(' src=', ' type="module" src=', $tag);

                        // Inject import map before the module to resolve bare specifiers
                        // This maps react/react-dom to WordPress's bundled versions
                        $import_map = '<script type="importmap">
{
    "imports": {
        "react": "data:text/javascript,export default window.React;export const Children=window.React.Children;export const Component=window.React.Component;export const Fragment=window.React.Fragment;export const Profiler=window.React.Profiler;export const PureComponent=window.React.PureComponent;export const StrictMode=window.React.StrictMode;export const Suspense=window.React.Suspense;export const cloneElement=window.React.cloneElement;export const createContext=window.React.createContext;export const createElement=window.React.createElement;export const createFactory=window.React.createFactory;export const createRef=window.React.createRef;export const forwardRef=window.React.forwardRef;export const isValidElement=window.React.isValidElement;export const lazy=window.React.lazy;export const memo=window.React.memo;export const startTransition=window.React.startTransition;export const useCallback=window.React.useCallback;export const useContext=window.React.useContext;export const useDebugValue=window.React.useDebugValue;export const useDeferredValue=window.React.useDeferredValue;export const useEffect=window.React.useEffect;export const useId=window.React.useId;export const useImperativeHandle=window.React.useImperativeHandle;export const useInsertionEffect=window.React.useInsertionEffect;export const useLayoutEffect=window.React.useLayoutEffect;export const useMemo=window.React.useMemo;export const useReducer=window.React.useReducer;export const useRef=window.React.useRef;export const useState=window.React.useState;export const useSyncExternalStore=window.React.useSyncExternalStore;export const useTransition=window.React.useTransition;export const version=window.React.version;",
        "react-dom": "data:text/javascript,export default window.ReactDOM;export const createPortal=window.ReactDOM.createPortal;export const flushSync=window.ReactDOM.flushSync;export const render=window.ReactDOM.render;export const hydrate=window.ReactDOM.hydrate;export const unmountComponentAtNode=window.ReactDOM.unmountComponentAtNode;export const findDOMNode=window.ReactDOM.findDOMNode;export const version=window.ReactDOM.version;",
        "react-dom/client": "data:text/javascript,export const createRoot=window.ReactDOM.createRoot;export const hydrateRoot=window.ReactDOM.hydrateRoot;export default window.ReactDOM;",
        "react/jsx-runtime": "data:text/javascript,const e=window.React;export const jsx=e.createElement;export const jsxs=e.createElement;export const Fragment=e.Fragment;export const jsxDEV=e.createElement;"
    }
}
</script>
';
                        $tag = $import_map . $tag;
                    }
                    return $tag ?? '';
                }, 10, 2);

                $license = peanut_get_license();
                $tier = $license['tier'] ?? 'free';

                // Localize for API client (client.ts expects peanutSuite)
                wp_localize_script('peanut-react-app', 'peanutSuite', [
                    'apiUrl' => rest_url(PEANUT_API_NAMESPACE),
                    'nonce' => wp_create_nonce('wp_rest'),
                    'version' => PEANUT_VERSION,
                    'isPro' => peanut_is_pro(),
                    'tier' => $tier,
                ]);

                // Get user and account context
                $user_context = $this->get_user_context();

                // Localize for Sidebar and Account context (peanutData)
                wp_localize_script('peanut-react-app', 'peanutData', [
                    'version' => PEANUT_VERSION,
                    'brandName' => apply_filters('peanut_brand_name', 'Marketing Suite'),
                    'license' => [
                        'tier' => $tier,
                        'isPro' => peanut_is_pro(),
                    ],
                    'user' => $user_context['user'],
                    'account' => $user_context['account'],
                    'logoutUrl' => wp_logout_url(home_url('/team-login/')),
                ]);
            }
            return; // Don't load legacy scripts for React app
        }

        // --- Legacy scripts for PHP pages ---

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

    /**
     * Get current user and account context for React
     */
    private function get_user_context(): array {
        $user_id = get_current_user_id();
        $current_user = wp_get_current_user();

        $user_data = null;
        $account_data = null;

        if ($user_id) {
            $user_data = [
                'id' => $user_id,
                'name' => $current_user->display_name,
                'email' => $current_user->user_email,
                'avatar' => get_avatar_url($user_id, ['size' => 96]),
            ];

            // Get or create account context if service is available
            if (class_exists('Peanut_Account_Service')) {
                $account = Peanut_Account_Service::get_or_create_for_user($user_id);

                if ($account) {
                    $member = Peanut_Account_Service::get_member($account['id'], $user_id);
                    $permissions = null;
                    $available_features = [];

                    if ($member) {
                        $permissions = Peanut_Account_Service::get_member_permissions($account['id'], $user_id);
                    }

                    $available_features = Peanut_Account_Service::get_available_features($account['tier'] ?? 'free');

                    $account_data = [
                        'id' => $account['id'],
                        'name' => $account['name'],
                        'slug' => $account['slug'] ?? '',
                        'tier' => $account['tier'] ?? 'free',
                        'role' => $member['role'] ?? 'owner',
                        'permissions' => $permissions,
                        'available_features' => $available_features,
                    ];
                }
            }
        }

        return [
            'user' => $user_data,
            'account' => $account_data,
        ];
    }
}

<?php
/**
 * Admin Pages Handler
 *
 * Routes and renders all admin pages using PHP templates.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Admin_Pages {

    /**
     * Page configurations
     */
    private array $pages = [];

    /**
     * Current page being rendered
     */
    private string $current_page = '';

    /**
     * Initialize pages
     */
    public function __construct() {
        $this->register_pages();
    }

    /**
     * Register all admin pages
     */
    private function register_pages(): void {
        // Free tier pages
        $this->pages = [
            'dashboard' => [
                'title' => __('Dashboard', 'peanut-suite'),
                'description' => __('Overview of your marketing performance', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'free',
                'icon' => 'dashicons-dashboard',
            ],
            'utm-builder' => [
                'title' => __('UTM Builder', 'peanut-suite'),
                'description' => __('Create tracked URLs with UTM parameters', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'free',
                'icon' => 'dashicons-tag',
            ],
            'utm-library' => [
                'title' => __('UTM Library', 'peanut-suite'),
                'description' => __('Manage your saved UTM campaigns', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'free',
                'icon' => 'dashicons-list-view',
                'parent' => 'utm-builder',
            ],
            'links' => [
                'title' => __('Links', 'peanut-suite'),
                'description' => __('Create and manage short links', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'free',
                'icon' => 'dashicons-admin-links',
            ],
            'contacts' => [
                'title' => __('Contacts', 'peanut-suite'),
                'description' => __('Manage your leads and contacts', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'free',
                'icon' => 'dashicons-groups',
            ],
            'webhooks' => [
                'title' => __('Webhooks', 'peanut-suite'),
                'description' => __('Monitor incoming webhook events', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'free',
                'icon' => 'dashicons-rest-api',
            ],
            // Pro tier pages
            'visitors' => [
                'title' => __('Visitors', 'peanut-suite'),
                'description' => __('Track website visitors and their journeys', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-visibility',
            ],
            'visitor-detail' => [
                'title' => __('Visitor Details', 'peanut-suite'),
                'description' => __('View visitor activity timeline', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'parent' => 'visitors',
                'hidden' => true,
            ],
            'attribution' => [
                'title' => __('Attribution', 'peanut-suite'),
                'description' => __('Multi-touch attribution analysis', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-chart-area',
            ],
            'analytics' => [
                'title' => __('Analytics', 'peanut-suite'),
                'description' => __('Comprehensive analytics dashboard', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-chart-bar',
            ],
            'popups' => [
                'title' => __('Popups', 'peanut-suite'),
                'description' => __('Create and manage conversion popups', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-format-chat',
            ],
            'popup-builder' => [
                'title' => __('Popup Builder', 'peanut-suite'),
                'description' => __('Design your popup', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'parent' => 'popups',
                'hidden' => true,
            ],
            // Agency tier pages
            'monitor' => [
                'title' => __('Monitor', 'peanut-suite'),
                'description' => __('Monitor multiple WordPress sites', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'agency',
                'icon' => 'dashicons-admin-multisite',
            ],
            'site-detail' => [
                'title' => __('Site Details', 'peanut-suite'),
                'description' => __('View site health and updates', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'agency',
                'parent' => 'monitor',
                'hidden' => true,
            ],
            // Pro tier pages (continued)
            'security' => [
                'title' => __('Security', 'peanut-suite'),
                'description' => __('Protect your site with login security, IP blocking, and 2FA', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-shield',
            ],
            'reports' => [
                'title' => __('Email Reports', 'peanut-suite'),
                'description' => __('Schedule automated email digest reports', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-email-alt',
            ],
            'backlinks' => [
                'title' => __('Backlinks', 'peanut-suite'),
                'description' => __('Discover and track sites linking to you', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-admin-links',
            ],
            'seo' => [
                'title' => __('SEO Tools', 'peanut-suite'),
                'description' => __('Keyword rank tracking and SEO audits', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-search',
            ],
            'woocommerce' => [
                'title' => __('WooCommerce', 'peanut-suite'),
                'description' => __('Track revenue by marketing campaign', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-cart',
            ],
            'sequences' => [
                'title' => __('Email Sequences', 'peanut-suite'),
                'description' => __('Automated drip campaigns and email sequences', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-email-alt2',
            ],
            'notifications' => [
                'title' => __('Notifications', 'peanut-suite'),
                'description' => __('Slack, Discord, and Telegram notifications', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-bell',
            ],
            'calendar' => [
                'title' => __('Content Calendar', 'peanut-suite'),
                'description' => __('Editorial calendar for content planning', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-calendar-alt',
            ],
            'forms' => [
                'title' => __('Form Analytics', 'peanut-suite'),
                'description' => __('Track form submissions and abandonment', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-feedback',
            ],
            'accessibility' => [
                'title' => __('Accessibility', 'peanut-suite'),
                'description' => __('ADA compliance tools and accessibility widget', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-universal-access-alt',
            ],
            'ga-integration' => [
                'title' => __('Google Analytics', 'peanut-suite'),
                'description' => __('Connect GA4 and Search Console for unified analytics', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'pro',
                'icon' => 'dashicons-chart-area',
            ],
            // Agency tier pages (continued)
            'whitelabel' => [
                'title' => __('White-Label', 'peanut-suite'),
                'description' => __('Custom branding for reports and dashboards', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'agency',
                'icon' => 'dashicons-art',
            ],
            // Settings (all tiers)
            'settings' => [
                'title' => __('Settings', 'peanut-suite'),
                'description' => __('Configure Peanut Suite', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'free',
                'icon' => 'dashicons-admin-settings',
            ],
            // API Checker - diagnostic tool (always available)
            'api-checker' => [
                'title' => __('API Checker', 'peanut-suite'),
                'description' => __('Test and diagnose API connections', 'peanut-suite'),
                'capability' => 'manage_options',
                'tier' => 'free',
                'icon' => 'dashicons-admin-tools',
            ],
        ];
    }

    /**
     * Get page configuration
     */
    public function get_page(string $slug): ?array {
        return $this->pages[$slug] ?? null;
    }

    /**
     * Get all pages
     */
    public function get_pages(): array {
        return $this->pages;
    }

    /**
     * Get pages for menu (visible, accessible)
     */
    public function get_menu_pages(): array {
        $menu_pages = [];

        foreach ($this->pages as $slug => $page) {
            // Skip hidden pages
            if (!empty($page['hidden'])) {
                continue;
            }

            // Check tier access
            if (!$this->user_has_tier_access($page['tier'])) {
                continue;
            }

            $menu_pages[$slug] = $page;
        }

        return $menu_pages;
    }

    /**
     * Check if user has access to tier
     */
    private function user_has_tier_access(string $tier): bool {
        switch ($tier) {
            case 'agency':
                return peanut_is_agency();
            case 'pro':
                return peanut_is_pro();
            case 'free':
            default:
                return true;
        }
    }

    /**
     * Render a page
     */
    public function render(string $page_slug): void {
        $page = $this->get_page($page_slug);

        if (!$page) {
            $this->render_404();
            return;
        }

        // Check capability
        if (!current_user_can($page['capability'])) {
            wp_die(__('You do not have permission to access this page.', 'peanut-suite'));
        }

        // Check tier access
        if (!$this->user_has_tier_access($page['tier'])) {
            $this->render_upgrade_notice($page['tier']);
            return;
        }

        $this->current_page = $page_slug;

        // Render the page
        $this->render_header($page);
        $this->render_content($page_slug);
        $this->render_footer();
    }

    /**
     * Render page header
     */
    private function render_header(array $page): void {
        ?>
        <div class="wrap peanut-wrap">
            <div class="peanut-header">
                <div class="peanut-header-content">
                    <h1 class="peanut-page-title">
                        <?php if (!empty($page['icon'])): ?>
                            <span class="dashicons <?php echo esc_attr($page['icon']); ?>"></span>
                        <?php endif; ?>
                        <?php echo esc_html($page['title']); ?>
                    </h1>
                    <?php if (!empty($page['description'])): ?>
                        <p class="peanut-page-description"><?php echo esc_html($page['description']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="peanut-header-actions">
                    <?php $this->render_header_actions($this->current_page); ?>
                </div>
            </div>
        <?php
    }

    /**
     * Render header action buttons
     */
    private function render_header_actions(string $page_slug): void {
        switch ($page_slug) {
            case 'utm-builder':
                ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-utm-library')); ?>" class="button">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('View Library', 'peanut-suite'); ?>
                </a>
                <?php
                break;
            case 'utm-library':
                ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-utm-builder')); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Create UTM', 'peanut-suite'); ?>
                </a>
                <?php
                break;
            case 'links':
                ?>
                <button type="button" class="button button-primary" id="peanut-add-link">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Add New Link', 'peanut-suite'); ?>
                </button>
                <?php
                break;
            case 'contacts':
                ?>
                <button type="button" class="button button-primary" id="peanut-add-contact">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Add Contact', 'peanut-suite'); ?>
                </button>
                <button type="button" class="button" id="peanut-export-contacts">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Export', 'peanut-suite'); ?>
                </button>
                <?php
                break;
            case 'popups':
                ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-popup-builder')); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Create Popup', 'peanut-suite'); ?>
                </a>
                <?php
                break;
            case 'visitors':
                ?>
                <button type="button" class="button" id="peanut-get-snippet">
                    <span class="dashicons dashicons-editor-code"></span>
                    <?php esc_html_e('Get Tracking Code', 'peanut-suite'); ?>
                </button>
                <?php
                break;
            case 'monitor':
                ?>
                <button type="button" class="button button-primary" id="peanut-add-site">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Add Site', 'peanut-suite'); ?>
                </button>
                <?php
                break;
        }
    }

    /**
     * Render page content
     */
    private function render_content(string $page_slug): void {
        $view_file = PEANUT_PLUGIN_DIR . 'core/admin/views/' . $page_slug . '.php';

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            $this->render_coming_soon($page_slug);
        }
    }

    /**
     * Render page footer
     */
    private function render_footer(): void {
        ?>
        </div><!-- .peanut-wrap -->
        <?php
    }

    /**
     * Render 404 page
     */
    private function render_404(): void {
        ?>
        <div class="wrap peanut-wrap">
            <div class="peanut-notice peanut-notice-error">
                <h2><?php esc_html_e('Page Not Found', 'peanut-suite'); ?></h2>
                <p><?php esc_html_e('The page you are looking for does not exist.', 'peanut-suite'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-suite')); ?>" class="button">
                    <?php esc_html_e('Go to Dashboard', 'peanut-suite'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render upgrade notice for locked features
     */
    private function render_upgrade_notice(string $required_tier): void {
        $tier_names = [
            'pro' => __('Pro', 'peanut-suite'),
            'agency' => __('Agency', 'peanut-suite'),
        ];

        $tier_name = $tier_names[$required_tier] ?? $required_tier;
        ?>
        <div class="wrap peanut-wrap">
            <div class="peanut-upgrade-notice">
                <div class="peanut-upgrade-icon">
                    <span class="dashicons dashicons-lock"></span>
                </div>
                <h2><?php printf(esc_html__('%s Feature', 'peanut-suite'), $tier_name); ?></h2>
                <p>
                    <?php printf(
                        esc_html__('This feature requires a %s license. Upgrade to unlock this and other powerful features.', 'peanut-suite'),
                        '<strong>' . esc_html($tier_name) . '</strong>'
                    ); ?>
                </p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=peanut-settings&tab=license')); ?>" class="button button-primary button-hero">
                    <?php esc_html_e('Upgrade Now', 'peanut-suite'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render coming soon placeholder
     */
    private function render_coming_soon(string $page_slug): void {
        ?>
        <div class="peanut-coming-soon">
            <div class="peanut-coming-soon-icon">
                <span class="dashicons dashicons-hammer"></span>
            </div>
            <h2><?php esc_html_e('Coming Soon', 'peanut-suite'); ?></h2>
            <p><?php esc_html_e('This page is under construction. Check back soon!', 'peanut-suite'); ?></p>
        </div>
        <?php
    }

    /**
     * Get current page slug
     */
    public function get_current_page(): string {
        return $this->current_page;
    }

    /**
     * Get dashboard stats
     */
    public function get_dashboard_stats(): array {
        global $wpdb;

        $stats = [
            'utms' => ['total' => 0, 'change' => 0],
            'links' => ['total' => 0, 'clicks' => 0],
            'contacts' => ['total' => 0, 'change' => 0],
            'clicks' => ['total' => 0],
            'recent_utms' => [],
            'sources' => ['labels' => [], 'data' => []],
            'timeline' => ['labels' => [], 'utm_clicks' => [], 'link_clicks' => []],
            'activity' => [],
        ];

        // Get UTM count
        $utms_table = $wpdb->prefix . 'peanut_utms';
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $utms_table)) === $utms_table) {
            $stats['utms']['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $utms_table");

            // Get recent UTMs
            $stats['recent_utms'] = $wpdb->get_results(
                "SELECT * FROM $utms_table ORDER BY created_at DESC LIMIT 5",
                ARRAY_A
            ) ?: [];

            // Get total clicks
            $stats['clicks']['total'] = (int) $wpdb->get_var("SELECT COALESCE(SUM(click_count), 0) FROM $utms_table");

            // Get sources breakdown
            $sources = $wpdb->get_results(
                "SELECT utm_source, SUM(click_count) as clicks FROM $utms_table GROUP BY utm_source ORDER BY clicks DESC LIMIT 5",
                ARRAY_A
            ) ?: [];

            foreach ($sources as $source) {
                if ($source['utm_source']) {
                    $stats['sources']['labels'][] = ucfirst($source['utm_source']);
                    $stats['sources']['data'][] = (int) $source['clicks'];
                }
            }
        }

        // Get links count
        $links_table = $wpdb->prefix . 'peanut_links';
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $links_table)) === $links_table) {
            $stats['links']['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $links_table");
            $stats['links']['clicks'] = (int) $wpdb->get_var("SELECT COALESCE(SUM(click_count), 0) FROM $links_table");
        }

        // Get contacts count
        $contacts_table = $wpdb->prefix . 'peanut_contacts';
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $contacts_table)) === $contacts_table) {
            $stats['contacts']['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $contacts_table");
        }

        // Generate timeline labels (last 30 days)
        for ($i = 29; $i >= 0; $i--) {
            $date = date('M j', strtotime("-$i days"));
            $stats['timeline']['labels'][] = $date;
            $stats['timeline']['utm_clicks'][] = rand(0, 50); // Placeholder - would come from real data
            $stats['timeline']['link_clicks'][] = rand(0, 30);
        }

        return $stats;
    }
}

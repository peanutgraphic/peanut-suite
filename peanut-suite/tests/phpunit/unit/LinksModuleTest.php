<?php
/**
 * Unit Tests for Links Module
 *
 * Tests URL shortening, slug generation, QR code generation, click tracking,
 * and user agent parsing.
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once PEANUT_PLUGIN_DIR . 'modules/links/class-links-module.php';

class LinksModuleTest extends Peanut_Test_Case {

    /**
     * @var Links_Module
     */
    private Links_Module $module;

    protected function setUp(): void {
        parent::setUp();
        $this->module = new Links_Module();
        global $wpdb;
        $wpdb = new wpdb();
    }

    // =========================================
    // Slug Generation Tests
    // =========================================

    /**
     * Test slug generation returns correct length
     */
    public function test_generate_slug_default_length(): void {
        $slug = Links_Module::generate_slug();

        $this->assertEquals(6, strlen($slug));
    }

    /**
     * Test slug generation with custom length
     */
    public function test_generate_slug_custom_length(): void {
        $slug = Links_Module::generate_slug(8);
        $this->assertEquals(8, strlen($slug));

        $slug = Links_Module::generate_slug(10);
        $this->assertEquals(10, strlen($slug));

        $slug = Links_Module::generate_slug(4);
        $this->assertEquals(4, strlen($slug));
    }

    /**
     * Test slug contains only alphanumeric characters
     */
    public function test_generate_slug_alphanumeric_only(): void {
        for ($i = 0; $i < 10; $i++) {
            $slug = Links_Module::generate_slug(20);
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $slug);
        }
    }

    /**
     * Test slug generation produces unique values
     */
    public function test_generate_slug_uniqueness(): void {
        $slugs = [];
        for ($i = 0; $i < 100; $i++) {
            $slugs[] = Links_Module::generate_slug(6);
        }

        // Most should be unique (very small chance of collision)
        $unique = array_unique($slugs);
        $this->assertGreaterThan(95, count($unique));
    }

    /**
     * Test slug with minimum length
     */
    public function test_generate_slug_minimum_length(): void {
        $slug = Links_Module::generate_slug(1);
        $this->assertEquals(1, strlen($slug));
    }

    // =========================================
    // QR Code URL Tests
    // =========================================

    /**
     * Test QR code URL generation with default size
     */
    public function test_qr_code_url_default_size(): void {
        $url = Links_Module::get_qr_code_url('https://example.com/go/test');

        $this->assertStringContainsString('api.qrserver.com', $url);
        $this->assertStringContainsString('200x200', $url);
        $this->assertStringContainsString('example.com', $url);
    }

    /**
     * Test QR code URL generation with custom size
     */
    public function test_qr_code_url_custom_size(): void {
        $url = Links_Module::get_qr_code_url('https://example.com', 500);

        $this->assertStringContainsString('500x500', $url);
    }

    /**
     * Test QR code URL encodes special characters
     */
    public function test_qr_code_url_encoding(): void {
        $url = Links_Module::get_qr_code_url('https://example.com/page?foo=bar&baz=qux');

        $this->assertStringContainsString('api.qrserver.com', $url);
        // URL should be properly encoded
        $this->assertStringContainsString('data=', $url);
    }

    /**
     * Test QR code URL with different sizes
     *
     * @dataProvider qrCodeSizeProvider
     */
    public function test_qr_code_url_sizes(int $size, string $expected): void {
        $url = Links_Module::get_qr_code_url('https://test.com', $size);

        $this->assertStringContainsString($expected, $url);
    }

    public static function qrCodeSizeProvider(): array {
        return [
            'small' => [100, '100x100'],
            'medium' => [250, '250x250'],
            'large' => [500, '500x500'],
            'extra large' => [1000, '1000x1000'],
        ];
    }

    // =========================================
    // User Agent Parsing Tests
    // =========================================

    /**
     * Test user agent parsing for desktop Chrome
     */
    public function test_parse_user_agent_chrome_desktop(): void {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

        $module = new Links_Module();
        $reflection = new ReflectionClass($module);
        $method = $reflection->getMethod('parse_user_agent');
        $method->setAccessible(true);

        $result = $method->invoke($module, $ua);

        $this->assertEquals('desktop', $result['device']);
        $this->assertEquals('Chrome', $result['browser']);
        $this->assertEquals('Windows', $result['os']);
    }

    /**
     * Test user agent parsing for mobile Safari
     */
    public function test_parse_user_agent_safari_mobile(): void {
        $ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Mobile/15E148 Safari/604.1';

        $module = new Links_Module();
        $reflection = new ReflectionClass($module);
        $method = $reflection->getMethod('parse_user_agent');
        $method->setAccessible(true);

        $result = $method->invoke($module, $ua);

        $this->assertEquals('mobile', $result['device']);
        $this->assertEquals('Safari', $result['browser']);
        $this->assertEquals('iOS', $result['os']);
    }

    /**
     * Test user agent parsing for iPad
     */
    public function test_parse_user_agent_ipad(): void {
        $ua = 'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Mobile/15E148 Safari/604.1';

        $module = new Links_Module();
        $reflection = new ReflectionClass($module);
        $method = $reflection->getMethod('parse_user_agent');
        $method->setAccessible(true);

        $result = $method->invoke($module, $ua);

        $this->assertEquals('tablet', $result['device']);
        $this->assertEquals('Safari', $result['browser']);
        $this->assertEquals('iOS', $result['os']);
    }

    /**
     * Test user agent parsing for Firefox Linux
     */
    public function test_parse_user_agent_firefox_linux(): void {
        $ua = 'Mozilla/5.0 (X11; Linux x86_64; rv:89.0) Gecko/20100101 Firefox/89.0';

        $module = new Links_Module();
        $reflection = new ReflectionClass($module);
        $method = $reflection->getMethod('parse_user_agent');
        $method->setAccessible(true);

        $result = $method->invoke($module, $ua);

        $this->assertEquals('desktop', $result['device']);
        $this->assertEquals('Firefox', $result['browser']);
        $this->assertEquals('Linux', $result['os']);
    }

    /**
     * Test user agent parsing for Android
     */
    public function test_parse_user_agent_android(): void {
        $ua = 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36';

        $module = new Links_Module();
        $reflection = new ReflectionClass($module);
        $method = $reflection->getMethod('parse_user_agent');
        $method->setAccessible(true);

        $result = $method->invoke($module, $ua);

        $this->assertEquals('mobile', $result['device']);
        $this->assertEquals('Chrome', $result['browser']);
        $this->assertEquals('Android', $result['os']);
    }

    /**
     * Test user agent parsing for Edge
     */
    public function test_parse_user_agent_edge(): void {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59';

        $module = new Links_Module();
        $reflection = new ReflectionClass($module);
        $method = $reflection->getMethod('parse_user_agent');
        $method->setAccessible(true);

        $result = $method->invoke($module, $ua);

        $this->assertEquals('desktop', $result['device']);
        $this->assertEquals('Edge', $result['browser']);
        $this->assertEquals('Windows', $result['os']);
    }

    /**
     * Test user agent parsing for unknown
     */
    public function test_parse_user_agent_unknown(): void {
        $ua = 'Custom Bot/1.0';

        $module = new Links_Module();
        $reflection = new ReflectionClass($module);
        $method = $reflection->getMethod('parse_user_agent');
        $method->setAccessible(true);

        $result = $method->invoke($module, $ua);

        $this->assertEquals('desktop', $result['device']);
        $this->assertEquals('unknown', $result['browser']);
        $this->assertEquals('unknown', $result['os']);
    }

    /**
     * Test user agent parsing for Mac
     */
    public function test_parse_user_agent_mac(): void {
        $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

        $module = new Links_Module();
        $reflection = new ReflectionClass($module);
        $method = $reflection->getMethod('parse_user_agent');
        $method->setAccessible(true);

        $result = $method->invoke($module, $ua);

        $this->assertEquals('desktop', $result['device']);
        $this->assertEquals('Chrome', $result['browser']);
        $this->assertEquals('macOS', $result['os']);
    }

    // =========================================
    // Module Initialization Tests
    // =========================================

    /**
     * Test module registers routes action
     */
    public function test_module_registers_routes_action(): void {
        global $wp_actions;
        $wp_actions = [];

        $this->module->init();

        $this->assertArrayHasKey('peanut_register_routes', $wp_actions);
    }

    /**
     * Test module registers init action for rewrite rules
     */
    public function test_module_registers_init_action(): void {
        global $wp_actions;
        $wp_actions = [];

        $this->module->init();

        $this->assertArrayHasKey('init', $wp_actions);
    }

    /**
     * Test module registers template_redirect action
     */
    public function test_module_registers_redirect_action(): void {
        global $wp_actions;
        $wp_actions = [];

        $this->module->init();

        $this->assertArrayHasKey('template_redirect', $wp_actions);
    }

    /**
     * Test module registers dashboard stats filter
     */
    public function test_module_registers_dashboard_filter(): void {
        global $wp_filters;
        $wp_filters = [];

        $this->module->init();

        $this->assertArrayHasKey('peanut_dashboard_stats', $wp_filters);
    }

    // =========================================
    // Link Prefix Tests
    // =========================================

    /**
     * Test get_link_prefix returns default
     */
    public function test_get_link_prefix_default(): void {
        global $wp_options;
        $wp_options = [];

        $prefix = $this->module->get_link_prefix();

        $this->assertEquals('go', $prefix);
    }

    /**
     * Test get_link_prefix returns custom setting
     */
    public function test_get_link_prefix_custom(): void {
        global $wp_options;
        $wp_options = [
            'peanut_settings' => [
                'link_prefix' => 'l',
            ],
        ];

        $prefix = $this->module->get_link_prefix();

        $this->assertEquals('l', $prefix);
    }

    // =========================================
    // Date Clause Tests
    // =========================================

    /**
     * Test date clause generation for stats
     */
    public function test_date_clause_generation(): void {
        $module = new Links_Module();
        $reflection = new ReflectionClass($module);
        $method = $reflection->getMethod('get_date_clause');
        $method->setAccessible(true);

        $clause_7d = $method->invoke($module, '7d');
        $this->assertStringContainsString('7 DAY', $clause_7d);

        $clause_30d = $method->invoke($module, '30d');
        $this->assertStringContainsString('30 DAY', $clause_30d);

        $clause_90d = $method->invoke($module, '90d');
        $this->assertStringContainsString('90 DAY', $clause_90d);

        $clause_all = $method->invoke($module, 'all');
        $this->assertEquals('', $clause_all);
    }

    // =========================================
    // Dashboard Stats Tests
    // =========================================

    /**
     * Test add_dashboard_stats adds expected keys
     */
    public function test_add_dashboard_stats(): void {
        $stats = [];

        $result = $this->module->add_dashboard_stats($stats, '7d');

        $this->assertArrayHasKey('links_total', $result);
        $this->assertArrayHasKey('links_clicks', $result);
        $this->assertArrayHasKey('links_clicks_period', $result);
    }

    /**
     * Test dashboard stats are integers
     */
    public function test_dashboard_stats_are_integers(): void {
        $stats = [];

        $result = $this->module->add_dashboard_stats($stats, '7d');

        $this->assertIsInt($result['links_total']);
        $this->assertIsInt($result['links_clicks']);
        $this->assertIsInt($result['links_clicks_period']);
    }

    // =========================================
    // Password Verification Tests
    // =========================================

    /**
     * Test verify_password returns false by default
     */
    public function test_verify_password_default(): void {
        $module = new Links_Module();
        $reflection = new ReflectionClass($module);
        $method = $reflection->getMethod('verify_password');
        $method->setAccessible(true);

        $result = $method->invoke($module, 1);

        // Default implementation returns false (needs session/cookie handling)
        $this->assertFalse($result);
    }

    // =========================================
    // Route Registration Tests
    // =========================================

    /**
     * Test register_routes loads controller
     */
    public function test_register_routes_loads_controller(): void {
        $this->module->register_routes();

        // The controller file should be included
        $this->assertTrue(class_exists('Links_Controller'));
    }

    // =========================================
    // Rewrite Rules Tests
    // =========================================

    /**
     * Test register_rewrite_rules function exists
     */
    public function test_register_rewrite_rules_exists(): void {
        $this->assertTrue(method_exists($this->module, 'register_rewrite_rules'));
    }

    // =========================================
    // Redirect Handler Tests
    // =========================================

    /**
     * Test handle_redirect method exists
     */
    public function test_handle_redirect_exists(): void {
        $this->assertTrue(method_exists($this->module, 'handle_redirect'));
    }

    // =========================================
    // Click Tracking Tests
    // =========================================

    /**
     * Test track_click method exists
     */
    public function test_track_click_method_exists(): void {
        $module = new Links_Module();
        $reflection = new ReflectionClass($module);

        $this->assertTrue($reflection->hasMethod('track_click'));
    }
}

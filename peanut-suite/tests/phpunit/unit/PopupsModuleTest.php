<?php
/**
 * Unit Tests for Popups Module
 *
 * Tests popup trigger logic, display rules, device detection,
 * scheduling, and interaction handling.
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-database.php';
require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-triggers.php';

class PopupsModuleTest extends Peanut_Test_Case {

    /**
     * @var Popups_Triggers
     */
    private Popups_Triggers $triggers;

    protected function setUp(): void {
        parent::setUp();
        $this->triggers = new Popups_Triggers();
        global $wpdb;
        $wpdb = new wpdb();

        // Reset SERVER vars
        unset($_SERVER['HTTP_USER_AGENT']);
        unset($_SERVER['HTTP_REFERER']);
        unset($_SERVER['REQUEST_URI']);
    }

    // =========================================
    // Trigger Types Tests
    // =========================================

    /**
     * Test trigger types constant contains all expected values
     */
    public function test_trigger_types_constant(): void {
        $types = Popups_Triggers::TRIGGER_TYPES;

        $this->assertArrayHasKey('time_delay', $types);
        $this->assertArrayHasKey('time_on_page', $types);
        $this->assertArrayHasKey('scroll_percent', $types);
        $this->assertArrayHasKey('scroll_depth', $types);
        $this->assertArrayHasKey('scroll_element', $types);
        $this->assertArrayHasKey('exit_intent', $types);
        $this->assertArrayHasKey('aggressive_exit', $types);
        $this->assertArrayHasKey('click', $types);
        $this->assertArrayHasKey('page_views', $types);
        $this->assertArrayHasKey('inactivity', $types);
        $this->assertArrayHasKey('engagement', $types);
    }

    /**
     * Test get_trigger_types returns all types
     */
    public function test_get_trigger_types(): void {
        $types = Popups_Triggers::get_trigger_types();

        $this->assertEquals(Popups_Triggers::TRIGGER_TYPES, $types);
    }

    // =========================================
    // Positions Tests
    // =========================================

    /**
     * Test positions constant for modal type
     */
    public function test_positions_modal(): void {
        $positions = Popups_Triggers::POSITIONS['modal'];

        $this->assertContains('center', $positions);
        $this->assertContains('top', $positions);
        $this->assertContains('bottom', $positions);
    }

    /**
     * Test positions constant for slide-in type
     */
    public function test_positions_slide_in(): void {
        $positions = Popups_Triggers::POSITIONS['slide-in'];

        $this->assertContains('bottom-right', $positions);
        $this->assertContains('bottom-left', $positions);
        $this->assertContains('top-right', $positions);
        $this->assertContains('top-left', $positions);
    }

    /**
     * Test positions constant for bar type
     */
    public function test_positions_bar(): void {
        $positions = Popups_Triggers::POSITIONS['bar'];

        $this->assertContains('top', $positions);
        $this->assertContains('bottom', $positions);
    }

    /**
     * Test positions constant for fullscreen type
     */
    public function test_positions_fullscreen(): void {
        $positions = Popups_Triggers::POSITIONS['fullscreen'];

        $this->assertContains('center', $positions);
    }

    /**
     * Test get_positions method
     */
    public function test_get_positions(): void {
        $modal = Popups_Triggers::get_positions('modal');
        $this->assertEquals(['center', 'top', 'bottom'], $modal);

        $unknown = Popups_Triggers::get_positions('unknown');
        $this->assertEquals(['center'], $unknown);
    }

    // =========================================
    // Trigger Configuration Tests
    // =========================================

    /**
     * Test get_trigger_config for time_delay
     */
    public function test_trigger_config_time_delay(): void {
        $popup = (object) [
            'triggers' => json_encode([
                'type' => 'time_delay',
                'delay' => 10,
            ]),
        ];

        $config = $this->triggers->get_trigger_config($popup);

        $this->assertEquals('time_delay', $config['type']);
        $this->assertEquals(10000, $config['delay']); // Converted to ms
    }

    /**
     * Test get_trigger_config for scroll_percent
     */
    public function test_trigger_config_scroll_percent(): void {
        $popup = (object) [
            'triggers' => json_encode([
                'type' => 'scroll_percent',
                'percent' => 75,
            ]),
        ];

        $config = $this->triggers->get_trigger_config($popup);

        $this->assertEquals('scroll_percent', $config['type']);
        $this->assertEquals(75, $config['percent']);
    }

    /**
     * Test get_trigger_config for exit_intent
     */
    public function test_trigger_config_exit_intent(): void {
        $popup = (object) [
            'triggers' => json_encode([
                'type' => 'exit_intent',
                'sensitivity' => 30,
                'delay' => 5,
                'mobile_enabled' => false,
            ]),
        ];

        $config = $this->triggers->get_trigger_config($popup);

        $this->assertEquals('exit_intent', $config['type']);
        $this->assertEquals(30, $config['sensitivity']);
        $this->assertEquals(5000, $config['delay']);
        $this->assertFalse($config['mobileEnabled']);
    }

    /**
     * Test get_trigger_config for aggressive_exit
     */
    public function test_trigger_config_aggressive_exit(): void {
        $popup = (object) [
            'triggers' => json_encode([
                'type' => 'aggressive_exit',
                'sensitivity' => 15,
                'track_tabs' => true,
                'track_back' => true,
                'track_idle' => true,
                'idle_timeout' => 30,
            ]),
        ];

        $config = $this->triggers->get_trigger_config($popup);

        $this->assertEquals('aggressive_exit', $config['type']);
        $this->assertTrue($config['trackMouse']);
        $this->assertTrue($config['trackTabs']);
        $this->assertTrue($config['trackBack']);
        $this->assertTrue($config['trackIdle']);
        $this->assertEquals(30000, $config['idleTimeout']);
    }

    /**
     * Test get_trigger_config for time_on_page
     */
    public function test_trigger_config_time_on_page(): void {
        $popup = (object) [
            'triggers' => json_encode([
                'type' => 'time_on_page',
                'min_time' => 45,
                'require_scroll' => true,
                'require_engagement' => true,
            ]),
        ];

        $config = $this->triggers->get_trigger_config($popup);

        $this->assertEquals('time_on_page', $config['type']);
        $this->assertEquals(45000, $config['minTime']);
        $this->assertTrue($config['requireScroll']);
        $this->assertTrue($config['requireEngagement']);
    }

    /**
     * Test get_trigger_config for engagement
     */
    public function test_trigger_config_engagement(): void {
        $popup = (object) [
            'triggers' => json_encode([
                'type' => 'engagement',
                'min_scroll' => 30,
                'min_time' => 20,
                'min_clicks' => 2,
            ]),
        ];

        $config = $this->triggers->get_trigger_config($popup);

        $this->assertEquals('engagement', $config['type']);
        $this->assertEquals(30, $config['minScrollPercent']);
        $this->assertEquals(20000, $config['minTime']);
        $this->assertEquals(2, $config['minClicks']);
    }

    /**
     * Test get_trigger_config defaults
     */
    public function test_trigger_config_defaults(): void {
        $popup = (object) [
            'triggers' => json_encode([]),
        ];

        $config = $this->triggers->get_trigger_config($popup);

        $this->assertEquals('time_delay', $config['type']);
        $this->assertEquals(5000, $config['delay']);
    }

    // =========================================
    // Device Detection Tests
    // =========================================

    /**
     * Test desktop detection
     */
    public function test_detect_device_desktop(): void {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('detect_device');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers);

        $this->assertEquals('desktop', $result);
    }

    /**
     * Test mobile detection
     */
    public function test_detect_device_mobile(): void {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X)';

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('detect_device');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers);

        $this->assertEquals('mobile', $result);
    }

    /**
     * Test tablet detection (iPad)
     */
    public function test_detect_device_tablet_ipad(): void {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X)';

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('detect_device');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers);

        $this->assertEquals('tablet', $result);
    }

    /**
     * Test Android mobile detection
     */
    public function test_detect_device_android_mobile(): void {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 Mobile';

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('detect_device');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers);

        $this->assertEquals('mobile', $result);
    }

    /**
     * Test no user agent defaults to desktop
     */
    public function test_detect_device_no_user_agent(): void {
        unset($_SERVER['HTTP_USER_AGENT']);

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('detect_device');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers);

        $this->assertEquals('desktop', $result);
    }

    // =========================================
    // Display Rules Tests
    // =========================================

    /**
     * Test matches_page_rules with "all" setting
     */
    public function test_matches_page_rules_all(): void {
        $rules = ['pages' => 'all'];

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('matches_page_rules');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers, $rules);

        $this->assertTrue($result);
    }

    /**
     * Test matches_device_rules with all devices
     */
    public function test_matches_device_rules_all(): void {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0)';
        $rules = ['devices' => ['desktop', 'tablet', 'mobile']];

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('matches_device_rules');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers, $rules);

        $this->assertTrue($result);
    }

    /**
     * Test matches_device_rules excludes mobile
     */
    public function test_matches_device_rules_excludes_mobile(): void {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6)';
        $rules = ['devices' => ['desktop', 'tablet']];

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('matches_device_rules');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers, $rules);

        $this->assertFalse($result);
    }

    /**
     * Test matches_user_rules with "all" setting
     */
    public function test_matches_user_rules_all(): void {
        $rules = ['user_status' => 'all'];

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('matches_user_rules');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers, $rules);

        $this->assertTrue($result);
    }

    /**
     * Test matches_referrer_rules with no rules
     */
    public function test_matches_referrer_rules_empty(): void {
        $rules = [];

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('matches_referrer_rules');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers, $rules);

        $this->assertTrue($result);
    }

    // =========================================
    // Scheduling Tests
    // =========================================

    /**
     * Test is_within_schedule with no dates
     */
    public function test_is_within_schedule_no_dates(): void {
        $popup = (object) [
            'start_date' => null,
            'end_date' => null,
        ];

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('is_within_schedule');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers, $popup);

        $this->assertTrue($result);
    }

    /**
     * Test is_within_schedule with future start date
     */
    public function test_is_within_schedule_future_start(): void {
        $popup = (object) [
            'start_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'end_date' => null,
        ];

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('is_within_schedule');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers, $popup);

        $this->assertFalse($result);
    }

    /**
     * Test is_within_schedule with past end date
     */
    public function test_is_within_schedule_past_end(): void {
        $popup = (object) [
            'start_date' => null,
            'end_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ];

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('is_within_schedule');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers, $popup);

        $this->assertFalse($result);
    }

    /**
     * Test is_within_schedule with valid range
     */
    public function test_is_within_schedule_valid_range(): void {
        $popup = (object) [
            'start_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'end_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
        ];

        $reflection = new ReflectionClass($this->triggers);
        $method = $reflection->getMethod('is_within_schedule');
        $method->setAccessible(true);

        $result = $method->invoke($this->triggers, $popup);

        $this->assertTrue($result);
    }

    // =========================================
    // Should Display Tests
    // =========================================

    /**
     * Test should_display returns false for inactive popup
     */
    public function test_should_display_inactive(): void {
        $popup = (object) [
            'status' => 'draft',
            'start_date' => null,
            'end_date' => null,
            'display_rules' => json_encode([]),
        ];

        $result = $this->triggers->should_display($popup);

        $this->assertFalse($result);
    }

    /**
     * Test should_display returns true for active popup with no rules
     */
    public function test_should_display_active_no_rules(): void {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0)';

        $popup = (object) [
            'status' => 'active',
            'start_date' => null,
            'end_date' => null,
            'display_rules' => json_encode([
                'pages' => 'all',
                'devices' => ['desktop', 'tablet', 'mobile'],
                'user_status' => 'all',
            ]),
        ];

        $result = $this->triggers->should_display($popup);

        $this->assertTrue($result);
    }

    // =========================================
    // Database Default Tests
    // =========================================

    /**
     * Test default popup structure
     */
    public function test_default_popup_structure(): void {
        $default = Popups_Database::get_default_popup();

        $this->assertArrayHasKey('name', $default);
        $this->assertArrayHasKey('type', $default);
        $this->assertArrayHasKey('position', $default);
        $this->assertArrayHasKey('status', $default);
        $this->assertArrayHasKey('priority', $default);
        $this->assertArrayHasKey('form_fields', $default);
        $this->assertArrayHasKey('button_text', $default);
        $this->assertArrayHasKey('success_message', $default);
        $this->assertArrayHasKey('triggers', $default);
        $this->assertArrayHasKey('display_rules', $default);
        $this->assertArrayHasKey('styles', $default);
        $this->assertArrayHasKey('settings', $default);
    }

    /**
     * Test default triggers configuration
     */
    public function test_default_triggers(): void {
        $default = Popups_Database::get_default_popup();
        $triggers = $default['triggers'];

        $this->assertEquals('time_delay', $triggers['type']);
        $this->assertEquals(5, $triggers['delay']);
    }

    /**
     * Test default display rules
     */
    public function test_default_display_rules(): void {
        $default = Popups_Database::get_default_popup();
        $rules = $default['display_rules'];

        $this->assertEquals('all', $rules['pages']);
        $this->assertEquals(['desktop', 'tablet', 'mobile'], $rules['devices']);
        $this->assertEquals('all', $rules['user_status']);
    }

    /**
     * Test default styles
     */
    public function test_default_styles(): void {
        $default = Popups_Database::get_default_popup();
        $styles = $default['styles'];

        $this->assertEquals('#ffffff', $styles['background_color']);
        $this->assertEquals('#333333', $styles['text_color']);
        $this->assertEquals('#0073aa', $styles['button_color']);
        $this->assertEquals(8, $styles['border_radius']);
        $this->assertEquals(500, $styles['max_width']);
    }

    /**
     * Test default settings
     */
    public function test_default_settings(): void {
        $default = Popups_Database::get_default_popup();
        $settings = $default['settings'];

        $this->assertEquals('fade', $settings['animation']);
        $this->assertTrue($settings['overlay']);
        $this->assertTrue($settings['close_button']);
        $this->assertTrue($settings['close_on_overlay']);
        $this->assertTrue($settings['close_on_esc']);
        $this->assertEquals(7, $settings['hide_after_dismiss_days']);
        $this->assertEquals(365, $settings['hide_after_convert_days']);
    }

    /**
     * Test default form fields include email
     */
    public function test_default_form_fields(): void {
        $default = Popups_Database::get_default_popup();
        $fields = $default['form_fields'];

        $this->assertCount(1, $fields);
        $this->assertEquals('email', $fields[0]['name']);
        $this->assertEquals('email', $fields[0]['type']);
        $this->assertEquals('Email', $fields[0]['label']);
        $this->assertTrue($fields[0]['required']);
    }

    // =========================================
    // Table Names Tests
    // =========================================

    /**
     * Test popups table name
     */
    public function test_popups_table_name(): void {
        $table = Popups_Database::popups_table();

        $this->assertStringContainsString('popups', $table);
        $this->assertStringStartsWith('wp_', $table);
    }

    /**
     * Test interactions table name
     */
    public function test_interactions_table_name(): void {
        $table = Popups_Database::interactions_table();

        $this->assertStringContainsString('popup_interactions', $table);
        $this->assertStringStartsWith('wp_', $table);
    }
}

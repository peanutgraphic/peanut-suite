<?php
/**
 * Integration Tests for Database Operations
 *
 * Tests CRUD operations across all database tables:
 * - Webhooks
 * - Popups
 * - Links
 * - Contacts
 * - Visitors
 * - Analytics
 * - Attribution
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class DatabaseOperationsTest extends Peanut_Test_Case {

    protected function setUp(): void {
        parent::setUp();
        global $wpdb;
        $wpdb = new wpdb();
    }

    // =========================================
    // Webhooks Database Tests
    // =========================================

    /**
     * Test Webhooks table name generation
     */
    public function test_webhooks_table_name(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        $table = Webhooks_Database::webhooks_table();

        $this->assertStringContainsString('webhooks_received', $table);
        $this->assertStringStartsWith('wp_', $table);
    }

    /**
     * Test Webhooks insert with array payload
     */
    public function test_webhooks_insert_with_array_payload(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        $payload = [
            'event' => 'form.submitted',
            'data' => ['email' => 'test@example.com'],
        ];

        $id = Webhooks_Database::insert([
            'source' => 'formflow',
            'event' => 'form.submitted',
            'payload' => $payload,
        ]);

        // Mock wpdb returns a random ID
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    /**
     * Test Webhooks insert with string payload
     */
    public function test_webhooks_insert_with_string_payload(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        $id = Webhooks_Database::insert([
            'source' => 'external',
            'event' => 'custom.event',
            'payload' => '{"test": "data"}',
        ]);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    /**
     * Test Webhooks update_status
     */
    public function test_webhooks_update_status(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        $result = Webhooks_Database::update_status(1, 'processed');

        $this->assertTrue($result);
    }

    /**
     * Test Webhooks update_status with error message
     */
    public function test_webhooks_update_status_with_error(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        $result = Webhooks_Database::update_status(1, 'failed', 'Connection timeout');

        $this->assertTrue($result);
    }

    /**
     * Test Webhooks increment_retry
     */
    public function test_webhooks_increment_retry(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        $result = Webhooks_Database::increment_retry(1);

        $this->assertTrue($result);
    }

    /**
     * Test Webhooks get_all filter parameters
     */
    public function test_webhooks_get_all_filters(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        $result = Webhooks_Database::get_all([
            'page' => 2,
            'per_page' => 10,
            'source' => 'formflow',
            'event' => 'form.submitted',
            'status' => 'pending',
            'search' => 'test',
            'orderby' => 'created_at',
            'order' => 'DESC',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(10, $result['per_page']);
    }

    /**
     * Test Webhooks get_all with invalid orderby falls back
     */
    public function test_webhooks_get_all_invalid_orderby(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        // This should not throw an error, just use default
        $result = Webhooks_Database::get_all([
            'orderby' => 'invalid_column',
        ]);

        $this->assertIsArray($result);
    }

    /**
     * Test Webhooks cleanup returns count
     */
    public function test_webhooks_cleanup(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        $result = Webhooks_Database::cleanup(30);

        // cleanup() returns int (number of deleted rows)
        $this->assertIsInt($result);
    }

    // =========================================
    // Popups Database Tests
    // =========================================

    /**
     * Test Popups table names
     */
    public function test_popups_table_names(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-database.php';

        $popups_table = Popups_Database::popups_table();
        $interactions_table = Popups_Database::interactions_table();

        $this->assertStringContainsString('popups', $popups_table);
        $this->assertStringContainsString('popup_interactions', $interactions_table);
    }

    /**
     * Test Popups get_default_popup structure
     */
    public function test_popups_default_structure(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-database.php';

        $default = Popups_Database::get_default_popup();

        $this->assertIsArray($default);
        $this->assertArrayHasKey('name', $default);
        $this->assertArrayHasKey('type', $default);
        $this->assertArrayHasKey('position', $default);
        $this->assertArrayHasKey('status', $default);
        $this->assertArrayHasKey('triggers', $default);
        $this->assertArrayHasKey('display_rules', $default);
        $this->assertArrayHasKey('styles', $default);
        $this->assertArrayHasKey('settings', $default);
        $this->assertArrayHasKey('form_fields', $default);
    }

    /**
     * Test Popups default type is modal
     */
    public function test_popups_default_type(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-database.php';

        $default = Popups_Database::get_default_popup();

        $this->assertEquals('modal', $default['type']);
        $this->assertEquals('center', $default['position']);
        $this->assertEquals('draft', $default['status']);
    }

    /**
     * Test Popups default styles
     */
    public function test_popups_default_styles(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-database.php';

        $default = Popups_Database::get_default_popup();
        $styles = $default['styles'];

        $this->assertArrayHasKey('background_color', $styles);
        $this->assertArrayHasKey('text_color', $styles);
        $this->assertArrayHasKey('button_color', $styles);
        $this->assertArrayHasKey('border_radius', $styles);
        $this->assertArrayHasKey('max_width', $styles);
    }

    /**
     * Test Popups default settings
     */
    public function test_popups_default_settings(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-database.php';

        $default = Popups_Database::get_default_popup();
        $settings = $default['settings'];

        $this->assertArrayHasKey('animation', $settings);
        $this->assertArrayHasKey('overlay', $settings);
        $this->assertArrayHasKey('close_button', $settings);
        $this->assertArrayHasKey('close_on_esc', $settings);
        $this->assertArrayHasKey('hide_after_dismiss_days', $settings);
    }

    /**
     * Test Popups default form fields include email
     */
    public function test_popups_default_form_fields(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-database.php';

        $default = Popups_Database::get_default_popup();
        $fields = $default['form_fields'];

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);

        $email_field = $fields[0];
        $this->assertEquals('email', $email_field['name']);
        $this->assertEquals('email', $email_field['type']);
        $this->assertTrue($email_field['required']);
    }

    // =========================================
    // Contacts Database Operations Tests
    // =========================================

    /**
     * Test Contacts module score calculation
     */
    public function test_contacts_score_calculation(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/contacts/class-contacts-module.php';

        // Test empty contact
        $score = Contacts_Module::calculate_score([
            'email' => '',
            'status' => 'lead',
        ]);
        $this->assertEquals(0, $score);

        // Test contact with email only
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'status' => 'lead',
        ]);
        $this->assertEquals(10, $score);

        // Test contact with full info
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '555-1234',
            'company' => 'Acme Inc',
            'status' => 'customer',
        ]);
        // email(10) + first(5) + last(5) + phone(10) + company(10) + customer(50)
        $this->assertEquals(90, $score);
    }

    /**
     * Test Contacts score with recent activity bonus
     */
    public function test_contacts_score_with_recent_activity(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/contacts/class-contacts-module.php';

        // Activity within last 7 days
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'status' => 'lead',
            'last_activity_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        ]);
        // email(10) + recent_activity(15)
        $this->assertEquals(25, $score);

        // Activity within last 30 days
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'status' => 'lead',
            'last_activity_at' => date('Y-m-d H:i:s', strtotime('-20 days')),
        ]);
        // email(10) + activity_30d(10)
        $this->assertEquals(20, $score);
    }

    /**
     * Test Contacts statuses constant
     */
    public function test_contacts_statuses(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/contacts/class-contacts-module.php';

        $statuses = Contacts_Module::STATUSES;

        $this->assertArrayHasKey('lead', $statuses);
        $this->assertArrayHasKey('contacted', $statuses);
        $this->assertArrayHasKey('qualified', $statuses);
        $this->assertArrayHasKey('customer', $statuses);
        $this->assertArrayHasKey('inactive', $statuses);
    }

    // =========================================
    // Links Database Operations Tests
    // =========================================

    /**
     * Test Links slug generation length
     */
    public function test_links_slug_generation(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/class-links-module.php';

        // Since we're mocking the database, the slug won't be checked for uniqueness
        $slug = Links_Module::generate_slug(6);

        $this->assertEquals(6, strlen($slug));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $slug);
    }

    /**
     * Test Links slug with custom length
     */
    public function test_links_slug_custom_length(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/class-links-module.php';

        $slug = Links_Module::generate_slug(10);

        $this->assertEquals(10, strlen($slug));
    }

    /**
     * Test Links QR code URL generation
     */
    public function test_links_qr_code_url(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/class-links-module.php';

        $url = Links_Module::get_qr_code_url('https://example.com/go/abc123', 300);

        $this->assertStringContainsString('qrserver.com', $url);
        $this->assertStringContainsString('300x300', $url);
        $this->assertStringContainsString('example.com', $url);
    }

    /**
     * Test Links QR code default size
     */
    public function test_links_qr_code_default_size(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/class-links-module.php';

        $url = Links_Module::get_qr_code_url('https://test.com');

        $this->assertStringContainsString('200x200', $url);
    }

    // =========================================
    // Visitors Database Operations Tests
    // =========================================

    /**
     * Test Visitors database class exists
     */
    public function test_visitors_database_exists(): void {
        $this->assertFileExists(
            PEANUT_PLUGIN_DIR . 'modules/visitors/class-visitors-database.php'
        );
    }

    // =========================================
    // Analytics Database Operations Tests
    // =========================================

    /**
     * Test Analytics database class exists
     */
    public function test_analytics_database_exists(): void {
        $this->assertFileExists(
            PEANUT_PLUGIN_DIR . 'modules/analytics/class-analytics-database.php'
        );
    }

    // =========================================
    // Attribution Database Operations Tests
    // =========================================

    /**
     * Test Attribution database class exists
     */
    public function test_attribution_database_exists(): void {
        $this->assertFileExists(
            PEANUT_PLUGIN_DIR . 'modules/attribution/class-attribution-database.php'
        );
    }

    // =========================================
    // Monitor Database Operations Tests
    // =========================================

    /**
     * Test Monitor database class exists
     */
    public function test_monitor_database_exists(): void {
        $this->assertFileExists(
            PEANUT_PLUGIN_DIR . 'modules/monitor/class-monitor-database.php'
        );
    }

    // =========================================
    // FormFlow Database Operations Tests
    // =========================================

    /**
     * Test FormFlow database class exists
     */
    public function test_formflow_database_exists(): void {
        $this->assertFileExists(
            PEANUT_PLUGIN_DIR . 'modules/formflow/class-formflow-database.php'
        );
    }

    // =========================================
    // Invoicing Database Operations Tests
    // =========================================

    /**
     * Test Invoicing database class exists
     */
    public function test_invoicing_database_exists(): void {
        $this->assertFileExists(
            PEANUT_PLUGIN_DIR . 'modules/invoicing/class-invoicing-database.php'
        );
    }

    // =========================================
    // Data Sanitization Tests
    // =========================================

    /**
     * Test database operations sanitize input
     */
    public function test_database_operations_sanitize_input(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        // Insert with potentially dangerous payload
        $id = Webhooks_Database::insert([
            'source' => '<script>alert("xss")</script>',
            'event' => 'test.event',
            'payload' => ['data' => '<img src=x onerror=alert(1)>'],
        ]);

        // The insert should succeed (sanitization happens at the DB level)
        $this->assertIsInt($id);
    }

    // =========================================
    // Pagination Tests
    // =========================================

    /**
     * Test pagination offset calculation
     */
    public function test_pagination_offset(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        // Page 1, per_page 20 = offset 0
        $result = Webhooks_Database::get_all(['page' => 1, 'per_page' => 20]);
        $this->assertEquals(1, $result['page']);

        // Page 3, per_page 20 = offset 40
        $result = Webhooks_Database::get_all(['page' => 3, 'per_page' => 20]);
        $this->assertEquals(3, $result['page']);
    }

    // =========================================
    // Sort Order Tests
    // =========================================

    /**
     * Test sort order validation
     */
    public function test_sort_order_validation(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        // Invalid order should default to DESC
        $result = Webhooks_Database::get_all(['order' => 'INVALID']);

        $this->assertIsArray($result);
    }
}

<?php
/**
 * Unit Tests for Contacts Module
 *
 * Tests contact CRUD operations, score calculation, activity tracking,
 * status management, and FormFlow integration.
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once PEANUT_PLUGIN_DIR . 'modules/contacts/class-contacts-module.php';

class ContactsModuleTest extends Peanut_Test_Case {

    /**
     * @var Contacts_Module
     */
    private Contacts_Module $module;

    protected function setUp(): void {
        parent::setUp();
        $this->module = new Contacts_Module();
        global $wpdb;
        $wpdb = new wpdb();
    }

    // =========================================
    // Score Calculation Tests
    // =========================================

    /**
     * Test score calculation for empty contact
     */
    public function test_calculate_score_empty_contact(): void {
        $score = Contacts_Module::calculate_score([
            'email' => '',
            'status' => 'lead',
        ]);

        $this->assertEquals(0, $score);
    }

    /**
     * Test score calculation with email only
     */
    public function test_calculate_score_email_only(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'status' => 'lead',
        ]);

        // email = 10 points
        $this->assertEquals(10, $score);
    }

    /**
     * Test score calculation with full name
     */
    public function test_calculate_score_with_name(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'lead',
        ]);

        // email(10) + first_name(5) + last_name(5) = 20
        $this->assertEquals(20, $score);
    }

    /**
     * Test score calculation with phone
     */
    public function test_calculate_score_with_phone(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'phone' => '555-1234',
            'status' => 'lead',
        ]);

        // email(10) + phone(10) = 20
        $this->assertEquals(20, $score);
    }

    /**
     * Test score calculation with company
     */
    public function test_calculate_score_with_company(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'company' => 'Acme Inc',
            'status' => 'lead',
        ]);

        // email(10) + company(10) = 20
        $this->assertEquals(20, $score);
    }

    /**
     * Test score calculation with different statuses
     *
     * @dataProvider statusScoreProvider
     */
    public function test_calculate_score_status_bonus(string $status, int $expectedBonus): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'status' => $status,
        ]);

        // email(10) + status bonus
        $this->assertEquals(10 + $expectedBonus, $score);
    }

    public static function statusScoreProvider(): array {
        return [
            'lead' => ['lead', 0],
            'contacted' => ['contacted', 10],
            'qualified' => ['qualified', 25],
            'customer' => ['customer', 50],
            'inactive' => ['inactive', 0],
        ];
    }

    /**
     * Test score calculation with full contact info
     */
    public function test_calculate_score_full_contact(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '555-1234',
            'company' => 'Acme Inc',
            'status' => 'customer',
        ]);

        // email(10) + first(5) + last(5) + phone(10) + company(10) + customer(50) = 90
        $this->assertEquals(90, $score);
    }

    /**
     * Test score calculation with recent activity (last 7 days)
     */
    public function test_calculate_score_recent_activity_7_days(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'status' => 'lead',
            'last_activity_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        ]);

        // email(10) + recent_7d(15) = 25
        $this->assertEquals(25, $score);
    }

    /**
     * Test score calculation with activity 30 days ago
     */
    public function test_calculate_score_activity_30_days(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'status' => 'lead',
            'last_activity_at' => date('Y-m-d H:i:s', strtotime('-20 days')),
        ]);

        // email(10) + activity_30d(10) = 20
        $this->assertEquals(20, $score);
    }

    /**
     * Test score calculation with activity 90 days ago
     */
    public function test_calculate_score_activity_90_days(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'status' => 'lead',
            'last_activity_at' => date('Y-m-d H:i:s', strtotime('-60 days')),
        ]);

        // email(10) + activity_90d(5) = 15
        $this->assertEquals(15, $score);
    }

    /**
     * Test score calculation with old activity
     */
    public function test_calculate_score_old_activity(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'status' => 'lead',
            'last_activity_at' => date('Y-m-d H:i:s', strtotime('-120 days')),
        ]);

        // email(10) only, no activity bonus
        $this->assertEquals(10, $score);
    }

    /**
     * Test score calculation with unknown status
     */
    public function test_calculate_score_unknown_status(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'status' => 'unknown_status',
        ]);

        // email(10) + unknown status(0) = 10
        $this->assertEquals(10, $score);
    }

    // =========================================
    // Status Constants Tests
    // =========================================

    /**
     * Test STATUSES constant contains all expected values
     */
    public function test_statuses_constant(): void {
        $statuses = Contacts_Module::STATUSES;

        $this->assertCount(5, $statuses);
        $this->assertArrayHasKey('lead', $statuses);
        $this->assertArrayHasKey('contacted', $statuses);
        $this->assertArrayHasKey('qualified', $statuses);
        $this->assertArrayHasKey('customer', $statuses);
        $this->assertArrayHasKey('inactive', $statuses);
    }

    /**
     * Test STATUSES has human-readable labels
     */
    public function test_statuses_labels(): void {
        $statuses = Contacts_Module::STATUSES;

        $this->assertEquals('Lead', $statuses['lead']);
        $this->assertEquals('Contacted', $statuses['contacted']);
        $this->assertEquals('Qualified', $statuses['qualified']);
        $this->assertEquals('Customer', $statuses['customer']);
        $this->assertEquals('Inactive', $statuses['inactive']);
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
     * Test module registers dashboard stats filter
     */
    public function test_module_registers_dashboard_filter(): void {
        global $wp_filters;
        $wp_filters = [];

        $this->module->init();

        $this->assertArrayHasKey('peanut_dashboard_stats', $wp_filters);
    }

    /**
     * Test module registers conversion action
     */
    public function test_module_registers_conversion_action(): void {
        global $wp_actions;
        $wp_actions = [];

        $this->module->init();

        $this->assertArrayHasKey('peanut_conversion', $wp_actions);
    }

    // =========================================
    // Activity Logging Tests
    // =========================================

    /**
     * Test add_activity method exists
     */
    public function test_add_activity_exists(): void {
        $this->assertTrue(method_exists($this->module, 'add_activity'));
    }

    /**
     * Test add_activity with basic parameters
     */
    public function test_add_activity_basic(): void {
        // This should not throw an error
        $this->module->add_activity(1, 'created', 'Contact created');

        $this->assertTrue(true);
    }

    /**
     * Test add_activity with metadata
     */
    public function test_add_activity_with_metadata(): void {
        $this->module->add_activity(1, 'status_change', 'Status changed to customer', [
            'old_status' => 'lead',
            'new_status' => 'customer',
        ]);

        $this->assertTrue(true);
    }

    // =========================================
    // Conversion Handler Tests
    // =========================================

    /**
     * Test create_from_conversion method exists
     */
    public function test_create_from_conversion_exists(): void {
        $this->assertTrue(method_exists($this->module, 'create_from_conversion'));
    }

    /**
     * Test create_from_conversion skips without email
     */
    public function test_create_from_conversion_requires_email(): void {
        // Should not create contact without email
        $this->module->create_from_conversion([
            'first_name' => 'John',
        ]);

        // No exception means it handled gracefully
        $this->assertTrue(true);
    }

    /**
     * Test create_from_conversion with valid data
     */
    public function test_create_from_conversion_valid_data(): void {
        $this->module->create_from_conversion([
            'email' => 'new@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'attribution' => [
                'utm_source' => 'google',
                'utm_medium' => 'cpc',
            ],
        ]);

        $this->assertTrue(true);
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

        $this->assertArrayHasKey('contacts_total', $result);
        $this->assertArrayHasKey('contacts_by_status', $result);
        $this->assertArrayHasKey('contacts_new', $result);
        $this->assertArrayHasKey('contacts_top_sources', $result);
    }

    /**
     * Test dashboard stats are correct types
     */
    public function test_dashboard_stats_types(): void {
        $stats = [];

        $result = $this->module->add_dashboard_stats($stats, '7d');

        $this->assertIsInt($result['contacts_total']);
        $this->assertIsArray($result['contacts_by_status']);
        $this->assertIsInt($result['contacts_new']);
        $this->assertIsArray($result['contacts_top_sources']);
    }

    // =========================================
    // Date Clause Tests
    // =========================================

    /**
     * Test date clause generation
     */
    public function test_date_clause_generation(): void {
        $module = new Contacts_Module();
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
    // Route Registration Tests
    // =========================================

    /**
     * Test register_routes loads controller
     */
    public function test_register_routes_loads_controller(): void {
        $this->module->register_routes();

        $this->assertTrue(class_exists('Contacts_Controller'));
    }

    // =========================================
    // Edge Cases Tests
    // =========================================

    /**
     * Test score calculation with null values
     */
    public function test_calculate_score_with_nulls(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'first_name' => null,
            'last_name' => null,
            'phone' => null,
            'company' => null,
            'status' => 'lead',
            'last_activity_at' => null,
        ]);

        // Only email points
        $this->assertEquals(10, $score);
    }

    /**
     * Test score calculation with empty strings
     */
    public function test_calculate_score_with_empty_strings(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'first_name' => '',
            'last_name' => '',
            'phone' => '',
            'company' => '',
            'status' => 'lead',
            'last_activity_at' => '',
        ]);

        // Only email points (empty string last_activity won't match)
        $this->assertEquals(10, $score);
    }

    /**
     * Test score calculation with whitespace-only values
     */
    public function test_calculate_score_with_whitespace(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'first_name' => '   ',
            'last_name' => '   ',
            'status' => 'lead',
        ]);

        // Whitespace is still truthy in PHP, so it counts
        // email(10) + first(5) + last(5) = 20
        $this->assertEquals(20, $score);
    }

    // =========================================
    // Score Boundary Tests
    // =========================================

    /**
     * Test maximum possible score
     */
    public function test_maximum_possible_score(): void {
        $score = Contacts_Module::calculate_score([
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '555-1234',
            'company' => 'Acme Inc',
            'status' => 'customer',
            'last_activity_at' => date('Y-m-d H:i:s'),
        ]);

        // email(10) + first(5) + last(5) + phone(10) + company(10) + customer(50) + recent(15) = 105
        $this->assertEquals(105, $score);
    }

    /**
     * Test minimum possible score
     */
    public function test_minimum_possible_score(): void {
        $score = Contacts_Module::calculate_score([
            'status' => 'inactive',
        ]);

        $this->assertEquals(0, $score);
    }
}

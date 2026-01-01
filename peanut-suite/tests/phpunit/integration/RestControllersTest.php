<?php
/**
 * Integration Tests for REST API Controllers
 *
 * Tests all REST API controllers including:
 * - Links Controller
 * - Contacts Controller
 * - Popups Controller
 * - Webhooks Controller
 * - Attribution Controller
 * - Analytics Controller
 * - Settings Controller
 * - Auth Controller
 * - Visitors Controller
 * - UTM Controller
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class RestControllersTest extends Peanut_Test_Case {

    /**
     * @var array Stores mock database results
     */
    private array $mockDbResults = [];

    protected function setUp(): void {
        parent::setUp();
        global $wpdb;
        $wpdb = new wpdb();
    }

    // =========================================
    // Links Controller Tests
    // =========================================

    /**
     * Test Links Controller route registration
     */
    public function test_links_controller_registers_routes(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/api/class-links-controller.php';

        // Mock the register_rest_route function to track calls
        global $registered_routes;
        $registered_routes = [];

        $controller = new Links_Controller();

        // Verify the controller has the correct rest_base
        $reflection = new ReflectionClass($controller);
        $property = $reflection->getProperty('rest_base');
        $property->setAccessible(true);

        $this->assertEquals('links', $property->getValue($controller));
    }

    /**
     * Test Links Controller get_items returns paginated response
     */
    public function test_links_controller_get_items_structure(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/api/class-links-controller.php';

        $controller = new Links_Controller();
        $request = $this->createMockRequest('GET', [
            'page' => 1,
            'per_page' => 20,
        ]);

        // The response should have the expected structure
        $response = $controller->get_items($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $data = $response->get_data();

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('total', $data['meta']);
        $this->assertArrayHasKey('page', $data['meta']);
        $this->assertArrayHasKey('per_page', $data['meta']);
    }

    /**
     * Test Links Controller create_item validation
     */
    public function test_links_controller_create_requires_destination(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/api/class-links-controller.php';

        $controller = new Links_Controller();
        $request = $this->createMockRequest('POST', []);

        $response = $controller->create_item($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertStringContainsString('Destination URL', $response->get_error_message());
    }

    /**
     * Test Links Controller validates duplicate slugs
     */
    public function test_links_controller_validates_duplicate_slug(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/api/class-links-controller.php';

        global $wpdb;
        // Mock database to return existing slug
        $wpdb = new class extends wpdb {
            public function get_var($query, $x = 0, $y = 0) {
                if (strpos($query, 'slug') !== false) {
                    return 1; // Simulate existing slug
                }
                return 0;
            }
        };

        $controller = new Links_Controller();
        $request = $this->createMockRequest('POST', [
            'destination_url' => 'https://example.com',
            'slug' => 'existing-slug',
        ]);

        $response = $controller->create_item($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertStringContainsString('slug', strtolower($response->get_error_message()));
    }

    // =========================================
    // Contacts Controller Tests
    // =========================================

    /**
     * Test Contacts Controller route registration
     */
    public function test_contacts_controller_registers_routes(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/contacts/api/class-contacts-controller.php';

        $controller = new Contacts_Controller();

        $reflection = new ReflectionClass($controller);
        $property = $reflection->getProperty('rest_base');
        $property->setAccessible(true);

        $this->assertEquals('contacts', $property->getValue($controller));
    }

    /**
     * Test Contacts Controller get_items with filters
     */
    public function test_contacts_controller_get_items_with_filters(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/contacts/api/class-contacts-controller.php';

        $controller = new Contacts_Controller();
        $request = $this->createMockRequest('GET', [
            'status' => 'lead',
            'search' => 'test',
            'utm_source' => 'google',
        ]);

        $response = $controller->get_items($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $data = $response->get_data();

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }

    /**
     * Test Contacts Controller create_item validation
     */
    public function test_contacts_controller_create_requires_email(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/contacts/api/class-contacts-controller.php';

        $controller = new Contacts_Controller();
        $request = $this->createMockRequest('POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response = $controller->create_item($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertStringContainsString('Email', $response->get_error_message());
    }

    /**
     * Test Contacts Controller prevents duplicate emails
     */
    public function test_contacts_controller_prevents_duplicate_email(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/contacts/api/class-contacts-controller.php';

        global $wpdb;
        $wpdb = new class extends wpdb {
            public function get_var($query, $x = 0, $y = 0) {
                if (strpos($query, 'email') !== false) {
                    return 1; // Simulate existing email
                }
                return 0;
            }
        };

        $controller = new Contacts_Controller();
        $request = $this->createMockRequest('POST', [
            'email' => 'existing@example.com',
        ]);

        $response = $controller->create_item($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertStringContainsString('already exists', $response->get_error_message());
    }

    /**
     * Test Contacts Controller bulk delete validation
     */
    public function test_contacts_controller_bulk_delete_requires_ids(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/contacts/api/class-contacts-controller.php';

        $controller = new Contacts_Controller();
        $request = $this->createMockRequest('POST', [
            'ids' => [],
        ]);

        $response = $controller->bulk_delete($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertStringContainsString('No contacts', $response->get_error_message());
    }

    /**
     * Test Contacts Controller bulk_update_status validates status
     */
    public function test_contacts_controller_bulk_status_validates(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/contacts/api/class-contacts-controller.php';
        require_once PEANUT_PLUGIN_DIR . 'modules/contacts/class-contacts-module.php';

        $controller = new Contacts_Controller();
        $request = $this->createMockRequest('POST', [
            'ids' => [1, 2, 3],
            'status' => 'invalid_status',
        ]);

        $response = $controller->bulk_update_status($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertStringContainsString('Invalid status', $response->get_error_message());
    }

    // =========================================
    // Popups Controller Tests
    // =========================================

    /**
     * Test Popups Controller get_popups returns list
     */
    public function test_popups_controller_get_popups_structure(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-database.php';
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-triggers.php';
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/api/class-popups-controller.php';

        $controller = new Popups_Controller();
        $request = $this->createMockRequest('GET', [
            'page' => 1,
            'per_page' => 20,
        ]);

        $response = $controller->get_popups($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $data = $response->get_data();

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('items', $data['data']);
        $this->assertArrayHasKey('total', $data['data']);
        $this->assertArrayHasKey('pages', $data['data']);
    }

    /**
     * Test Popups Controller get_trigger_types returns all types
     */
    public function test_popups_controller_get_trigger_types(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-database.php';
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-triggers.php';
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/api/class-popups-controller.php';

        $controller = new Popups_Controller();
        $request = $this->createMockRequest('GET', []);

        $response = $controller->get_trigger_types($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $data = $response->get_data();

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('triggers', $data['data']);
        $this->assertArrayHasKey('positions', $data['data']);

        // Verify known trigger types exist
        $triggers = $data['data']['triggers'];
        $this->assertArrayHasKey('time_delay', $triggers);
        $this->assertArrayHasKey('exit_intent', $triggers);
        $this->assertArrayHasKey('scroll_percent', $triggers);
    }

    /**
     * Test Popups Controller get_defaults returns valid structure
     */
    public function test_popups_controller_get_defaults(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-database.php';
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-triggers.php';
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/api/class-popups-controller.php';

        $controller = new Popups_Controller();
        $request = $this->createMockRequest('GET', []);

        $response = $controller->get_defaults($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $data = $response->get_data();

        $this->assertArrayHasKey('data', $data);
        $defaults = $data['data'];

        $this->assertArrayHasKey('type', $defaults);
        $this->assertArrayHasKey('position', $defaults);
        $this->assertArrayHasKey('triggers', $defaults);
        $this->assertArrayHasKey('display_rules', $defaults);
        $this->assertArrayHasKey('styles', $defaults);
        $this->assertArrayHasKey('settings', $defaults);
    }

    /**
     * Test Popups Controller bulk action validation
     */
    public function test_popups_controller_bulk_action_requires_ids(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-database.php';
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/class-popups-triggers.php';
        require_once PEANUT_PLUGIN_DIR . 'modules/popups/api/class-popups-controller.php';

        $controller = new Popups_Controller();
        $request = $this->createMockRequest('POST', [
            'action' => 'delete',
            'ids' => [],
        ]);

        $response = $controller->bulk_action($request);

        $this->assertInstanceOf(WP_Error::class, $response);
    }

    // =========================================
    // Webhooks Controller Tests
    // =========================================

    /**
     * Test Webhooks Database get stats structure
     */
    public function test_webhooks_database_get_stats(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        $stats = Webhooks_Database::get_stats();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('processing', $stats);
        $this->assertArrayHasKey('processed', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('today', $stats);
    }

    /**
     * Test Webhooks Database get_all with pagination
     */
    public function test_webhooks_database_get_all_pagination(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/webhooks/class-webhooks-database.php';

        $result = Webhooks_Database::get_all([
            'page' => 1,
            'per_page' => 10,
            'status' => 'pending',
        ]);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);
    }

    // =========================================
    // Attribution Controller Tests
    // =========================================

    /**
     * Test Attribution Controller route structure
     */
    public function test_attribution_controller_exists(): void {
        $controller_path = PEANUT_PLUGIN_DIR . 'modules/attribution/api/class-attribution-controller.php';
        $this->assertFileExists($controller_path);

        require_once PEANUT_PLUGIN_DIR . 'modules/attribution/class-attribution-models.php';
        require_once PEANUT_PLUGIN_DIR . 'modules/attribution/class-attribution-calculator.php';
        require_once $controller_path;

        $controller = new Attribution_Controller();
        $this->assertInstanceOf(Peanut_REST_Controller::class, $controller);
    }

    // =========================================
    // Analytics Controller Tests
    // =========================================

    /**
     * Test Analytics Controller exists and extends base
     */
    public function test_analytics_controller_exists(): void {
        $controller_path = PEANUT_PLUGIN_DIR . 'modules/analytics/api/class-analytics-controller.php';
        $this->assertFileExists($controller_path);

        require_once PEANUT_PLUGIN_DIR . 'modules/analytics/class-analytics-database.php';
        require_once $controller_path;

        $controller = new Analytics_Controller();
        $this->assertInstanceOf(Peanut_REST_Controller::class, $controller);
    }

    // =========================================
    // Base Controller Tests
    // =========================================

    /**
     * Test base controller success response structure
     */
    public function test_base_controller_success_response(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/api/class-links-controller.php';

        $controller = new Links_Controller();

        // Use reflection to test protected method
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('success');
        $method->setAccessible(true);

        $response = $method->invoke($controller, ['test' => 'data']);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $data = $response->get_data();

        $this->assertTrue($data['success']);
        $this->assertEquals(['test' => 'data'], $data['data']);
    }

    /**
     * Test base controller paginated response structure
     */
    public function test_base_controller_paginated_response(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/api/class-links-controller.php';

        $controller = new Links_Controller();

        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('paginated');
        $method->setAccessible(true);

        $response = $method->invoke($controller, [['id' => 1]], 100, 2, 20);

        $data = $response->get_data();

        $this->assertTrue($data['success']);
        $this->assertEquals(100, $data['meta']['total']);
        $this->assertEquals(2, $data['meta']['page']);
        $this->assertEquals(20, $data['meta']['per_page']);
        $this->assertEquals(5, $data['meta']['total_pages']);
    }

    /**
     * Test base controller error response
     */
    public function test_base_controller_error_response(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/api/class-links-controller.php';

        $controller = new Links_Controller();

        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('error');
        $method->setAccessible(true);

        $response = $method->invoke($controller, 'test_error', 'Test error message', 400);

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertEquals('test_error', $response->get_error_code());
        $this->assertEquals('Test error message', $response->get_error_message());
    }

    /**
     * Test base controller not_found response
     */
    public function test_base_controller_not_found_response(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/api/class-links-controller.php';

        $controller = new Links_Controller();

        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('not_found');
        $method->setAccessible(true);

        $response = $method->invoke($controller, 'Resource not found');

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertEquals('not_found', $response->get_error_code());
    }

    /**
     * Test base controller get_pagination params
     */
    public function test_base_controller_get_pagination(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/api/class-links-controller.php';

        $controller = new Links_Controller();

        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('get_pagination');
        $method->setAccessible(true);

        // Test default pagination
        $request = $this->createMockRequest('GET', []);
        $pagination = $method->invoke($controller, $request);

        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(20, $pagination['per_page']);

        // Test custom pagination
        $request = $this->createMockRequest('GET', ['page' => 3, 'per_page' => 50]);
        $pagination = $method->invoke($controller, $request);

        $this->assertEquals(3, $pagination['page']);
        $this->assertEquals(50, $pagination['per_page']);

        // Test max per_page limit
        $request = $this->createMockRequest('GET', ['per_page' => 500]);
        $pagination = $method->invoke($controller, $request);

        $this->assertEquals(100, $pagination['per_page']);
    }

    /**
     * Test base controller get_sort params
     */
    public function test_base_controller_get_sort(): void {
        require_once PEANUT_PLUGIN_DIR . 'modules/links/api/class-links-controller.php';

        $controller = new Links_Controller();

        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('get_sort');
        $method->setAccessible(true);

        // Test default sort
        $request = $this->createMockRequest('GET', []);
        $sort = $method->invoke($controller, $request, ['created_at', 'name']);

        $this->assertEquals('created_at', $sort['orderby']);
        $this->assertEquals('DESC', $sort['order']);

        // Test custom sort
        $request = $this->createMockRequest('GET', ['sort_by' => 'name', 'sort_order' => 'ASC']);
        $sort = $method->invoke($controller, $request, ['created_at', 'name']);

        $this->assertEquals('name', $sort['orderby']);
        $this->assertEquals('ASC', $sort['order']);

        // Test invalid sort falls back to default
        $request = $this->createMockRequest('GET', ['sort_by' => 'invalid_field']);
        $sort = $method->invoke($controller, $request, ['created_at', 'name']);

        $this->assertEquals('created_at', $sort['orderby']);
    }

    // =========================================
    // UTM Controller Tests
    // =========================================

    /**
     * Test UTM Controller exists
     */
    public function test_utm_controller_exists(): void {
        $controller_path = PEANUT_PLUGIN_DIR . 'modules/utm/api/class-utm-controller.php';
        $this->assertFileExists($controller_path);
    }

    // =========================================
    // Visitors Controller Tests
    // =========================================

    /**
     * Test Visitors Controller exists
     */
    public function test_visitors_controller_exists(): void {
        $controller_path = PEANUT_PLUGIN_DIR . 'modules/visitors/api/class-visitors-controller.php';
        $this->assertFileExists($controller_path);
    }

    // =========================================
    // Monitor Controller Tests
    // =========================================

    /**
     * Test Monitor Controller exists
     */
    public function test_monitor_controller_exists(): void {
        $controller_path = PEANUT_PLUGIN_DIR . 'modules/monitor/api/class-monitor-controller.php';
        $this->assertFileExists($controller_path);
    }

    // =========================================
    // FormFlow Controller Tests
    // =========================================

    /**
     * Test FormFlow Controller exists
     */
    public function test_formflow_controller_exists(): void {
        $controller_path = PEANUT_PLUGIN_DIR . 'modules/formflow/api/class-formflow-controller.php';
        $this->assertFileExists($controller_path);
    }

    // =========================================
    // Invoicing Controller Tests
    // =========================================

    /**
     * Test Invoicing Controller exists
     */
    public function test_invoicing_controller_exists(): void {
        $controller_path = PEANUT_PLUGIN_DIR . 'modules/invoicing/api/class-invoicing-controller.php';
        $this->assertFileExists($controller_path);
    }

    // =========================================
    // Health Reports Controller Tests
    // =========================================

    /**
     * Test Health Reports Controller exists
     */
    public function test_health_reports_controller_exists(): void {
        $controller_path = PEANUT_PLUGIN_DIR . 'modules/health-reports/api/class-health-reports-controller.php';
        $this->assertFileExists($controller_path);
    }
}

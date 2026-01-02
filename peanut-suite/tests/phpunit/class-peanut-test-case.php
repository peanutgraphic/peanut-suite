<?php
/**
 * Base Test Case for Peanut Suite
 *
 * Provides common functionality for all test classes.
 */

use PHPUnit\Framework\TestCase;

class Peanut_Test_Case extends TestCase {

    /**
     * Set up before each test
     */
    protected function setUp(): void {
        parent::setUp();

        // Reset global state
        global $wp_actions, $wp_filters, $wp_options;
        $wp_actions = [];
        $wp_filters = [];
        $wp_options = [];
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Create a mock user with specific capabilities
     */
    protected function createMockUser(array $capabilities = ['manage_options']): object {
        return (object) [
            'ID' => 1,
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'display_name' => 'Test User',
            'capabilities' => array_fill_keys($capabilities, true),
        ];
    }

    /**
     * Set an option in the mock options array
     */
    protected function setOption(string $key, $value): void {
        global $wp_options;
        $wp_options[$key] = $value;
    }

    /**
     * Get an option from the mock options array
     */
    protected function getOption(string $key, $default = false) {
        global $wp_options;
        return $wp_options[$key] ?? $default;
    }

    /**
     * Assert that an action was added
     */
    protected function assertActionAdded(string $hook, $callback = null): void {
        global $wp_actions;
        $this->assertArrayHasKey($hook, $wp_actions, "Action '$hook' was not added.");

        if ($callback !== null) {
            $found = false;
            foreach ($wp_actions[$hook] as $action) {
                if ($action['callback'] === $callback) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Callback was not registered for action '$hook'.");
        }
    }

    /**
     * Assert that a filter was added
     */
    protected function assertFilterAdded(string $hook, $callback = null): void {
        global $wp_filters;
        $this->assertArrayHasKey($hook, $wp_filters, "Filter '$hook' was not added.");

        if ($callback !== null) {
            $found = false;
            foreach ($wp_filters[$hook] as $filter) {
                if ($filter['callback'] === $callback) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Callback was not registered for filter '$hook'.");
        }
    }

    /**
     * Create a mock REST request
     */
    protected function createMockRequest(string $method, array $params = []): WP_REST_Request {
        $request = new WP_REST_Request($method, '/peanut/v1/test');
        foreach ($params as $key => $value) {
            $request->set_param($key, $value);
        }
        return $request;
    }

    /**
     * Create a mock REST response
     */
    protected function createMockResponse($data, int $status = 200): WP_REST_Response {
        return new WP_REST_Response($data, $status);
    }

    /**
     * Assert JSON response structure
     */
    protected function assertJsonResponseSuccess($response): void {
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }

    /**
     * Assert JSON response error
     */
    protected function assertJsonResponseError($response): void {
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
    }
}

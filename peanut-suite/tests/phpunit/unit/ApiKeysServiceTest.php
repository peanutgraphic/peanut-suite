<?php
/**
 * Unit tests for Peanut_Api_Keys_Service class
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once PEANUT_PLUGIN_DIR . 'core/services/class-peanut-api-keys-service.php';

class ApiKeysServiceTest extends Peanut_Test_Case {

    /**
     * Test that SCOPES constant contains expected scopes
     */
    public function test_scopes_constant_contains_expected_scopes(): void {
        $scopes = Peanut_Api_Keys_Service::SCOPES;

        $this->assertContains('links:read', $scopes);
        $this->assertContains('links:write', $scopes);
        $this->assertContains('utms:read', $scopes);
        $this->assertContains('utms:write', $scopes);
        $this->assertContains('contacts:read', $scopes);
        $this->assertContains('contacts:write', $scopes);
        $this->assertContains('analytics:read', $scopes);
    }

    /**
     * Test has_scope returns true for valid scope
     */
    public function test_has_scope_returns_true_for_valid_scope(): void {
        $key_data = [
            'scopes' => ['links:read', 'links:write', 'contacts:read'],
        ];

        $this->assertTrue(Peanut_Api_Keys_Service::has_scope($key_data, 'links:read'));
        $this->assertTrue(Peanut_Api_Keys_Service::has_scope($key_data, 'links:write'));
        $this->assertTrue(Peanut_Api_Keys_Service::has_scope($key_data, 'contacts:read'));
    }

    /**
     * Test has_scope returns false for invalid scope
     */
    public function test_has_scope_returns_false_for_invalid_scope(): void {
        $key_data = [
            'scopes' => ['links:read', 'links:write'],
        ];

        $this->assertFalse(Peanut_Api_Keys_Service::has_scope($key_data, 'contacts:read'));
        $this->assertFalse(Peanut_Api_Keys_Service::has_scope($key_data, 'contacts:write'));
        $this->assertFalse(Peanut_Api_Keys_Service::has_scope($key_data, 'analytics:read'));
    }

    /**
     * Test has_scope returns false for empty scopes
     */
    public function test_has_scope_returns_false_for_empty_scopes(): void {
        $key_data = [
            'scopes' => [],
        ];

        $this->assertFalse(Peanut_Api_Keys_Service::has_scope($key_data, 'links:read'));
    }

    /**
     * Test has_scope is case sensitive
     */
    public function test_has_scope_is_case_sensitive(): void {
        $key_data = [
            'scopes' => ['links:read'],
        ];

        $this->assertTrue(Peanut_Api_Keys_Service::has_scope($key_data, 'links:read'));
        $this->assertFalse(Peanut_Api_Keys_Service::has_scope($key_data, 'Links:Read'));
        $this->assertFalse(Peanut_Api_Keys_Service::has_scope($key_data, 'LINKS:READ'));
    }

    /**
     * Test scope validation - read implies only read access
     */
    public function test_read_scope_does_not_imply_write(): void {
        $key_data = [
            'scopes' => ['links:read'],
        ];

        $this->assertTrue(Peanut_Api_Keys_Service::has_scope($key_data, 'links:read'));
        $this->assertFalse(Peanut_Api_Keys_Service::has_scope($key_data, 'links:write'));
    }

    /**
     * Test write scope does not imply read (separate permissions)
     */
    public function test_write_scope_does_not_imply_read(): void {
        $key_data = [
            'scopes' => ['links:write'],
        ];

        $this->assertFalse(Peanut_Api_Keys_Service::has_scope($key_data, 'links:read'));
        $this->assertTrue(Peanut_Api_Keys_Service::has_scope($key_data, 'links:write'));
    }

    /**
     * Test all available scopes are valid
     */
    public function test_all_scopes_count(): void {
        $scopes = Peanut_Api_Keys_Service::SCOPES;

        // Currently 7 scopes defined
        $this->assertCount(7, $scopes);
    }

    /**
     * Test scope names follow consistent pattern
     */
    public function test_scope_naming_convention(): void {
        foreach (Peanut_Api_Keys_Service::SCOPES as $scope) {
            // Each scope should contain exactly one colon
            $this->assertEquals(1, substr_count($scope, ':'), "Scope '$scope' should have exactly one colon");

            // Each scope should be lowercase
            $this->assertEquals(strtolower($scope), $scope, "Scope '$scope' should be lowercase");

            // Each scope should end with :read or :write
            $this->assertTrue(
                str_ends_with($scope, ':read') || str_ends_with($scope, ':write'),
                "Scope '$scope' should end with :read or :write"
            );
        }
    }

    /**
     * Test resource types extracted from scopes
     */
    public function test_scope_resource_types(): void {
        $resources = [];
        foreach (Peanut_Api_Keys_Service::SCOPES as $scope) {
            [$resource, $action] = explode(':', $scope);
            $resources[$resource] = true;
        }

        $this->assertArrayHasKey('links', $resources);
        $this->assertArrayHasKey('utms', $resources);
        $this->assertArrayHasKey('contacts', $resources);
        $this->assertArrayHasKey('analytics', $resources);
    }

    /**
     * Test has_scope with multiple scopes
     */
    public function test_has_scope_with_all_scopes(): void {
        $key_data = [
            'scopes' => Peanut_Api_Keys_Service::SCOPES,
        ];

        foreach (Peanut_Api_Keys_Service::SCOPES as $scope) {
            $this->assertTrue(
                Peanut_Api_Keys_Service::has_scope($key_data, $scope),
                "Key with all scopes should have scope '$scope'"
            );
        }
    }

    /**
     * Test has_scope with non-existent scope
     */
    public function test_has_scope_with_nonexistent_scope(): void {
        $key_data = [
            'scopes' => Peanut_Api_Keys_Service::SCOPES,
        ];

        $this->assertFalse(Peanut_Api_Keys_Service::has_scope($key_data, 'nonexistent:read'));
        $this->assertFalse(Peanut_Api_Keys_Service::has_scope($key_data, 'admin:write'));
        $this->assertFalse(Peanut_Api_Keys_Service::has_scope($key_data, ''));
    }
}

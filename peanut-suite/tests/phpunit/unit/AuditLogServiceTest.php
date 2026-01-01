<?php
/**
 * Unit tests for Peanut_Audit_Log_Service class
 *
 * Tests action constants, resource types, and pure functions.
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once PEANUT_PLUGIN_DIR . 'core/services/class-peanut-audit-log-service.php';

class AuditLogServiceTest extends Peanut_Test_Case {

    // =========================================
    // Action Constants Tests
    // =========================================

    /**
     * Test action constants are defined correctly
     */
    public function test_action_constants_defined(): void {
        $this->assertEquals('create', Peanut_Audit_Log_Service::ACTION_CREATE);
        $this->assertEquals('update', Peanut_Audit_Log_Service::ACTION_UPDATE);
        $this->assertEquals('delete', Peanut_Audit_Log_Service::ACTION_DELETE);
        $this->assertEquals('login', Peanut_Audit_Log_Service::ACTION_LOGIN);
        $this->assertEquals('logout', Peanut_Audit_Log_Service::ACTION_LOGOUT);
        $this->assertEquals('invite', Peanut_Audit_Log_Service::ACTION_INVITE);
        $this->assertEquals('revoke', Peanut_Audit_Log_Service::ACTION_REVOKE);
        $this->assertEquals('export', Peanut_Audit_Log_Service::ACTION_EXPORT);
        $this->assertEquals('access_denied', Peanut_Audit_Log_Service::ACTION_ACCESS_DENIED);
        $this->assertEquals('rate_limited', Peanut_Audit_Log_Service::ACTION_RATE_LIMITED);
    }

    // =========================================
    // Resource Constants Tests
    // =========================================

    /**
     * Test resource type constants are defined correctly
     */
    public function test_resource_constants_defined(): void {
        $this->assertEquals('account', Peanut_Audit_Log_Service::RESOURCE_ACCOUNT);
        $this->assertEquals('member', Peanut_Audit_Log_Service::RESOURCE_MEMBER);
        $this->assertEquals('api_key', Peanut_Audit_Log_Service::RESOURCE_API_KEY);
        $this->assertEquals('link', Peanut_Audit_Log_Service::RESOURCE_LINK);
        $this->assertEquals('utm', Peanut_Audit_Log_Service::RESOURCE_UTM);
        $this->assertEquals('contact', Peanut_Audit_Log_Service::RESOURCE_CONTACT);
        $this->assertEquals('settings', Peanut_Audit_Log_Service::RESOURCE_SETTINGS);
    }

    // =========================================
    // Available Actions Tests
    // =========================================

    /**
     * Test get_available_actions returns array
     */
    public function test_get_available_actions_returns_array(): void {
        $actions = Peanut_Audit_Log_Service::get_available_actions();

        $this->assertIsArray($actions);
        $this->assertNotEmpty($actions);
    }

    /**
     * Test get_available_actions contains all action constants
     */
    public function test_get_available_actions_contains_all_actions(): void {
        $actions = Peanut_Audit_Log_Service::get_available_actions();

        $this->assertContains(Peanut_Audit_Log_Service::ACTION_CREATE, $actions);
        $this->assertContains(Peanut_Audit_Log_Service::ACTION_UPDATE, $actions);
        $this->assertContains(Peanut_Audit_Log_Service::ACTION_DELETE, $actions);
        $this->assertContains(Peanut_Audit_Log_Service::ACTION_LOGIN, $actions);
        $this->assertContains(Peanut_Audit_Log_Service::ACTION_LOGOUT, $actions);
        $this->assertContains(Peanut_Audit_Log_Service::ACTION_INVITE, $actions);
        $this->assertContains(Peanut_Audit_Log_Service::ACTION_REVOKE, $actions);
        $this->assertContains(Peanut_Audit_Log_Service::ACTION_EXPORT, $actions);
        $this->assertContains(Peanut_Audit_Log_Service::ACTION_ACCESS_DENIED, $actions);
        $this->assertContains(Peanut_Audit_Log_Service::ACTION_RATE_LIMITED, $actions);
    }

    /**
     * Test get_available_actions returns exactly 10 actions
     */
    public function test_get_available_actions_count(): void {
        $actions = Peanut_Audit_Log_Service::get_available_actions();

        $this->assertCount(10, $actions);
    }

    // =========================================
    // Available Resource Types Tests
    // =========================================

    /**
     * Test get_available_resource_types returns array
     */
    public function test_get_available_resource_types_returns_array(): void {
        $types = Peanut_Audit_Log_Service::get_available_resource_types();

        $this->assertIsArray($types);
        $this->assertNotEmpty($types);
    }

    /**
     * Test get_available_resource_types contains all resource constants
     */
    public function test_get_available_resource_types_contains_all_types(): void {
        $types = Peanut_Audit_Log_Service::get_available_resource_types();

        $this->assertContains(Peanut_Audit_Log_Service::RESOURCE_ACCOUNT, $types);
        $this->assertContains(Peanut_Audit_Log_Service::RESOURCE_MEMBER, $types);
        $this->assertContains(Peanut_Audit_Log_Service::RESOURCE_API_KEY, $types);
        $this->assertContains(Peanut_Audit_Log_Service::RESOURCE_LINK, $types);
        $this->assertContains(Peanut_Audit_Log_Service::RESOURCE_UTM, $types);
        $this->assertContains(Peanut_Audit_Log_Service::RESOURCE_CONTACT, $types);
        $this->assertContains(Peanut_Audit_Log_Service::RESOURCE_SETTINGS, $types);
    }

    /**
     * Test get_available_resource_types returns exactly 7 types
     */
    public function test_get_available_resource_types_count(): void {
        $types = Peanut_Audit_Log_Service::get_available_resource_types();

        $this->assertCount(7, $types);
    }

    // =========================================
    // Action Type Categorization Tests
    // =========================================

    /**
     * Test CRUD actions are present
     */
    public function test_crud_actions_available(): void {
        $actions = Peanut_Audit_Log_Service::get_available_actions();

        // Core CRUD
        $this->assertContains('create', $actions);
        $this->assertContains('update', $actions);
        $this->assertContains('delete', $actions);
    }

    /**
     * Test authentication actions are present
     */
    public function test_auth_actions_available(): void {
        $actions = Peanut_Audit_Log_Service::get_available_actions();

        // Auth actions
        $this->assertContains('login', $actions);
        $this->assertContains('logout', $actions);
    }

    /**
     * Test team management actions are present
     */
    public function test_team_actions_available(): void {
        $actions = Peanut_Audit_Log_Service::get_available_actions();

        // Team actions
        $this->assertContains('invite', $actions);
        $this->assertContains('revoke', $actions);
    }

    /**
     * Test security-related actions are present
     */
    public function test_security_actions_available(): void {
        $actions = Peanut_Audit_Log_Service::get_available_actions();

        // Security actions
        $this->assertContains('access_denied', $actions);
        $this->assertContains('rate_limited', $actions);
    }

    // =========================================
    // Resource Type Categorization Tests
    // =========================================

    /**
     * Test account-related resource types are present
     */
    public function test_account_resource_types_available(): void {
        $types = Peanut_Audit_Log_Service::get_available_resource_types();

        $this->assertContains('account', $types);
        $this->assertContains('member', $types);
        $this->assertContains('api_key', $types);
        $this->assertContains('settings', $types);
    }

    /**
     * Test marketing resource types are present
     */
    public function test_marketing_resource_types_available(): void {
        $types = Peanut_Audit_Log_Service::get_available_resource_types();

        $this->assertContains('link', $types);
        $this->assertContains('utm', $types);
        $this->assertContains('contact', $types);
    }

    // =========================================
    // Consistency Tests
    // =========================================

    /**
     * Test all action strings are lowercase
     */
    public function test_action_strings_are_lowercase(): void {
        $actions = Peanut_Audit_Log_Service::get_available_actions();

        foreach ($actions as $action) {
            $this->assertEquals(
                strtolower($action),
                $action,
                "Action '$action' should be lowercase"
            );
        }
    }

    /**
     * Test all resource type strings are lowercase
     */
    public function test_resource_type_strings_are_lowercase(): void {
        $types = Peanut_Audit_Log_Service::get_available_resource_types();

        foreach ($types as $type) {
            $this->assertEquals(
                strtolower($type),
                $type,
                "Resource type '$type' should be lowercase"
            );
        }
    }

    /**
     * Test action strings use underscores for multi-word
     */
    public function test_action_strings_use_underscores(): void {
        $this->assertStringContainsString('_', Peanut_Audit_Log_Service::ACTION_ACCESS_DENIED);
        $this->assertStringContainsString('_', Peanut_Audit_Log_Service::ACTION_RATE_LIMITED);
    }

    /**
     * Test resource type strings use underscores for multi-word
     */
    public function test_resource_type_strings_use_underscores(): void {
        $this->assertStringContainsString('_', Peanut_Audit_Log_Service::RESOURCE_API_KEY);
    }

    // =========================================
    // Uniqueness Tests
    // =========================================

    /**
     * Test all actions are unique
     */
    public function test_actions_are_unique(): void {
        $actions = Peanut_Audit_Log_Service::get_available_actions();
        $unique = array_unique($actions);

        $this->assertCount(count($actions), $unique, 'Actions should be unique');
    }

    /**
     * Test all resource types are unique
     */
    public function test_resource_types_are_unique(): void {
        $types = Peanut_Audit_Log_Service::get_available_resource_types();
        $unique = array_unique($types);

        $this->assertCount(count($types), $unique, 'Resource types should be unique');
    }
}

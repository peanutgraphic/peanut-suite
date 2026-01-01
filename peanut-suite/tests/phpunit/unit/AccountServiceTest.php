<?php
/**
 * Unit tests for Peanut_Account_Service class
 *
 * Tests role hierarchy, tier limits, and permission logic.
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once PEANUT_PLUGIN_DIR . 'core/services/class-peanut-account-service.php';

class AccountServiceTest extends Peanut_Test_Case {

    // =========================================
    // Constants Tests
    // =========================================

    /**
     * Test status constants are defined
     */
    public function test_status_constants_defined(): void {
        $this->assertEquals('active', Peanut_Account_Service::STATUS_ACTIVE);
        $this->assertEquals('suspended', Peanut_Account_Service::STATUS_SUSPENDED);
        $this->assertEquals('cancelled', Peanut_Account_Service::STATUS_CANCELLED);
    }

    /**
     * Test role constants are defined
     */
    public function test_role_constants_defined(): void {
        $this->assertEquals('owner', Peanut_Account_Service::ROLE_OWNER);
        $this->assertEquals('admin', Peanut_Account_Service::ROLE_ADMIN);
        $this->assertEquals('member', Peanut_Account_Service::ROLE_MEMBER);
        $this->assertEquals('viewer', Peanut_Account_Service::ROLE_VIEWER);
    }

    // =========================================
    // Feature Configuration Tests
    // =========================================

    /**
     * Test features constant is defined
     */
    public function test_features_constant_defined(): void {
        $features = Peanut_Account_Service::FEATURES;

        $this->assertIsArray($features);
        $this->assertNotEmpty($features);
    }

    /**
     * Test each feature has required keys
     *
     * @dataProvider featureProvider
     */
    public function test_feature_has_required_keys(string $feature): void {
        $features = Peanut_Account_Service::FEATURES;

        $this->assertArrayHasKey($feature, $features);
        $this->assertArrayHasKey('name', $features[$feature]);
        $this->assertArrayHasKey('tier', $features[$feature]);
    }

    public static function featureProvider(): array {
        return [
            'utm' => ['utm'],
            'links' => ['links'],
            'contacts' => ['contacts'],
            'webhooks' => ['webhooks'],
            'visitors' => ['visitors'],
            'attribution' => ['attribution'],
            'analytics' => ['analytics'],
            'popups' => ['popups'],
            'monitor' => ['monitor'],
        ];
    }

    /**
     * Test free tier features
     */
    public function test_free_tier_features(): void {
        $features = Peanut_Account_Service::FEATURES;

        $free_features = array_filter($features, fn($f) => $f['tier'] === 'free');

        $this->assertArrayHasKey('utm', $free_features);
        $this->assertArrayHasKey('links', $free_features);
        $this->assertArrayHasKey('contacts', $free_features);
        $this->assertArrayHasKey('webhooks', $free_features);
    }

    /**
     * Test pro tier features
     */
    public function test_pro_tier_features(): void {
        $features = Peanut_Account_Service::FEATURES;

        $pro_features = array_filter($features, fn($f) => $f['tier'] === 'pro');

        $this->assertArrayHasKey('visitors', $pro_features);
        $this->assertArrayHasKey('attribution', $pro_features);
        $this->assertArrayHasKey('analytics', $pro_features);
        $this->assertArrayHasKey('popups', $pro_features);
    }

    /**
     * Test agency tier features
     */
    public function test_agency_tier_features(): void {
        $features = Peanut_Account_Service::FEATURES;

        $agency_features = array_filter($features, fn($f) => $f['tier'] === 'agency');

        $this->assertArrayHasKey('monitor', $agency_features);
    }

    // =========================================
    // Tier Max Users Tests
    // =========================================

    /**
     * Test get_max_users_for_tier
     *
     * @dataProvider tierMaxUsersProvider
     */
    public function test_get_max_users_for_tier(string $tier, int $expected): void {
        $result = Peanut_Account_Service::get_max_users_for_tier($tier);
        $this->assertEquals($expected, $result);
    }

    public static function tierMaxUsersProvider(): array {
        return [
            'free tier' => ['free', 3],
            'pro tier' => ['pro', 10],
            'agency tier' => ['agency', 50],
            'unknown tier defaults to 3' => ['unknown', 3],
        ];
    }

    // =========================================
    // Default Permissions Tests
    // =========================================

    /**
     * Test get_default_permissions_for_role returns array
     *
     * @dataProvider roleProvider
     */
    public function test_get_default_permissions_returns_array(string $role): void {
        $permissions = Peanut_Account_Service::get_default_permissions_for_role($role);

        $this->assertIsArray($permissions);
        $this->assertNotEmpty($permissions);
    }

    public static function roleProvider(): array {
        return [
            'owner' => ['owner'],
            'admin' => ['admin'],
            'member' => ['member'],
            'viewer' => ['viewer'],
        ];
    }

    /**
     * Test owner has all permissions
     */
    public function test_owner_has_all_permissions(): void {
        $permissions = Peanut_Account_Service::get_default_permissions_for_role('owner');

        foreach (array_keys(Peanut_Account_Service::FEATURES) as $feature) {
            $this->assertArrayHasKey($feature, $permissions);
            $this->assertTrue($permissions[$feature]['access']);
        }
    }

    /**
     * Test admin has all permissions
     */
    public function test_admin_has_all_permissions(): void {
        $permissions = Peanut_Account_Service::get_default_permissions_for_role('admin');

        foreach (array_keys(Peanut_Account_Service::FEATURES) as $feature) {
            $this->assertArrayHasKey($feature, $permissions);
            $this->assertTrue($permissions[$feature]['access']);
        }
    }

    /**
     * Test member only has free tier permissions
     */
    public function test_member_has_free_tier_permissions(): void {
        $permissions = Peanut_Account_Service::get_default_permissions_for_role('member');

        // Free tier features should be accessible
        $this->assertTrue($permissions['utm']['access']);
        $this->assertTrue($permissions['links']['access']);
        $this->assertTrue($permissions['contacts']['access']);
        $this->assertTrue($permissions['webhooks']['access']);

        // Pro/Agency tier features should NOT be accessible
        $this->assertFalse($permissions['visitors']['access']);
        $this->assertFalse($permissions['attribution']['access']);
        $this->assertFalse($permissions['analytics']['access']);
        $this->assertFalse($permissions['popups']['access']);
        $this->assertFalse($permissions['monitor']['access']);
    }

    /**
     * Test viewer only has free tier permissions
     */
    public function test_viewer_has_free_tier_permissions(): void {
        $permissions = Peanut_Account_Service::get_default_permissions_for_role('viewer');

        // Free tier features should be accessible
        $this->assertTrue($permissions['utm']['access']);
        $this->assertTrue($permissions['links']['access']);

        // Pro/Agency tier features should NOT be accessible
        $this->assertFalse($permissions['visitors']['access']);
        $this->assertFalse($permissions['monitor']['access']);
    }

    // =========================================
    // Available Features Tests
    // =========================================

    /**
     * Test get_available_features for free tier
     */
    public function test_get_available_features_free_tier(): void {
        $features = Peanut_Account_Service::get_available_features('free');

        // Free tier features should be available
        $this->assertTrue($features['utm']['available']);
        $this->assertTrue($features['links']['available']);
        $this->assertTrue($features['contacts']['available']);
        $this->assertTrue($features['webhooks']['available']);

        // Pro features should NOT be available
        $this->assertFalse($features['visitors']['available']);
        $this->assertFalse($features['attribution']['available']);
        $this->assertFalse($features['analytics']['available']);
        $this->assertFalse($features['popups']['available']);

        // Agency features should NOT be available
        $this->assertFalse($features['monitor']['available']);
    }

    /**
     * Test get_available_features for pro tier
     */
    public function test_get_available_features_pro_tier(): void {
        $features = Peanut_Account_Service::get_available_features('pro');

        // Free and Pro tier features should be available
        $this->assertTrue($features['utm']['available']);
        $this->assertTrue($features['visitors']['available']);
        $this->assertTrue($features['attribution']['available']);
        $this->assertTrue($features['analytics']['available']);
        $this->assertTrue($features['popups']['available']);

        // Agency features should NOT be available
        $this->assertFalse($features['monitor']['available']);
    }

    /**
     * Test get_available_features for agency tier
     */
    public function test_get_available_features_agency_tier(): void {
        $features = Peanut_Account_Service::get_available_features('agency');

        // ALL features should be available
        foreach ($features as $feature => $config) {
            $this->assertTrue(
                $config['available'],
                "Feature '$feature' should be available for agency tier"
            );
        }
    }

    /**
     * Test get_available_features includes metadata
     */
    public function test_get_available_features_includes_metadata(): void {
        $features = Peanut_Account_Service::get_available_features('free');

        foreach ($features as $feature => $config) {
            $this->assertArrayHasKey('name', $config);
            $this->assertArrayHasKey('tier', $config);
            $this->assertArrayHasKey('available', $config);
            $this->assertIsBool($config['available']);
        }
    }

    // =========================================
    // Role Hierarchy Tests
    // =========================================

    /**
     * Test role hierarchy order (higher roles have more access)
     */
    public function test_role_hierarchy_order(): void {
        $roles = ['viewer', 'member', 'admin', 'owner'];

        // Each role should have more default permissions than the previous
        // (or equal for owner/admin which both have full access)
        $viewer_perms = Peanut_Account_Service::get_default_permissions_for_role('viewer');
        $member_perms = Peanut_Account_Service::get_default_permissions_for_role('member');
        $admin_perms = Peanut_Account_Service::get_default_permissions_for_role('admin');
        $owner_perms = Peanut_Account_Service::get_default_permissions_for_role('owner');

        // Count accessible features
        $viewer_count = count(array_filter($viewer_perms, fn($p) => $p['access']));
        $member_count = count(array_filter($member_perms, fn($p) => $p['access']));
        $admin_count = count(array_filter($admin_perms, fn($p) => $p['access']));
        $owner_count = count(array_filter($owner_perms, fn($p) => $p['access']));

        // Viewer and member should have same (free tier)
        $this->assertEquals($viewer_count, $member_count);

        // Admin and owner should have same (all access)
        $this->assertEquals($admin_count, $owner_count);

        // Admin/owner should have more than member/viewer
        $this->assertGreaterThan($member_count, $admin_count);
    }

    // =========================================
    // Unknown Input Handling Tests
    // =========================================

    /**
     * Test unknown tier defaults gracefully
     */
    public function test_unknown_tier_defaults(): void {
        $features = Peanut_Account_Service::get_available_features('unknown_tier');

        // Should treat as free tier
        $this->assertTrue($features['utm']['available']);
        $this->assertFalse($features['visitors']['available']);
        $this->assertFalse($features['monitor']['available']);
    }

    /**
     * Test unknown role defaults to member-like permissions
     */
    public function test_unknown_role_defaults(): void {
        $permissions = Peanut_Account_Service::get_default_permissions_for_role('unknown_role');

        // Should get free tier access (like member/viewer)
        $this->assertTrue($permissions['utm']['access']);
        $this->assertFalse($permissions['monitor']['access']);
    }
}

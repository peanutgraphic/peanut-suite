<?php
/**
 * Integration Tests for Module Manager
 *
 * Tests module registration, initialization, activation, and dependencies.
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once PEANUT_PLUGIN_DIR . 'core/admin/class-peanut-module-manager.php';

class ModuleManagerTest extends Peanut_Test_Case {

    /**
     * @var Peanut_Module_Manager
     */
    private Peanut_Module_Manager $manager;

    protected function setUp(): void {
        parent::setUp();
        $this->manager = new Peanut_Module_Manager();
    }

    // =========================================
    // Module Registration Tests
    // =========================================

    /**
     * Test module registration with minimal config
     */
    public function test_register_module_minimal(): void {
        $this->manager->register('test-module', [
            'name' => 'Test Module',
        ]);

        $registered = $this->manager->get_registered();

        $this->assertArrayHasKey('test-module', $registered);
        $this->assertEquals('Test Module', $registered['test-module']['name']);
    }

    /**
     * Test module registration with full config
     */
    public function test_register_module_full_config(): void {
        $this->manager->register('full-module', [
            'name' => 'Full Module',
            'description' => 'A fully configured module',
            'icon' => 'star',
            'file' => '/path/to/module.php',
            'class' => 'Full_Module_Class',
            'default' => true,
            'pro' => false,
            'tier' => 'pro',
            'dependencies' => ['other-module'],
        ]);

        $module = $this->manager->get_module('full-module');

        $this->assertNotNull($module);
        $this->assertEquals('Full Module', $module['name']);
        $this->assertEquals('A fully configured module', $module['description']);
        $this->assertEquals('star', $module['icon']);
        $this->assertEquals('pro', $module['tier']);
        $this->assertTrue($module['default']);
        $this->assertFalse($module['pro']);
        $this->assertEquals(['other-module'], $module['dependencies']);
    }

    /**
     * Test multiple module registration
     */
    public function test_register_multiple_modules(): void {
        $this->manager->register('module-a', ['name' => 'Module A']);
        $this->manager->register('module-b', ['name' => 'Module B']);
        $this->manager->register('module-c', ['name' => 'Module C']);

        $registered = $this->manager->get_registered();

        $this->assertCount(3, $registered);
        $this->assertArrayHasKey('module-a', $registered);
        $this->assertArrayHasKey('module-b', $registered);
        $this->assertArrayHasKey('module-c', $registered);
    }

    /**
     * Test default values are applied
     */
    public function test_register_applies_defaults(): void {
        $this->manager->register('minimal', []);

        $module = $this->manager->get_module('minimal');

        $this->assertEquals('minimal', $module['name']);
        $this->assertEquals('', $module['description']);
        $this->assertEquals('box', $module['icon']);
        $this->assertEquals('', $module['file']);
        $this->assertEquals('', $module['class']);
        $this->assertFalse($module['default']);
        $this->assertFalse($module['pro']);
        $this->assertNull($module['tier']);
        $this->assertEquals([], $module['dependencies']);
    }

    // =========================================
    // Module Activation Tests
    // =========================================

    /**
     * Test module activation
     */
    public function test_activate_module(): void {
        $this->manager->register('activatable', [
            'name' => 'Activatable Module',
        ]);

        $result = $this->manager->activate('activatable');

        $this->assertTrue($result);

        $active = $this->manager->get_active_modules();
        $this->assertTrue($active['activatable']);
    }

    /**
     * Test activating non-existent module fails
     */
    public function test_activate_nonexistent_module_fails(): void {
        $result = $this->manager->activate('nonexistent');

        $this->assertFalse($result);
    }

    /**
     * Test deactivate module
     */
    public function test_deactivate_module(): void {
        $this->manager->register('deactivatable', [
            'name' => 'Deactivatable Module',
            'default' => true,
        ]);

        // First activate
        $this->manager->activate('deactivatable');

        // Then deactivate
        $result = $this->manager->deactivate('deactivatable');

        $this->assertTrue($result);

        $active = $this->manager->get_active_modules();
        $this->assertFalse($active['deactivatable']);
    }

    /**
     * Test activation fires action
     */
    public function test_activate_fires_action(): void {
        global $wp_actions;
        $wp_actions = [];

        $this->manager->register('action-test', [
            'name' => 'Action Test Module',
        ]);

        $this->manager->activate('action-test');

        $this->assertArrayHasKey('peanut_module_activated_action-test', $wp_actions);
        $this->assertArrayHasKey('peanut_module_activated', $wp_actions);
    }

    /**
     * Test deactivation fires action
     */
    public function test_deactivate_fires_action(): void {
        global $wp_actions;
        $wp_actions = [];

        $this->manager->register('deactivate-action', [
            'name' => 'Deactivate Action Module',
        ]);

        $this->manager->deactivate('deactivate-action');

        $this->assertArrayHasKey('peanut_module_deactivated_deactivate-action', $wp_actions);
        $this->assertArrayHasKey('peanut_module_deactivated', $wp_actions);
    }

    // =========================================
    // Active Modules Tests
    // =========================================

    /**
     * Test get_active_modules returns defaults
     */
    public function test_get_active_modules_with_defaults(): void {
        $this->manager->register('default-on', [
            'name' => 'Default On',
            'default' => true,
        ]);

        $this->manager->register('default-off', [
            'name' => 'Default Off',
            'default' => false,
        ]);

        $active = $this->manager->get_active_modules();

        $this->assertTrue($active['default-on']);
        $this->assertFalse($active['default-off']);
    }

    /**
     * Test is_active returns false for unregistered module
     */
    public function test_is_active_unregistered_module(): void {
        $result = $this->manager->is_active('nonexistent');

        $this->assertFalse($result);
    }

    // =========================================
    // Module Info Tests
    // =========================================

    /**
     * Test get_module returns null for nonexistent
     */
    public function test_get_module_nonexistent(): void {
        $module = $this->manager->get_module('nonexistent');

        $this->assertNull($module);
    }

    /**
     * Test get_instance returns null for non-initialized module
     */
    public function test_get_instance_non_initialized(): void {
        $this->manager->register('not-initialized', [
            'name' => 'Not Initialized',
        ]);

        $instance = $this->manager->get_instance('not-initialized');

        $this->assertNull($instance);
    }

    // =========================================
    // Modules for Display Tests
    // =========================================

    /**
     * Test get_modules_for_display structure
     */
    public function test_get_modules_for_display(): void {
        $this->manager->register('display-test', [
            'name' => 'Display Test',
            'description' => 'Test description',
            'icon' => 'test-icon',
            'pro' => false,
            'tier' => null,
        ]);

        $modules = $this->manager->get_modules_for_display();

        $this->assertNotEmpty($modules);

        $module = array_filter($modules, fn($m) => $m['id'] === 'display-test');
        $module = reset($module);

        $this->assertEquals('Display Test', $module['name']);
        $this->assertEquals('Test description', $module['description']);
        $this->assertEquals('test-icon', $module['icon']);
        $this->assertArrayHasKey('active', $module);
        $this->assertArrayHasKey('locked', $module);
        $this->assertArrayHasKey('tier', $module);
    }

    /**
     * Test pro modules show as locked without license
     */
    public function test_pro_modules_locked_without_license(): void {
        // Mock peanut_is_pro to return false
        if (!function_exists('peanut_is_pro')) {
            function peanut_is_pro() {
                return false;
            }
        }

        $this->manager->register('pro-locked', [
            'name' => 'Pro Locked',
            'pro' => true,
        ]);

        $modules = $this->manager->get_modules_for_display();

        $module = array_filter($modules, fn($m) => $m['id'] === 'pro-locked');
        $module = reset($module);

        $this->assertTrue($module['locked']);
        $this->assertTrue($module['pro']);
    }

    // =========================================
    // Tier Requirements Tests
    // =========================================

    /**
     * Test free tier modules are always accessible
     */
    public function test_free_tier_always_accessible(): void {
        $this->manager->register('free-module', [
            'name' => 'Free Module',
            'tier' => null,
        ]);

        $modules = $this->manager->get_modules_for_display();
        $module = array_filter($modules, fn($m) => $m['id'] === 'free-module');
        $module = reset($module);

        $this->assertFalse($module['locked']);
    }

    /**
     * Test get_user_tier returns free by default
     */
    public function test_get_user_tier_default(): void {
        $tier = $this->manager->get_user_tier();

        // When Peanut_License class doesn't exist or returns null
        $this->assertEquals('free', $tier);
    }

    // =========================================
    // Module Initialization Tests
    // =========================================

    /**
     * Test init_modules skips inactive modules
     */
    public function test_init_modules_skips_inactive(): void {
        $this->manager->register('inactive-module', [
            'name' => 'Inactive Module',
            'default' => false,
            'class' => 'Nonexistent_Class',
        ]);

        // This should not throw an error about missing class
        $this->manager->init_modules();

        $this->assertNull($this->manager->get_instance('inactive-module'));
    }

    /**
     * Test module with missing class file doesn't initialize
     */
    public function test_module_missing_class_not_initialized(): void {
        $this->manager->register('missing-class', [
            'name' => 'Missing Class',
            'default' => true,
            'class' => 'Class_That_Does_Not_Exist',
        ]);

        $this->manager->init_modules();

        $this->assertNull($this->manager->get_instance('missing-class'));
    }

    // =========================================
    // Dependencies Tests
    // =========================================

    /**
     * Test module with missing dependency doesn't activate
     */
    public function test_module_missing_dependency(): void {
        $this->manager->register('dependent', [
            'name' => 'Dependent Module',
            'default' => true,
            'dependencies' => ['required-module'],
        ]);

        $this->manager->init_modules();

        // Module should not be active because dependency is missing
        $this->assertFalse($this->manager->is_active('dependent'));
    }

    // =========================================
    // Registration Override Tests
    // =========================================

    /**
     * Test re-registering a module overrides config
     */
    public function test_reregister_module_overrides(): void {
        $this->manager->register('override-test', [
            'name' => 'Original Name',
            'description' => 'Original description',
        ]);

        $this->manager->register('override-test', [
            'name' => 'New Name',
            'description' => 'New description',
        ]);

        $module = $this->manager->get_module('override-test');

        $this->assertEquals('New Name', $module['name']);
        $this->assertEquals('New description', $module['description']);
    }
}

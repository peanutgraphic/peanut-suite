<?php
/**
 * Module Manager
 *
 * Handles registration, activation, and initialization of modules.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Module_Manager {

    /**
     * Registered modules
     */
    private array $registered = [];

    /**
     * Active module instances
     */
    private array $instances = [];

    /**
     * Register a module
     *
     * @param string $id Module identifier
     * @param array $config Module configuration
     */
    public function register(string $id, array $config): void {
        $this->registered[$id] = wp_parse_args($config, [
            'name' => $id,
            'description' => '',
            'icon' => 'box',
            'file' => '',
            'class' => '',
            'default' => false,
            'pro' => false,
            'tier' => null,  // null = free, 'pro', 'agency', 'enterprise'
            'dependencies' => [],
        ]);
    }

    /**
     * Initialize active modules
     */
    public function init_modules(): void {
        $active = $this->get_active_modules();

        foreach ($active as $module_id => $enabled) {
            if (!$enabled) {
                continue;
            }

            $this->init_module($module_id);
        }
    }

    /**
     * Initialize a single module
     */
    private function init_module(string $id): bool {
        if (!isset($this->registered[$id])) {
            return false;
        }

        $config = $this->registered[$id];

        // Check tier requirement
        if (!$this->user_has_tier($config['tier'])) {
            return false;
        }

        // Check if pro module and user has license (legacy check)
        if ($config['pro'] && !peanut_is_pro()) {
            return false;
        }

        // Check dependencies
        foreach ($config['dependencies'] as $dep) {
            if (!$this->is_active($dep)) {
                return false;
            }
        }

        // Load module file
        if (!empty($config['file']) && file_exists($config['file'])) {
            require_once $config['file'];
        }

        // Instantiate module class
        if (!empty($config['class']) && class_exists($config['class'])) {
            $this->instances[$id] = new $config['class']();

            // Call init if method exists
            if (method_exists($this->instances[$id], 'init')) {
                $this->instances[$id]->init();
            }

            return true;
        }

        return false;
    }

    /**
     * Check if user has required tier
     */
    private function user_has_tier(?string $tier): bool {
        if ($tier === null) {
            return true; // Free module
        }

        if (!class_exists('Peanut_License')) {
            return false;
        }

        $license = new Peanut_License();
        $user_tier = $license->get_tier();

        $tier_levels = ['free' => 0, 'pro' => 1, 'agency' => 2, 'enterprise' => 3];
        $required_level = $tier_levels[$tier] ?? 0;
        $user_level = $tier_levels[$user_tier] ?? 0;

        return $user_level >= $required_level;
    }

    /**
     * Get active modules setting
     */
    public function get_active_modules(): array {
        $saved = get_option('peanut_active_modules', []);

        // Merge with defaults
        $defaults = [];
        foreach ($this->registered as $id => $config) {
            $defaults[$id] = $config['default'];
        }

        return wp_parse_args($saved, $defaults);
    }

    /**
     * Check if a module is active
     */
    public function is_active(string $id): bool {
        $active = $this->get_active_modules();
        return !empty($active[$id]) && isset($this->instances[$id]);
    }

    /**
     * Activate a module
     */
    public function activate(string $id): bool {
        if (!isset($this->registered[$id])) {
            return false;
        }

        $config = $this->registered[$id];

        // Check tier requirement
        if (!$this->user_has_tier($config['tier'])) {
            return false;
        }

        // Check pro requirement (legacy)
        if ($config['pro'] && !peanut_is_pro()) {
            return false;
        }

        $active = $this->get_active_modules();
        $active[$id] = true;
        update_option('peanut_active_modules', $active);

        // Run module activation hook
        do_action("peanut_module_activated_{$id}");
        do_action('peanut_module_activated', $id);

        return true;
    }

    /**
     * Deactivate a module
     */
    public function deactivate(string $id): bool {
        $active = $this->get_active_modules();
        $active[$id] = false;
        update_option('peanut_active_modules', $active);

        // Run module deactivation hook
        do_action("peanut_module_deactivated_{$id}");
        do_action('peanut_module_deactivated', $id);

        return true;
    }

    /**
     * Get all registered modules
     */
    public function get_registered(): array {
        return $this->registered;
    }

    /**
     * Get module config
     */
    public function get_module(string $id): ?array {
        return $this->registered[$id] ?? null;
    }

    /**
     * Get module instance
     */
    public function get_instance(string $id): ?object {
        return $this->instances[$id] ?? null;
    }

    /**
     * Get modules for display (with status)
     */
    public function get_modules_for_display(): array {
        $active = $this->get_active_modules();
        $modules = [];

        foreach ($this->registered as $id => $config) {
            $tier = $config['tier'];
            $is_locked = !$this->user_has_tier($tier);

            // Also check legacy pro flag
            if ($config['pro'] && !peanut_is_pro()) {
                $is_locked = true;
            }

            $modules[] = [
                'id' => $id,
                'name' => $config['name'],
                'description' => $config['description'],
                'icon' => $config['icon'],
                'active' => !empty($active[$id]) && !$is_locked,
                'pro' => $config['pro'],
                'tier' => $tier,
                'locked' => $is_locked,
                'required_tier' => $tier ?? ($config['pro'] ? 'pro' : 'free'),
            ];
        }

        return $modules;
    }

    /**
     * Get user's current license tier
     */
    public function get_user_tier(): string {
        if (!class_exists('Peanut_License')) {
            return 'free';
        }

        $license = new Peanut_License();
        return $license->get_tier() ?? 'free';
    }
}

<?php
/**
 * PHPUnit Bootstrap File for Peanut Suite
 *
 * Sets up the WordPress test environment.
 */

// Define test mode
define('PEANUT_TESTING', true);

// Composer autoloader (if available)
if (file_exists(dirname(__DIR__, 2) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
}

// Check for WordPress test library
$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Give access to tests_add_filter() function
if (file_exists($_tests_dir . '/includes/functions.php')) {
    require_once $_tests_dir . '/includes/functions.php';

    /**
     * Manually load the plugin being tested.
     */
    function _manually_load_plugin() {
        require dirname(__DIR__, 2) . '/peanut-suite.php';
    }
    tests_add_filter('muplugins_loaded', '_manually_load_plugin');

    // Start up the WP testing environment
    require $_tests_dir . '/includes/bootstrap.php';
} else {
    // WordPress test library not available, load minimal mocks
    echo "WordPress test library not found. Running with mocks.\n";
    echo "Set WP_TESTS_DIR environment variable or install wp-phpunit.\n\n";

    // Load mock functions for standalone testing
    require_once __DIR__ . '/mocks/wordpress-mocks.php';

    // Load the plugin constants
    define('PEANUT_PLUGIN_DIR', dirname(__DIR__, 2) . '/');
    define('PEANUT_PLUGIN_URL', 'http://example.org/wp-content/plugins/peanut-suite/');
    define('PEANUT_VERSION', '4.2.0');
    define('ABSPATH', '/tmp/wordpress/');
}

// Load base test case
require_once __DIR__ . '/class-peanut-test-case.php';

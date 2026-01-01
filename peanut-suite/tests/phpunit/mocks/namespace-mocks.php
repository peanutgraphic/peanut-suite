<?php
/**
 * Namespaced WordPress Mock Functions
 *
 * Provides mock implementations for WordPress functions used in namespaced code.
 */

namespace PeanutSuite\Attribution {
    if (!function_exists('PeanutSuite\Attribution\wp_parse_url')) {
        function wp_parse_url($url, $component = -1) {
            return \parse_url($url, $component);
        }
    }
}

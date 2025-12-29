<?php
/**
 * Plugin Deactivator
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Deactivator {

    public static function deactivate(): void {
        // Clear scheduled events
        wp_clear_scheduled_hook('peanut_daily_maintenance');

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

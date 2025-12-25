<?php
/**
 * Database Manager
 *
 * Handles table creation and database operations for all modules.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Database {

    /**
     * Database version
     */
    private const DB_VERSION = '1.0.0';

    /**
     * Table names
     */
    public static function table(string $name): string {
        global $wpdb;
        return $wpdb->prefix . PEANUT_TABLE_PREFIX . $name;
    }

    // Shorthand methods
    public static function utms_table(): string { return self::table('utms'); }
    public static function links_table(): string { return self::table('links'); }
    public static function link_clicks_table(): string { return self::table('link_clicks'); }
    public static function contacts_table(): string { return self::table('contacts'); }
    public static function contact_activities_table(): string { return self::table('contact_activities'); }
    public static function tags_table(): string { return self::table('tags'); }
    public static function taggables_table(): string { return self::table('taggables'); }
    public static function analytics_cache_table(): string { return self::table('analytics_cache'); }

    /**
     * Create all tables
     */
    public static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // UTMs table
        $sql = "CREATE TABLE " . self::utms_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            name varchar(255) NOT NULL,
            base_url varchar(2048) NOT NULL,
            utm_source varchar(255) NOT NULL,
            utm_medium varchar(255) NOT NULL,
            utm_campaign varchar(255) NOT NULL,
            utm_term varchar(255) DEFAULT NULL,
            utm_content varchar(255) DEFAULT NULL,
            full_url text NOT NULL,
            click_count bigint(20) UNSIGNED DEFAULT 0,
            notes text DEFAULT NULL,
            is_archived tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY utm_source (utm_source),
            KEY utm_campaign (utm_campaign),
            KEY created_at (created_at),
            KEY is_archived (is_archived)
        ) $charset;";
        dbDelta($sql);

        // Links table
        $sql = "CREATE TABLE " . self::links_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            slug varchar(50) NOT NULL,
            destination_url varchar(2048) NOT NULL,
            title varchar(255) DEFAULT NULL,
            utm_id bigint(20) UNSIGNED DEFAULT NULL,
            click_count bigint(20) UNSIGNED DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            expires_at datetime DEFAULT NULL,
            password_hash varchar(255) DEFAULT NULL,
            qr_code_path varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY user_id (user_id),
            KEY utm_id (utm_id),
            KEY is_active (is_active)
        ) $charset;";
        dbDelta($sql);

        // Link clicks table
        $sql = "CREATE TABLE " . self::link_clicks_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            link_id bigint(20) UNSIGNED NOT NULL,
            visitor_id varchar(64) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            referer varchar(2048) DEFAULT NULL,
            country varchar(2) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            device_type varchar(20) DEFAULT NULL,
            browser varchar(50) DEFAULT NULL,
            os varchar(50) DEFAULT NULL,
            clicked_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY link_id (link_id),
            KEY clicked_at (clicked_at),
            KEY visitor_id (visitor_id)
        ) $charset;";
        dbDelta($sql);

        // Contacts table
        $sql = "CREATE TABLE " . self::contacts_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            email varchar(255) NOT NULL,
            first_name varchar(100) DEFAULT NULL,
            last_name varchar(100) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            company varchar(255) DEFAULT NULL,
            status varchar(50) DEFAULT 'lead',
            source varchar(100) DEFAULT NULL,
            utm_source varchar(255) DEFAULT NULL,
            utm_medium varchar(255) DEFAULT NULL,
            utm_campaign varchar(255) DEFAULT NULL,
            custom_fields text DEFAULT NULL,
            notes text DEFAULT NULL,
            score int DEFAULT 0,
            last_activity_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY email (email),
            KEY status (status),
            KEY source (source),
            KEY utm_campaign (utm_campaign),
            KEY created_at (created_at)
        ) $charset;";
        dbDelta($sql);

        // Contact activities table
        $sql = "CREATE TABLE " . self::contact_activities_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            contact_id bigint(20) UNSIGNED NOT NULL,
            type varchar(50) NOT NULL,
            description text DEFAULT NULL,
            metadata text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY contact_id (contact_id),
            KEY type (type),
            KEY created_at (created_at)
        ) $charset;";
        dbDelta($sql);

        // Tags table (polymorphic)
        $sql = "CREATE TABLE " . self::tags_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            name varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            color varchar(7) DEFAULT '#6366f1',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_slug (user_id, slug),
            KEY user_id (user_id)
        ) $charset;";
        dbDelta($sql);

        // Taggables (polymorphic pivot)
        $sql = "CREATE TABLE " . self::taggables_table() . " (
            tag_id bigint(20) UNSIGNED NOT NULL,
            taggable_id bigint(20) UNSIGNED NOT NULL,
            taggable_type varchar(50) NOT NULL,
            PRIMARY KEY (tag_id, taggable_id, taggable_type),
            KEY taggable (taggable_type, taggable_id)
        ) $charset;";
        dbDelta($sql);

        // Analytics cache
        $sql = "CREATE TABLE " . self::analytics_cache_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            cache_key varchar(64) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            module varchar(50) NOT NULL,
            data longtext NOT NULL,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY cache_key (cache_key),
            KEY expires_at (expires_at),
            KEY user_module (user_id, module)
        ) $charset;";
        dbDelta($sql);

        update_option('peanut_db_version', self::DB_VERSION);
    }

    /**
     * Drop all tables
     */
    public static function drop_tables(): void {
        global $wpdb;

        $tables = [
            self::utms_table(),
            self::links_table(),
            self::link_clicks_table(),
            self::contacts_table(),
            self::contact_activities_table(),
            self::tags_table(),
            self::taggables_table(),
            self::analytics_cache_table(),
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('peanut_db_version');
    }

    /**
     * Cleanup expired cache
     */
    public static function cleanup_expired_cache(): void {
        global $wpdb;
        $table = self::analytics_cache_table();

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table WHERE expires_at < %s",
                current_time('mysql')
            )
        );
    }

    /**
     * Check if tables need upgrade
     */
    public static function needs_upgrade(): bool {
        $current = get_option('peanut_db_version', '0');
        return version_compare($current, self::DB_VERSION, '<');
    }
}

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
    private const DB_VERSION = '2.4.0';

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

    // Multi-tenancy tables
    public static function accounts_table(): string { return self::table('accounts'); }
    public static function account_members_table(): string { return self::table('account_members'); }
    public static function api_keys_table(): string { return self::table('api_keys'); }
    public static function audit_log_table(): string { return self::table('audit_log'); }
    public static function utm_access_table(): string { return self::table('utm_access'); }

    // Projects tables
    public static function projects_table(): string { return self::table('projects'); }
    public static function project_members_table(): string { return self::table('project_members'); }

    // Clients tables
    public static function clients_table(): string { return self::table('clients'); }
    public static function client_contacts_table(): string { return self::table('client_contacts'); }

    // Plesk monitoring tables
    public static function monitor_servers_table(): string { return self::table('monitor_servers'); }
    public static function monitor_server_health_table(): string { return self::table('monitor_server_health'); }

    // Health reports tables
    public static function health_reports_table(): string { return self::table('health_reports'); }
    public static function health_report_settings_table(): string { return self::table('health_report_settings'); }

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
            account_id bigint(20) UNSIGNED DEFAULT NULL,
            project_id bigint(20) UNSIGNED DEFAULT NULL,
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
            KEY account_id (account_id),
            KEY project_id (project_id),
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
            account_id bigint(20) UNSIGNED DEFAULT NULL,
            project_id bigint(20) UNSIGNED DEFAULT NULL,
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
            KEY account_id (account_id),
            KEY project_id (project_id),
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
            account_id bigint(20) UNSIGNED DEFAULT NULL,
            project_id bigint(20) UNSIGNED DEFAULT NULL,
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
            KEY account_id (account_id),
            KEY project_id (project_id),
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

        // ===== Multi-tenancy Tables =====

        // Accounts table
        $sql = "CREATE TABLE " . self::accounts_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            status varchar(20) DEFAULT 'active',
            tier varchar(20) DEFAULT 'free',
            max_users int DEFAULT 1,
            owner_user_id bigint(20) UNSIGNED NOT NULL,
            settings longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY owner_user_id (owner_user_id),
            KEY status (status),
            KEY tier (tier)
        ) $charset;";
        dbDelta($sql);

        // Account members table
        $sql = "CREATE TABLE " . self::account_members_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            account_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            role varchar(20) NOT NULL DEFAULT 'member',
            feature_permissions longtext DEFAULT NULL,
            invited_by bigint(20) UNSIGNED DEFAULT NULL,
            invited_at datetime DEFAULT NULL,
            accepted_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY account_user (account_id, user_id),
            KEY account_id (account_id),
            KEY user_id (user_id),
            KEY role (role)
        ) $charset;";
        dbDelta($sql);

        // API keys table
        $sql = "CREATE TABLE " . self::api_keys_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            account_id bigint(20) UNSIGNED NOT NULL,
            key_id varchar(32) NOT NULL,
            key_hash varchar(255) NOT NULL,
            name varchar(255) NOT NULL,
            scopes text NOT NULL,
            created_by bigint(20) UNSIGNED NOT NULL,
            last_used_at datetime DEFAULT NULL,
            last_used_ip varchar(45) DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            revoked_at datetime DEFAULT NULL,
            revoked_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY key_id (key_id),
            KEY account_id (account_id),
            KEY created_by (created_by),
            KEY revoked_at (revoked_at)
        ) $charset;";
        dbDelta($sql);

        // Audit log table
        $sql = "CREATE TABLE " . self::audit_log_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            account_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            api_key_id bigint(20) UNSIGNED DEFAULT NULL,
            action varchar(50) NOT NULL,
            resource_type varchar(50) NOT NULL,
            resource_id bigint(20) UNSIGNED DEFAULT NULL,
            details longtext DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY account_id (account_id),
            KEY user_id (user_id),
            KEY api_key_id (api_key_id),
            KEY action (action),
            KEY resource_type (resource_type),
            KEY created_at (created_at)
        ) $charset;";
        dbDelta($sql);

        // UTM access table (for data segmentation)
        $sql = "CREATE TABLE " . self::utm_access_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            utm_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            account_id bigint(20) UNSIGNED NOT NULL,
            access_level varchar(20) DEFAULT 'view',
            assigned_by bigint(20) UNSIGNED NOT NULL,
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY utm_user (utm_id, user_id),
            KEY utm_id (utm_id),
            KEY user_id (user_id),
            KEY account_id (account_id)
        ) $charset;";
        dbDelta($sql);

        // ===== Projects Tables =====

        // Projects table (hierarchical)
        $sql = "CREATE TABLE " . self::projects_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            account_id bigint(20) UNSIGNED NOT NULL,
            parent_id bigint(20) UNSIGNED DEFAULT NULL,
            name varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            description text DEFAULT NULL,
            color varchar(7) DEFAULT '#6366f1',
            status varchar(20) DEFAULT 'active',
            settings longtext DEFAULT NULL,
            created_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY account_id (account_id),
            KEY parent_id (parent_id),
            KEY status (status),
            KEY created_by (created_by),
            UNIQUE KEY account_slug (account_id, slug)
        ) $charset;";
        dbDelta($sql);

        // Project members table
        $sql = "CREATE TABLE " . self::project_members_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            role varchar(20) NOT NULL DEFAULT 'member',
            assigned_by bigint(20) UNSIGNED NOT NULL,
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY project_user (project_id, user_id),
            KEY project_id (project_id),
            KEY user_id (user_id),
            KEY role (role)
        ) $charset;";
        dbDelta($sql);

        // ===== Clients Tables =====

        // Clients table
        $sql = "CREATE TABLE " . self::clients_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            account_id bigint(20) UNSIGNED NOT NULL,
            name varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            legal_name varchar(255) DEFAULT NULL,
            website varchar(500) DEFAULT NULL,
            industry varchar(100) DEFAULT NULL,
            size varchar(50) DEFAULT NULL,
            billing_email varchar(255) DEFAULT NULL,
            billing_address text DEFAULT NULL,
            billing_city varchar(100) DEFAULT NULL,
            billing_state varchar(100) DEFAULT NULL,
            billing_postal varchar(20) DEFAULT NULL,
            billing_country varchar(2) DEFAULT NULL,
            tax_id varchar(100) DEFAULT NULL,
            currency varchar(3) DEFAULT 'USD',
            payment_terms int DEFAULT 30,
            status varchar(20) DEFAULT 'active',
            acquisition_source varchar(100) DEFAULT NULL,
            acquired_at date DEFAULT NULL,
            notes text DEFAULT NULL,
            custom_fields longtext DEFAULT NULL,
            settings longtext DEFAULT NULL,
            created_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY account_id (account_id),
            KEY status (status),
            KEY created_by (created_by),
            UNIQUE KEY account_slug (account_id, slug)
        ) $charset;";
        dbDelta($sql);

        // Client contacts junction table
        $sql = "CREATE TABLE " . self::client_contacts_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id bigint(20) UNSIGNED NOT NULL,
            contact_id bigint(20) UNSIGNED NOT NULL,
            role varchar(50) DEFAULT 'primary',
            is_primary tinyint(1) DEFAULT 0,
            title varchar(100) DEFAULT NULL,
            department varchar(100) DEFAULT NULL,
            notes text DEFAULT NULL,
            assigned_by bigint(20) UNSIGNED NOT NULL,
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY client_contact (client_id, contact_id),
            KEY client_id (client_id),
            KEY contact_id (contact_id),
            KEY role (role)
        ) $charset;";
        dbDelta($sql);

        // ===== Plesk Server Monitoring Tables =====

        // Monitor servers table (Plesk servers)
        $sql = "CREATE TABLE " . self::monitor_servers_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            account_id bigint(20) UNSIGNED DEFAULT NULL,
            project_id bigint(20) UNSIGNED DEFAULT NULL,
            server_name varchar(255) NOT NULL,
            server_host varchar(255) NOT NULL,
            server_port int DEFAULT 8443,
            api_key_encrypted text DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            last_check datetime DEFAULT NULL,
            last_health longtext DEFAULT NULL,
            plesk_version varchar(50) DEFAULT NULL,
            os_info varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_host (user_id, server_host),
            KEY user_id (user_id),
            KEY account_id (account_id),
            KEY project_id (project_id),
            KEY status (status)
        ) $charset;";
        dbDelta($sql);

        // Monitor server health history
        $sql = "CREATE TABLE " . self::monitor_server_health_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            server_id bigint(20) UNSIGNED NOT NULL,
            status varchar(20) NOT NULL,
            score tinyint UNSIGNED DEFAULT NULL,
            grade char(1) DEFAULT NULL,
            checks longtext DEFAULT NULL,
            checked_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY server_id (server_id),
            KEY checked_at (checked_at),
            KEY status (status)
        ) $charset;";
        dbDelta($sql);

        // ===== Health Reports Tables =====

        // Health reports table
        $sql = "CREATE TABLE " . self::health_reports_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            report_type varchar(20) DEFAULT 'weekly',
            period_start date NOT NULL,
            period_end date NOT NULL,
            overall_grade char(1) DEFAULT NULL,
            overall_score tinyint UNSIGNED DEFAULT NULL,
            sites_data longtext DEFAULT NULL,
            servers_data longtext DEFAULT NULL,
            recommendations longtext DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY period_start (period_start),
            KEY report_type (report_type)
        ) $charset;";
        dbDelta($sql);

        // Health report settings table
        $sql = "CREATE TABLE " . self::health_report_settings_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            enabled tinyint(1) DEFAULT 1,
            frequency varchar(20) DEFAULT 'weekly',
            day_of_week tinyint DEFAULT 1,
            send_time varchar(5) DEFAULT '08:00',
            recipients text DEFAULT NULL,
            include_sites tinyint(1) DEFAULT 1,
            include_servers tinyint(1) DEFAULT 1,
            include_recommendations tinyint(1) DEFAULT 1,
            selected_site_ids text DEFAULT NULL,
            selected_server_ids text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset;";
        dbDelta($sql);

        // Run migrations for existing data
        self::run_migrations();

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
            // Multi-tenancy tables
            self::utm_access_table(),
            self::audit_log_table(),
            self::api_keys_table(),
            self::account_members_table(),
            self::accounts_table(),
            // Projects tables
            self::project_members_table(),
            self::projects_table(),
            // Clients tables
            self::client_contacts_table(),
            self::clients_table(),
            // Plesk monitoring tables
            self::monitor_server_health_table(),
            self::monitor_servers_table(),
            // Health reports tables
            self::health_reports_table(),
            self::health_report_settings_table(),
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('peanut_db_version');
    }

    /**
     * Run data migrations for existing users
     */
    private static function run_migrations(): void {
        global $wpdb;

        $current_version = get_option('peanut_db_version', '0');

        // Migration to 2.1.0: Set up feature permissions and UTM access
        if (version_compare($current_version, '2.1.0', '<')) {
            self::migrate_to_2_1_0();
        }

        // Migration to 2.3.0: Create default projects for existing accounts
        if (version_compare($current_version, '2.3.0', '<')) {
            self::migrate_to_2_3_0();
        }

        // Migration to 2.4.0: Add client_id columns and create default clients
        if (version_compare($current_version, '2.4.0', '<')) {
            self::migrate_to_2_4_0();
        }
    }

    /**
     * Migration to 2.1.0
     * - Set default feature permissions for existing members
     * - Set account_id on existing UTMs
     * - Grant UTM access to account owners
     */
    private static function migrate_to_2_1_0(): void {
        global $wpdb;

        $members_table = self::account_members_table();
        $accounts_table = self::accounts_table();
        $utms_table = self::utms_table();
        $utm_access_table = self::utm_access_table();

        // Default permissions by role
        $default_permissions = [
            'owner' => json_encode([
                'utm' => ['access' => true],
                'links' => ['access' => true],
                'contacts' => ['access' => true],
                'webhooks' => ['access' => true],
                'visitors' => ['access' => true],
                'attribution' => ['access' => true],
                'analytics' => ['access' => true],
                'popups' => ['access' => true],
                'monitor' => ['access' => true],
            ]),
            'admin' => json_encode([
                'utm' => ['access' => true],
                'links' => ['access' => true],
                'contacts' => ['access' => true],
                'webhooks' => ['access' => true],
                'visitors' => ['access' => true],
                'attribution' => ['access' => true],
                'analytics' => ['access' => true],
                'popups' => ['access' => true],
                'monitor' => ['access' => true],
            ]),
            'member' => json_encode([
                'utm' => ['access' => true],
                'links' => ['access' => true],
                'contacts' => ['access' => true],
                'webhooks' => ['access' => true],
                'visitors' => ['access' => false],
                'attribution' => ['access' => false],
                'analytics' => ['access' => false],
                'popups' => ['access' => false],
                'monitor' => ['access' => false],
            ]),
            'viewer' => json_encode([
                'utm' => ['access' => true],
                'links' => ['access' => true],
                'contacts' => ['access' => true],
                'webhooks' => ['access' => true],
                'visitors' => ['access' => false],
                'attribution' => ['access' => false],
                'analytics' => ['access' => false],
                'popups' => ['access' => false],
                'monitor' => ['access' => false],
            ]),
        ];

        // Update existing members with default permissions (only if null)
        foreach ($default_permissions as $role => $permissions) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$members_table}
                 SET feature_permissions = %s
                 WHERE role = %s AND feature_permissions IS NULL",
                $permissions,
                $role
            ));
        }

        // Set account_id on existing UTMs based on user's account
        $wpdb->query(
            "UPDATE {$utms_table} u
             INNER JOIN {$accounts_table} a ON u.user_id = a.owner_user_id
             SET u.account_id = a.id
             WHERE u.account_id IS NULL"
        );

        // Grant full access to existing UTMs for account owners
        $wpdb->query(
            "INSERT IGNORE INTO {$utm_access_table} (utm_id, user_id, account_id, access_level, assigned_by)
             SELECT u.id, u.user_id, u.account_id, 'full', u.user_id
             FROM {$utms_table} u
             WHERE u.account_id IS NOT NULL"
        );
    }

    /**
     * Migration to 2.3.0
     * - Create default project for each existing account
     * - Assign existing entities to the default project
     * - Add account owners as project admins
     */
    private static function migrate_to_2_3_0(): void {
        global $wpdb;

        $projects_table = self::projects_table();
        $project_members_table = self::project_members_table();
        $accounts_table = self::accounts_table();
        $utms_table = self::utms_table();
        $links_table = self::links_table();
        $contacts_table = self::contacts_table();
        $monitor_servers_table = self::monitor_servers_table();

        // Get all existing accounts
        $accounts = $wpdb->get_results("SELECT id, owner_user_id FROM $accounts_table");

        foreach ($accounts as $account) {
            // Check if default project already exists for this account
            $existing_project = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $projects_table WHERE account_id = %d AND slug = 'default'",
                $account->id
            ));

            if ($existing_project) {
                continue; // Skip if already migrated
            }

            // Create default project
            $wpdb->insert($projects_table, [
                'account_id' => $account->id,
                'parent_id' => null,
                'name' => 'General',
                'slug' => 'default',
                'description' => 'Default project for existing items',
                'color' => '#6366f1',
                'status' => 'active',
                'created_by' => $account->owner_user_id,
            ]);

            $project_id = $wpdb->insert_id;

            if (!$project_id) {
                continue; // Skip if insert failed
            }

            // Add owner as project admin
            $wpdb->insert($project_members_table, [
                'project_id' => $project_id,
                'user_id' => $account->owner_user_id,
                'role' => 'admin',
                'assigned_by' => $account->owner_user_id,
            ]);

            // Assign existing UTMs to default project
            $wpdb->query($wpdb->prepare(
                "UPDATE $utms_table SET project_id = %d WHERE account_id = %d AND project_id IS NULL",
                $project_id,
                $account->id
            ));

            // Assign existing links to default project (set account_id first if needed)
            $wpdb->query($wpdb->prepare(
                "UPDATE $links_table SET account_id = %d, project_id = %d
                 WHERE user_id = %d AND project_id IS NULL",
                $account->id,
                $project_id,
                $account->owner_user_id
            ));

            // Assign existing contacts to default project
            $wpdb->query($wpdb->prepare(
                "UPDATE $contacts_table SET account_id = %d, project_id = %d
                 WHERE user_id = %d AND project_id IS NULL",
                $account->id,
                $project_id,
                $account->owner_user_id
            ));

            // Assign existing servers to default project
            $wpdb->query($wpdb->prepare(
                "UPDATE $monitor_servers_table SET account_id = %d, project_id = %d
                 WHERE user_id = %d AND project_id IS NULL",
                $account->id,
                $project_id,
                $account->owner_user_id
            ));
        }
    }

    /**
     * Migration to 2.4.0
     * - Add client_id column to projects, contacts tables
     * - Create default client for each account
     * - Assign existing projects to the default client
     * - Create clients from existing contact companies
     */
    private static function migrate_to_2_4_0(): void {
        global $wpdb;

        $clients_table = self::clients_table();
        $client_contacts_table = self::client_contacts_table();
        $projects_table = self::projects_table();
        $contacts_table = self::contacts_table();
        $accounts_table = self::accounts_table();

        // Add client_id column to projects table if it doesn't exist
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$projects_table} LIKE 'client_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$projects_table} ADD COLUMN client_id bigint(20) UNSIGNED DEFAULT NULL AFTER account_id");
            $wpdb->query("ALTER TABLE {$projects_table} ADD KEY client_id (client_id)");
        }

        // Add client_id column to contacts table if it doesn't exist
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$contacts_table} LIKE 'client_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$contacts_table} ADD COLUMN client_id bigint(20) UNSIGNED DEFAULT NULL AFTER project_id");
            $wpdb->query("ALTER TABLE {$contacts_table} ADD KEY client_id (client_id)");
        }

        // Get all existing accounts
        $accounts = $wpdb->get_results("SELECT id, owner_user_id FROM {$accounts_table}");

        foreach ($accounts as $account) {
            // Check if default client already exists for this account
            $existing_client = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$clients_table} WHERE account_id = %d AND slug = 'general'",
                $account->id
            ));

            if ($existing_client) {
                continue; // Skip if already migrated
            }

            // Create default client
            $wpdb->insert($clients_table, [
                'account_id' => $account->id,
                'name' => 'General',
                'slug' => 'general',
                'status' => 'active',
                'settings' => json_encode(['is_default' => true]),
                'created_by' => $account->owner_user_id,
            ]);

            $client_id = $wpdb->insert_id;

            if (!$client_id) {
                continue; // Skip if insert failed
            }

            // Assign all projects in this account to the default client
            $wpdb->query($wpdb->prepare(
                "UPDATE {$projects_table} SET client_id = %d WHERE account_id = %d AND client_id IS NULL",
                $client_id,
                $account->id
            ));

            // Create additional clients from unique company names in contacts
            $companies = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT company FROM {$contacts_table}
                 WHERE account_id = %d AND company IS NOT NULL AND company != '' AND company != 'General'",
                $account->id
            ));

            foreach ($companies as $company) {
                // Create slug from company name
                $slug = sanitize_title($company);
                $base_slug = $slug;
                $counter = 1;

                // Ensure unique slug within account
                while ($wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$clients_table} WHERE account_id = %d AND slug = %s",
                    $account->id,
                    $slug
                ))) {
                    $slug = $base_slug . '-' . $counter;
                    $counter++;
                }

                // Create client for this company
                $wpdb->insert($clients_table, [
                    'account_id' => $account->id,
                    'name' => $company,
                    'slug' => $slug,
                    'status' => 'active',
                    'created_by' => $account->owner_user_id,
                ]);

                $company_client_id = $wpdb->insert_id;

                if ($company_client_id) {
                    // Link contacts with this company to the new client
                    $contact_ids = $wpdb->get_col($wpdb->prepare(
                        "SELECT id FROM {$contacts_table}
                         WHERE account_id = %d AND company = %s",
                        $account->id,
                        $company
                    ));

                    foreach ($contact_ids as $contact_id) {
                        // Update contact with client_id
                        $wpdb->update(
                            $contacts_table,
                            ['client_id' => $company_client_id],
                            ['id' => $contact_id]
                        );

                        // Create junction table entry
                        $wpdb->insert($client_contacts_table, [
                            'client_id' => $company_client_id,
                            'contact_id' => $contact_id,
                            'role' => 'primary',
                            'is_primary' => 1,
                            'assigned_by' => $account->owner_user_id,
                        ]);
                    }
                }
            }
        }
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

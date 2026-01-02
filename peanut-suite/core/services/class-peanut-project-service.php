<?php
/**
 * Project Service
 *
 * Handles project management for hierarchical project organization.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Project_Service {

    /**
     * Project statuses
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * Member roles within a project
     */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';
    public const ROLE_VIEWER = 'viewer';

    /**
     * Role hierarchy (higher number = more permissions)
     */
    private const ROLE_LEVELS = [
        self::ROLE_VIEWER => 1,
        self::ROLE_MEMBER => 2,
        self::ROLE_ADMIN => 3,
    ];

    /**
     * Max projects per tier
     */
    private const TIER_PROJECT_LIMITS = [
        'free' => 3,
        'pro' => 25,
        'agency' => -1, // Unlimited
    ];

    /**
     * Get project limit for a tier
     */
    public static function get_project_limit(string $tier): int {
        return self::TIER_PROJECT_LIMITS[$tier] ?? 3;
    }

    /**
     * Check if account can create more projects
     */
    public static function can_create_project(int $account_id): bool {
        $account = Peanut_Account_Service::get_by_id($account_id);
        if (!$account) {
            return false;
        }

        $limit = self::get_project_limit($account['tier'] ?? 'free');
        if ($limit === -1) {
            return true; // Unlimited
        }

        $current_count = self::get_project_count($account_id);
        return $current_count < $limit;
    }

    /**
     * Get project count for an account
     */
    public static function get_project_count(int $account_id): int {
        global $wpdb;
        $table = Peanut_Database::projects_table();

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE account_id = %d AND status = %s",
            $account_id,
            self::STATUS_ACTIVE
        ));
    }

    /**
     * Get project limits info for an account
     */
    public static function get_limits(int $account_id): array {
        $account = Peanut_Account_Service::get_by_id($account_id);
        $tier = $account['tier'] ?? 'free';
        $limit = self::get_project_limit($tier);
        $current = self::get_project_count($account_id);

        return [
            'current' => $current,
            'max' => $limit,
            'unlimited' => $limit === -1,
            'tier' => $tier,
            'can_create' => $limit === -1 || $current < $limit,
        ];
    }

    /**
     * Get all projects for an account
     */
    public static function get_projects_for_account(int $account_id, ?int $parent_id = null): array {
        global $wpdb;
        $table = Peanut_Database::projects_table();

        if ($parent_id === null) {
            // Get all projects for account
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE account_id = %d ORDER BY parent_id ASC, name ASC",
                $account_id
            ), ARRAY_A) ?: [];
        }

        // Get children of a specific parent
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE account_id = %d AND parent_id = %d ORDER BY name ASC",
            $account_id,
            $parent_id
        ), ARRAY_A) ?: [];
    }

    /**
     * Get projects accessible to a user
     */
    public static function get_accessible_projects_for_user(int $account_id, int $user_id): array {
        global $wpdb;

        // Check if user is owner/admin at account level - they see all projects
        $role = Peanut_Account_Service::get_user_role($account_id, $user_id);
        if (in_array($role, ['owner', 'admin'], true)) {
            return self::get_projects_for_account($account_id);
        }

        // For members/viewers, only return projects they're explicitly assigned to
        $projects_table = Peanut_Database::projects_table();
        $members_table = Peanut_Database::project_members_table();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.* FROM $projects_table p
             INNER JOIN $members_table pm ON p.id = pm.project_id
             WHERE p.account_id = %d AND pm.user_id = %d AND p.status = %s
             ORDER BY p.name ASC",
            $account_id,
            $user_id,
            self::STATUS_ACTIVE
        ), ARRAY_A) ?: [];
    }

    /**
     * Get project by ID
     */
    public static function get_by_id(int $project_id): ?array {
        global $wpdb;
        $table = Peanut_Database::projects_table();

        $project = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $project_id
        ), ARRAY_A);

        return $project ?: null;
    }

    /**
     * Get project by slug within an account
     */
    public static function get_by_slug(int $account_id, string $slug): ?array {
        global $wpdb;
        $table = Peanut_Database::projects_table();

        $project = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE account_id = %d AND slug = %s",
            $account_id,
            $slug
        ), ARRAY_A);

        return $project ?: null;
    }

    /**
     * Create a new project
     */
    public static function create(int $account_id, array $data, int $created_by): ?int {
        global $wpdb;
        $table = Peanut_Database::projects_table();

        // Check if can create
        if (!self::can_create_project($account_id)) {
            return null;
        }

        // Generate slug if not provided
        $slug = $data['slug'] ?? sanitize_title($data['name']);
        $slug = self::ensure_unique_slug($account_id, $slug);

        $insert_data = [
            'account_id' => $account_id,
            'parent_id' => $data['parent_id'] ?? null,
            'name' => sanitize_text_field($data['name']),
            'slug' => $slug,
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : null,
            'color' => $data['color'] ?? '#6366f1',
            'status' => self::STATUS_ACTIVE,
            'settings' => isset($data['settings']) ? wp_json_encode($data['settings']) : null,
            'created_by' => $created_by,
        ];

        $result = $wpdb->insert($table, $insert_data);

        if (!$result) {
            return null;
        }

        $project_id = (int) $wpdb->insert_id;

        // Add creator as project admin
        self::add_member($project_id, $created_by, self::ROLE_ADMIN, $created_by);

        // Log the action
        if (class_exists('Peanut_Audit_Log_Service')) {
            Peanut_Audit_Log_Service::log(
                $account_id,
                'create',
                'project',
                $project_id,
                ['name' => $data['name']]
            );
        }

        return $project_id;
    }

    /**
     * Ensure slug is unique within account
     */
    private static function ensure_unique_slug(int $account_id, string $slug, ?int $exclude_id = null): string {
        global $wpdb;
        $table = Peanut_Database::projects_table();
        $original_slug = $slug;
        $counter = 1;

        while (true) {
            $query = $wpdb->prepare(
                "SELECT id FROM $table WHERE account_id = %d AND slug = %s",
                $account_id,
                $slug
            );

            if ($exclude_id) {
                $query .= $wpdb->prepare(" AND id != %d", $exclude_id);
            }

            $exists = $wpdb->get_var($query);

            if (!$exists) {
                break;
            }

            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Update a project
     */
    public static function update(int $project_id, array $data): bool {
        global $wpdb;
        $table = Peanut_Database::projects_table();

        $project = self::get_by_id($project_id);
        if (!$project) {
            return false;
        }

        $update_data = [];

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }

        if (isset($data['slug'])) {
            $update_data['slug'] = self::ensure_unique_slug(
                $project['account_id'],
                sanitize_title($data['slug']),
                $project_id
            );
        }

        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
        }

        if (isset($data['color'])) {
            $update_data['color'] = sanitize_hex_color($data['color']) ?: '#6366f1';
        }

        if (isset($data['parent_id'])) {
            // Prevent circular references
            if ($data['parent_id'] !== null && !self::is_valid_parent($project_id, (int) $data['parent_id'])) {
                return false;
            }
            $update_data['parent_id'] = $data['parent_id'];
        }

        if (isset($data['status'])) {
            $update_data['status'] = in_array($data['status'], [self::STATUS_ACTIVE, self::STATUS_ARCHIVED], true)
                ? $data['status']
                : self::STATUS_ACTIVE;
        }

        if (isset($data['settings'])) {
            $update_data['settings'] = wp_json_encode($data['settings']);
        }

        if (empty($update_data)) {
            return true; // Nothing to update
        }

        $result = $wpdb->update($table, $update_data, ['id' => $project_id]);

        return $result !== false;
    }

    /**
     * Check if a parent_id is valid (not creating circular reference)
     */
    private static function is_valid_parent(int $project_id, int $parent_id): bool {
        if ($project_id === $parent_id) {
            return false;
        }

        // Check if parent_id is a descendant of project_id
        $descendants = self::get_all_descendants($project_id);
        return !in_array($parent_id, $descendants, true);
    }

    /**
     * Get all descendant project IDs
     */
    public static function get_all_descendants(int $project_id): array {
        global $wpdb;
        $table = Peanut_Database::projects_table();

        $descendants = [];
        $to_check = [$project_id];

        while (!empty($to_check)) {
            $current = array_shift($to_check);
            $children = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM $table WHERE parent_id = %d",
                $current
            ));

            foreach ($children as $child_id) {
                $descendants[] = (int) $child_id;
                $to_check[] = (int) $child_id;
            }
        }

        return $descendants;
    }

    /**
     * Delete a project
     */
    public static function delete(int $project_id): bool {
        global $wpdb;
        $table = Peanut_Database::projects_table();
        $members_table = Peanut_Database::project_members_table();

        $project = self::get_by_id($project_id);
        if (!$project) {
            return false;
        }

        // Check if project has children
        $children = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE parent_id = %d",
            $project_id
        ));

        if ($children > 0) {
            // Move children to parent (or root)
            $wpdb->update(
                $table,
                ['parent_id' => $project['parent_id']],
                ['parent_id' => $project_id]
            );
        }

        // Delete project members
        $wpdb->delete($members_table, ['project_id' => $project_id]);

        // Delete project
        $result = $wpdb->delete($table, ['id' => $project_id]);

        return $result !== false;
    }

    /**
     * Get project members
     */
    public static function get_members(int $project_id): array {
        global $wpdb;
        $table = Peanut_Database::project_members_table();

        $members = $wpdb->get_results($wpdb->prepare(
            "SELECT pm.*, u.display_name, u.user_email
             FROM $table pm
             INNER JOIN {$wpdb->users} u ON pm.user_id = u.ID
             WHERE pm.project_id = %d
             ORDER BY pm.role ASC, u.display_name ASC",
            $project_id
        ), ARRAY_A);

        return $members ?: [];
    }

    /**
     * Get a specific member
     */
    public static function get_member(int $project_id, int $user_id): ?array {
        global $wpdb;
        $table = Peanut_Database::project_members_table();

        $member = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE project_id = %d AND user_id = %d",
            $project_id,
            $user_id
        ), ARRAY_A);

        return $member ?: null;
    }

    /**
     * Add member to project
     */
    public static function add_member(int $project_id, int $user_id, string $role, int $assigned_by): bool {
        global $wpdb;
        $table = Peanut_Database::project_members_table();

        // Check if already a member
        $exists = self::get_member($project_id, $user_id);
        if ($exists) {
            return false;
        }

        // Validate role
        if (!isset(self::ROLE_LEVELS[$role])) {
            $role = self::ROLE_MEMBER;
        }

        $result = $wpdb->insert($table, [
            'project_id' => $project_id,
            'user_id' => $user_id,
            'role' => $role,
            'assigned_by' => $assigned_by,
        ]);

        return $result !== false;
    }

    /**
     * Update member role
     */
    public static function update_member_role(int $project_id, int $user_id, string $role): bool {
        global $wpdb;
        $table = Peanut_Database::project_members_table();

        // Validate role
        if (!isset(self::ROLE_LEVELS[$role])) {
            return false;
        }

        $result = $wpdb->update(
            $table,
            ['role' => $role],
            ['project_id' => $project_id, 'user_id' => $user_id]
        );

        return $result !== false;
    }

    /**
     * Remove member from project
     */
    public static function remove_member(int $project_id, int $user_id): bool {
        global $wpdb;
        $table = Peanut_Database::project_members_table();

        $result = $wpdb->delete($table, [
            'project_id' => $project_id,
            'user_id' => $user_id,
        ]);

        return $result !== false;
    }

    /**
     * Check if user can access a project
     */
    public static function user_can_access_project(int $project_id, int $user_id): bool {
        $project = self::get_by_id($project_id);
        if (!$project) {
            return false;
        }

        // Check if user is owner/admin at account level
        $account_role = Peanut_Account_Service::get_user_role($project['account_id'], $user_id);
        if (in_array($account_role, ['owner', 'admin'], true)) {
            return true;
        }

        // Check if user is explicitly a project member
        $member = self::get_member($project_id, $user_id);
        return $member !== null;
    }

    /**
     * Check if user has a minimum role in project
     */
    public static function user_has_project_role(int $project_id, int $user_id, string $minimum_role): bool {
        $project = self::get_by_id($project_id);
        if (!$project) {
            return false;
        }

        // Account owners/admins have full access to all projects
        $account_role = Peanut_Account_Service::get_user_role($project['account_id'], $user_id);
        if (in_array($account_role, ['owner', 'admin'], true)) {
            return true;
        }

        // Get user's project role
        $member = self::get_member($project_id, $user_id);
        if (!$member) {
            return false;
        }

        $user_level = self::ROLE_LEVELS[$member['role']] ?? 0;
        $required_level = self::ROLE_LEVELS[$minimum_role] ?? 0;

        return $user_level >= $required_level;
    }

    /**
     * Get project hierarchy (nested structure)
     */
    public static function get_hierarchy_for_account(int $account_id): array {
        $projects = self::get_projects_for_account($account_id);

        // Build tree structure
        $project_map = [];
        $roots = [];

        // First pass: index all projects
        foreach ($projects as $project) {
            $project['children'] = [];
            $project_map[$project['id']] = $project;
        }

        // Second pass: build tree
        foreach ($project_map as $id => $project) {
            if ($project['parent_id'] && isset($project_map[$project['parent_id']])) {
                $project_map[$project['parent_id']]['children'][] = &$project_map[$id];
            } else {
                $roots[] = &$project_map[$id];
            }
        }

        return $roots;
    }

    /**
     * Get project ancestors (for breadcrumb)
     */
    public static function get_ancestors(int $project_id): array {
        $ancestors = [];
        $current = self::get_by_id($project_id);

        while ($current && $current['parent_id']) {
            $parent = self::get_by_id($current['parent_id']);
            if ($parent) {
                array_unshift($ancestors, $parent);
                $current = $parent;
            } else {
                break;
            }
        }

        return $ancestors;
    }

    /**
     * Get direct children of a project
     */
    public static function get_children(int $project_id): array {
        global $wpdb;
        $table = Peanut_Database::projects_table();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE parent_id = %d ORDER BY name ASC",
            $project_id
        ), ARRAY_A) ?: [];
    }

    /**
     * Get projects a user is assigned to (for team member)
     */
    public static function get_user_project_assignments(int $account_id, int $user_id): array {
        global $wpdb;
        $projects_table = Peanut_Database::projects_table();
        $members_table = Peanut_Database::project_members_table();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.id, p.name, pm.role
             FROM $projects_table p
             INNER JOIN $members_table pm ON p.id = pm.project_id
             WHERE p.account_id = %d AND pm.user_id = %d
             ORDER BY p.name ASC",
            $account_id,
            $user_id
        ), ARRAY_A) ?: [];
    }

    /**
     * Bulk assign user to projects
     */
    public static function assign_user_to_projects(int $user_id, array $project_ids, string $role, int $assigned_by): int {
        $count = 0;
        foreach ($project_ids as $project_id) {
            if (self::add_member((int) $project_id, $user_id, $role, $assigned_by)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Remove user from all projects in account
     */
    public static function remove_user_from_all_projects(int $account_id, int $user_id): int {
        global $wpdb;
        $projects_table = Peanut_Database::projects_table();
        $members_table = Peanut_Database::project_members_table();

        return (int) $wpdb->query($wpdb->prepare(
            "DELETE pm FROM $members_table pm
             INNER JOIN $projects_table p ON pm.project_id = p.id
             WHERE p.account_id = %d AND pm.user_id = %d",
            $account_id,
            $user_id
        ));
    }
}

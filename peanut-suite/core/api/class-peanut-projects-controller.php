<?php
/**
 * Projects REST Controller
 *
 * Handles project CRUD and member management endpoints.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Peanut_Projects_Controller extends Peanut_REST_Controller {

    protected string $rest_base = 'projects';

    /**
     * Register routes
     */
    public function register_routes(): void {
        // Project list and create
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_projects'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_project'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Accessible projects for current user
        register_rest_route($this->namespace, '/' . $this->rest_base . '/accessible', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_accessible_projects'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Hierarchy view
        register_rest_route($this->namespace, '/' . $this->rest_base . '/hierarchy', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_project_hierarchy'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Tier limits
        register_rest_route($this->namespace, '/' . $this->rest_base . '/limits', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_project_limits'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Single project operations
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_project'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_project'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_project'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Project stats
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/stats', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_project_stats'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Project members
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<project_id>\d+)/members', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_members'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'add_member'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<project_id>\d+)/members/(?P<user_id>\d+)', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_member'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'remove_member'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Bulk member operations
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<project_id>\d+)/members/bulk', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'bulk_add_members'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Children projects
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/children', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_children'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);
    }

    // ===============================
    // Project Methods
    // ===============================

    /**
     * Get all projects for the current account
     */
    public function get_projects(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = get_current_user_id();
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);

        if (!$account) {
            return $this->error('account_error', 'Failed to get account', 500);
        }

        $parent_id = $request->get_param('parent_id');
        $parent_id = $parent_id !== null ? (int) $parent_id : null;

        $projects = Peanut_Project_Service::get_projects_for_account($account['id'], $parent_id);

        return $this->success($projects);
    }

    /**
     * Get accessible projects for the current user
     */
    public function get_accessible_projects(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = get_current_user_id();
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);

        if (!$account) {
            return $this->error('account_error', 'Failed to get account', 500);
        }

        $projects = Peanut_Project_Service::get_accessible_projects_for_user($account['id'], $user_id);

        return $this->success($projects);
    }

    /**
     * Get project hierarchy
     */
    public function get_project_hierarchy(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = get_current_user_id();
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);

        if (!$account) {
            return $this->error('account_error', 'Failed to get account', 500);
        }

        $hierarchy = Peanut_Project_Service::get_hierarchy_for_account($account['id']);

        return $this->success($hierarchy);
    }

    /**
     * Get project limits based on tier
     */
    public function get_project_limits(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = get_current_user_id();
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);

        if (!$account) {
            return $this->error('account_error', 'Failed to get account', 500);
        }

        $limits = Peanut_Project_Service::get_limits($account['id']);

        return $this->success($limits);
    }

    /**
     * Get single project
     */
    public function get_project(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $project_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        $project = Peanut_Project_Service::get_by_id($project_id);
        if (!$project) {
            return $this->not_found('Project not found');
        }

        // Check access
        if (!Peanut_Project_Service::user_can_access_project($project_id, $user_id)) {
            return $this->error('forbidden', 'Access denied', 403);
        }

        return $this->success($project);
    }

    /**
     * Create new project
     */
    public function create_project(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $user_id = get_current_user_id();
        $account = Peanut_Account_Service::get_or_create_for_user($user_id);

        if (!$account) {
            return $this->error('account_error', 'Failed to get account', 500);
        }

        // Check if user has admin role
        if (!Peanut_Account_Service::user_has_role($account['id'], $user_id, 'admin')) {
            return $this->error('forbidden', 'Admin access required', 403);
        }

        // Check tier limits
        if (!Peanut_Project_Service::can_create_project($account['id'])) {
            $limits = Peanut_Project_Service::get_limits($account['id']);
            return $this->error(
                'limit_exceeded',
                sprintf('Project limit reached (%d/%d). Upgrade to create more projects.', $limits['current'], $limits['max']),
                403
            );
        }

        $data = [
            'name' => sanitize_text_field($request->get_param('name')),
            'slug' => sanitize_title($request->get_param('slug') ?: $request->get_param('name')),
            'description' => sanitize_textarea_field($request->get_param('description') ?: ''),
            'color' => sanitize_hex_color($request->get_param('color') ?: '#6366f1'),
            'parent_id' => $request->get_param('parent_id') ? (int) $request->get_param('parent_id') : null,
            'settings' => $request->get_param('settings') ?: [],
        ];

        if (empty($data['name'])) {
            return $this->error('missing_name', 'Project name is required');
        }

        // Validate parent project if provided
        if ($data['parent_id']) {
            $parent = Peanut_Project_Service::get_by_id($data['parent_id']);
            if (!$parent || $parent['account_id'] !== $account['id']) {
                return $this->error('invalid_parent', 'Parent project not found');
            }
        }

        $project_id = Peanut_Project_Service::create($account['id'], $data, $user_id);

        if (!$project_id) {
            return $this->error('create_failed', 'Failed to create project');
        }

        // Log the action
        Peanut_Audit_Log_Service::log(
            $account['id'],
            Peanut_Audit_Log_Service::ACTION_CREATE,
            'project',
            $project_id,
            ['name' => $data['name']]
        );

        $project = Peanut_Project_Service::get_by_id($project_id);
        return $this->success($project, 201);
    }

    /**
     * Update project
     */
    public function update_project(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $project_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        $project = Peanut_Project_Service::get_by_id($project_id);
        if (!$project) {
            return $this->not_found('Project not found');
        }

        // Check if user can manage this project
        if (!Peanut_Project_Service::user_has_project_role($project_id, $user_id, 'admin')) {
            return $this->error('forbidden', 'Admin access required', 403);
        }

        $data = [];

        if ($request->has_param('name')) {
            $data['name'] = sanitize_text_field($request->get_param('name'));
        }
        if ($request->has_param('slug')) {
            $data['slug'] = sanitize_title($request->get_param('slug'));
        }
        if ($request->has_param('description')) {
            $data['description'] = sanitize_textarea_field($request->get_param('description'));
        }
        if ($request->has_param('color')) {
            $data['color'] = sanitize_hex_color($request->get_param('color'));
        }
        if ($request->has_param('status')) {
            $data['status'] = sanitize_key($request->get_param('status'));
        }
        if ($request->has_param('parent_id')) {
            $parent_id = $request->get_param('parent_id');
            $data['parent_id'] = $parent_id ? (int) $parent_id : null;

            // Validate parent if provided
            if ($data['parent_id']) {
                // Can't set self as parent
                if ($data['parent_id'] === $project_id) {
                    return $this->error('invalid_parent', 'Project cannot be its own parent');
                }

                $parent = Peanut_Project_Service::get_by_id($data['parent_id']);
                if (!$parent || $parent['account_id'] !== $project['account_id']) {
                    return $this->error('invalid_parent', 'Parent project not found');
                }

                // Check for circular reference
                $ancestors = Peanut_Project_Service::get_ancestors($data['parent_id']);
                if (in_array($project_id, array_column($ancestors, 'id'), true)) {
                    return $this->error('circular_reference', 'Cannot create circular project hierarchy');
                }
            }
        }
        if ($request->has_param('settings')) {
            $data['settings'] = $request->get_param('settings');
        }

        $result = Peanut_Project_Service::update($project_id, $data);

        if (!$result) {
            return $this->error('update_failed', 'Failed to update project');
        }

        // Log the action
        Peanut_Audit_Log_Service::log(
            $project['account_id'],
            Peanut_Audit_Log_Service::ACTION_UPDATE,
            'project',
            $project_id,
            ['fields' => array_keys($data)]
        );

        $updated_project = Peanut_Project_Service::get_by_id($project_id);
        return $this->success($updated_project);
    }

    /**
     * Delete project
     */
    public function delete_project(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $project_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        $project = Peanut_Project_Service::get_by_id($project_id);
        if (!$project) {
            return $this->not_found('Project not found');
        }

        // Check if user can manage this project
        if (!Peanut_Project_Service::user_has_project_role($project_id, $user_id, 'admin')) {
            return $this->error('forbidden', 'Admin access required', 403);
        }

        // Can't delete if it's the default project
        if (!empty($project['settings']['is_default'])) {
            return $this->error('cannot_delete_default', 'Cannot delete the default project');
        }

        // Check for children
        $children = Peanut_Project_Service::get_children($project_id);
        if (!empty($children)) {
            return $this->error('has_children', 'Cannot delete project with sub-projects. Delete or move children first.');
        }

        // Get counts for entities
        $stats = Peanut_Project_Service::get_stats($project_id);
        $total_entities = ($stats['links_count'] ?? 0) + ($stats['utms_count'] ?? 0) +
                         ($stats['contacts_count'] ?? 0) + ($stats['sites_count'] ?? 0);

        if ($total_entities > 0) {
            $force = (bool) $request->get_param('force');
            $move_to = $request->get_param('move_to');

            if (!$force && !$move_to) {
                return $this->error(
                    'has_entities',
                    sprintf('Project contains %d items. Provide move_to project ID or set force=true to delete everything.', $total_entities),
                    400
                );
            }

            if ($move_to) {
                $target = Peanut_Project_Service::get_by_id((int) $move_to);
                if (!$target || $target['account_id'] !== $project['account_id']) {
                    return $this->error('invalid_target', 'Target project not found');
                }
                // Move entities to target project
                Peanut_Project_Service::move_entities($project_id, (int) $move_to);
            }
        }

        $result = Peanut_Project_Service::delete($project_id);

        if (!$result) {
            return $this->error('delete_failed', 'Failed to delete project');
        }

        // Log the action
        Peanut_Audit_Log_Service::log(
            $project['account_id'],
            Peanut_Audit_Log_Service::ACTION_DELETE,
            'project',
            $project_id,
            ['name' => $project['name']]
        );

        return $this->success(['deleted' => true]);
    }

    /**
     * Get project stats
     */
    public function get_project_stats(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $project_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        $project = Peanut_Project_Service::get_by_id($project_id);
        if (!$project) {
            return $this->not_found('Project not found');
        }

        // Check access
        if (!Peanut_Project_Service::user_can_access_project($project_id, $user_id)) {
            return $this->error('forbidden', 'Access denied', 403);
        }

        $stats = Peanut_Project_Service::get_stats($project_id);

        return $this->success($stats);
    }

    /**
     * Get children projects
     */
    public function get_children(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $project_id = (int) $request->get_param('id');
        $user_id = get_current_user_id();

        $project = Peanut_Project_Service::get_by_id($project_id);
        if (!$project) {
            return $this->not_found('Project not found');
        }

        // Check access
        if (!Peanut_Project_Service::user_can_access_project($project_id, $user_id)) {
            return $this->error('forbidden', 'Access denied', 403);
        }

        $children = Peanut_Project_Service::get_children($project_id);

        return $this->success($children);
    }

    // ===============================
    // Member Methods
    // ===============================

    /**
     * Get project members
     */
    public function get_members(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $project_id = (int) $request->get_param('project_id');
        $user_id = get_current_user_id();

        $project = Peanut_Project_Service::get_by_id($project_id);
        if (!$project) {
            return $this->not_found('Project not found');
        }

        // Check access
        if (!Peanut_Project_Service::user_can_access_project($project_id, $user_id)) {
            return $this->error('forbidden', 'Access denied', 403);
        }

        $members = Peanut_Project_Service::get_members($project_id);

        return $this->success($members);
    }

    /**
     * Add member to project
     */
    public function add_member(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $project_id = (int) $request->get_param('project_id');
        $current_user_id = get_current_user_id();

        $project = Peanut_Project_Service::get_by_id($project_id);
        if (!$project) {
            return $this->not_found('Project not found');
        }

        // Check if user can manage this project
        if (!Peanut_Project_Service::user_has_project_role($project_id, $current_user_id, 'admin')) {
            return $this->error('forbidden', 'Admin access required', 403);
        }

        $target_user_id = (int) $request->get_param('user_id');
        $role = sanitize_key($request->get_param('role') ?: 'member');

        // Verify target user is a member of the account
        $account_role = Peanut_Account_Service::get_user_role($project['account_id'], $target_user_id);
        if (!$account_role) {
            return $this->error('not_account_member', 'User must be a member of the account first');
        }

        // Validate role
        if (!in_array($role, ['admin', 'member', 'viewer'], true)) {
            return $this->error('invalid_role', 'Role must be admin, member, or viewer');
        }

        $result = Peanut_Project_Service::add_member($project_id, $target_user_id, $role, $current_user_id);

        if (!$result) {
            return $this->error('add_failed', 'Failed to add member. They may already be assigned.');
        }

        // Log the action
        Peanut_Audit_Log_Service::log(
            $project['account_id'],
            Peanut_Audit_Log_Service::ACTION_CREATE,
            'project_member',
            $project_id,
            ['user_id' => $target_user_id, 'role' => $role]
        );

        $members = Peanut_Project_Service::get_members($project_id);
        return $this->success($members, 201);
    }

    /**
     * Update project member role
     */
    public function update_member(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $project_id = (int) $request->get_param('project_id');
        $target_user_id = (int) $request->get_param('user_id');
        $current_user_id = get_current_user_id();

        $project = Peanut_Project_Service::get_by_id($project_id);
        if (!$project) {
            return $this->not_found('Project not found');
        }

        // Check if user can manage this project
        if (!Peanut_Project_Service::user_has_project_role($project_id, $current_user_id, 'admin')) {
            return $this->error('forbidden', 'Admin access required', 403);
        }

        $new_role = sanitize_key($request->get_param('role'));

        // Validate role
        if (!in_array($new_role, ['admin', 'member', 'viewer'], true)) {
            return $this->error('invalid_role', 'Role must be admin, member, or viewer');
        }

        $result = Peanut_Project_Service::update_member_role($project_id, $target_user_id, $new_role);

        if (!$result) {
            return $this->error('update_failed', 'Failed to update member role');
        }

        // Log the action
        Peanut_Audit_Log_Service::log(
            $project['account_id'],
            Peanut_Audit_Log_Service::ACTION_UPDATE,
            'project_member',
            $project_id,
            ['user_id' => $target_user_id, 'role' => $new_role]
        );

        $members = Peanut_Project_Service::get_members($project_id);
        return $this->success($members);
    }

    /**
     * Remove member from project
     */
    public function remove_member(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $project_id = (int) $request->get_param('project_id');
        $target_user_id = (int) $request->get_param('user_id');
        $current_user_id = get_current_user_id();

        $project = Peanut_Project_Service::get_by_id($project_id);
        if (!$project) {
            return $this->not_found('Project not found');
        }

        // Check if user can manage this project or is removing themselves
        $can_remove = $target_user_id === $current_user_id ||
                      Peanut_Project_Service::user_has_project_role($project_id, $current_user_id, 'admin');

        if (!$can_remove) {
            return $this->error('forbidden', 'Permission denied', 403);
        }

        $result = Peanut_Project_Service::remove_member($project_id, $target_user_id);

        if (!$result) {
            return $this->error('remove_failed', 'Failed to remove member');
        }

        // Log the action
        Peanut_Audit_Log_Service::log(
            $project['account_id'],
            Peanut_Audit_Log_Service::ACTION_DELETE,
            'project_member',
            $project_id,
            ['user_id' => $target_user_id]
        );

        $members = Peanut_Project_Service::get_members($project_id);
        return $this->success($members);
    }

    /**
     * Bulk add members to project
     */
    public function bulk_add_members(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $project_id = (int) $request->get_param('project_id');
        $current_user_id = get_current_user_id();

        $project = Peanut_Project_Service::get_by_id($project_id);
        if (!$project) {
            return $this->not_found('Project not found');
        }

        // Check if user can manage this project
        if (!Peanut_Project_Service::user_has_project_role($project_id, $current_user_id, 'admin')) {
            return $this->error('forbidden', 'Admin access required', 403);
        }

        $members_to_add = $request->get_param('members');
        if (!is_array($members_to_add) || empty($members_to_add)) {
            return $this->error('missing_members', 'Members array is required');
        }

        $added = [];
        $failed = [];

        foreach ($members_to_add as $member) {
            $user_id = (int) ($member['user_id'] ?? 0);
            $role = sanitize_key($member['role'] ?? 'member');

            if (!$user_id) {
                continue;
            }

            // Verify user is account member
            $account_role = Peanut_Account_Service::get_user_role($project['account_id'], $user_id);
            if (!$account_role) {
                $failed[] = ['user_id' => $user_id, 'reason' => 'Not an account member'];
                continue;
            }

            if (!in_array($role, ['admin', 'member', 'viewer'], true)) {
                $role = 'member';
            }

            $result = Peanut_Project_Service::add_member($project_id, $user_id, $role, $current_user_id);

            if ($result) {
                $added[] = ['user_id' => $user_id, 'role' => $role];
            } else {
                $failed[] = ['user_id' => $user_id, 'reason' => 'Already assigned or error'];
            }
        }

        // Log the action
        if (!empty($added)) {
            Peanut_Audit_Log_Service::log(
                $project['account_id'],
                Peanut_Audit_Log_Service::ACTION_CREATE,
                'project_member',
                $project_id,
                ['action' => 'bulk_add', 'added' => count($added)]
            );
        }

        $members = Peanut_Project_Service::get_members($project_id);

        return $this->success([
            'members' => $members,
            'added' => $added,
            'failed' => $failed,
        ]);
    }
}

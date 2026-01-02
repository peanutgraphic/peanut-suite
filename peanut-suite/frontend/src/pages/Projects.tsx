import { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  FolderKanban,
  Plus,
  Trash2,
  Edit2,
  Users,
  Loader2,
  ChevronRight,
  Link2,
  AlertCircle,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Input, Modal, Badge, Textarea, useToast } from '../components/common';
import { projectsApi, accountsApi } from '../api/endpoints';
import { useAccountStore, useProjectStore, flattenHierarchy } from '../store';
import type { Project, ProjectFormData, ProjectMember, ProjectRole, ProjectHierarchy, AccountMember } from '../types';

const ROLE_VARIANTS: Record<ProjectRole, 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'> = {
  admin: 'primary',
  member: 'info',
  viewer: 'default',
};

const PROJECT_COLORS = [
  '#6366f1', // Indigo
  '#8b5cf6', // Violet
  '#ec4899', // Pink
  '#ef4444', // Red
  '#f97316', // Orange
  '#eab308', // Yellow
  '#22c55e', // Green
  '#14b8a6', // Teal
  '#0ea5e9', // Sky
  '#6b7280', // Gray
];

export default function Projects() {
  const [searchParams, setSearchParams] = useSearchParams();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { account, isOwnerOrAdmin } = useAccountStore();
  const { setProjects, setHierarchy, setLimits } = useProjectStore();

  const [showCreateModal, setShowCreateModal] = useState(searchParams.get('create') === 'true');
  const [showEditModal, setShowEditModal] = useState(false);
  const [showMembersModal, setShowMembersModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [selectedProject, setSelectedProject] = useState<Project | null>(null);
  const [expandedProjects, setExpandedProjects] = useState<Set<number>>(new Set());

  // Form state
  const [formData, setFormData] = useState<ProjectFormData>({
    name: '',
    description: '',
    color: PROJECT_COLORS[0],
    parent_id: null,
  });

  // Close create modal when URL changes
  useEffect(() => {
    if (searchParams.get('create') === 'true') {
      setShowCreateModal(true);
      // Clear the search param
      setSearchParams({}, { replace: true });
    }
  }, [searchParams, setSearchParams]);

  // Fetch projects
  const { data: projects = [], isLoading } = useQuery({
    queryKey: ['projects'],
    queryFn: async () => {
      const data = await projectsApi.getAll();
      setProjects(data);
      return data;
    },
  });

  // Fetch hierarchy
  const { data: hierarchy = [] } = useQuery({
    queryKey: ['projects', 'hierarchy'],
    queryFn: async () => {
      const data = await projectsApi.getHierarchy();
      setHierarchy(data);
      return data;
    },
  });

  // Fetch limits
  const { data: limits } = useQuery({
    queryKey: ['projects', 'limits'],
    queryFn: async () => {
      const data = await projectsApi.getLimits();
      setLimits(data);
      return data;
    },
  });

  // Fetch account members for the members modal
  const { data: accountMembers = [] } = useQuery({
    queryKey: ['account', 'members', account?.id],
    queryFn: () => accountsApi.getMembers(account!.id),
    enabled: !!account?.id && showMembersModal,
  });

  // Fetch project members when modal is open
  const { data: projectMembers = [], refetch: refetchMembers } = useQuery({
    queryKey: ['projects', selectedProject?.id, 'members'],
    queryFn: () => projectsApi.getMembers(selectedProject!.id),
    enabled: !!selectedProject?.id && showMembersModal,
  });

  // Create mutation
  const createMutation = useMutation({
    mutationFn: (data: ProjectFormData) => projectsApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      setShowCreateModal(false);
      resetForm();
      toast.success('Project created');
    },
    onError: (error: Error) => {
      toast.error(error.message);
    },
  });

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<ProjectFormData> }) =>
      projectsApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      setShowEditModal(false);
      setSelectedProject(null);
      resetForm();
      toast.success('Project updated');
    },
    onError: (error: Error) => {
      toast.error(error.message);
    },
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: (id: number) => projectsApi.delete(id, { force: true }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      setShowDeleteModal(false);
      setSelectedProject(null);
      toast.success('Project deleted');
    },
    onError: (error: Error) => {
      toast.error(error.message);
    },
  });

  // Add member mutation
  const addMemberMutation = useMutation({
    mutationFn: ({ projectId, userId, role }: { projectId: number; userId: number; role: ProjectRole }) =>
      projectsApi.addMember(projectId, userId, role),
    onSuccess: () => {
      refetchMembers();
      toast.success('Member added');
    },
    onError: (error: Error) => {
      toast.error(error.message);
    },
  });

  // Remove member mutation
  const removeMemberMutation = useMutation({
    mutationFn: ({ projectId, userId }: { projectId: number; userId: number }) =>
      projectsApi.removeMember(projectId, userId),
    onSuccess: () => {
      refetchMembers();
      toast.success('Member removed');
    },
    onError: (error: Error) => {
      toast.error(error.message);
    },
  });

  const resetForm = () => {
    setFormData({
      name: '',
      description: '',
      color: PROJECT_COLORS[0],
      parent_id: null,
    });
  };

  const handleCreate = () => {
    if (!formData.name.trim()) return;
    createMutation.mutate(formData);
  };

  const handleEdit = (project: Project) => {
    setSelectedProject(project);
    setFormData({
      name: project.name,
      description: project.description || '',
      color: project.color,
      parent_id: project.parent_id,
    });
    setShowEditModal(true);
  };

  const handleUpdate = () => {
    if (!selectedProject || !formData.name.trim()) return;
    updateMutation.mutate({ id: selectedProject.id, data: formData });
  };

  const handleDelete = (project: Project) => {
    setSelectedProject(project);
    setShowDeleteModal(true);
  };

  const handleManageMembers = (project: Project) => {
    setSelectedProject(project);
    setShowMembersModal(true);
  };

  const toggleExpand = (projectId: number) => {
    setExpandedProjects((prev) => {
      const next = new Set(prev);
      if (next.has(projectId)) {
        next.delete(projectId);
      } else {
        next.add(projectId);
      }
      return next;
    });
  };

  // Flatten hierarchy for display
  const flatProjects = flattenHierarchy(hierarchy as ProjectHierarchy[]);

  // Get members not already in project
  const availableMembers = accountMembers.filter(
    (am: AccountMember) => !projectMembers.some((pm: ProjectMember) => pm.user_id === am.user_id)
  );

  const canCreate = isOwnerOrAdmin() && (limits?.can_create ?? true);

  if (isLoading) {
    return (
      <Layout title="Projects" description="Organize your work into projects">
        <Card>
          <div className="py-12 text-center">
            <Loader2 className="w-8 h-8 text-primary-500 mx-auto mb-4 animate-spin" />
            <p className="text-slate-500">Loading projects...</p>
          </div>
        </Card>
      </Layout>
    );
  }

  return (
    <Layout title="Projects" description="Organize your work into projects" pageGuideId="projects">
      {/* Header actions */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-4">
          {limits && (
            <Badge variant={limits.can_create ? 'info' : 'warning'}>
              {limits.current} / {limits.max === -1 ? 'Unlimited' : limits.max} Projects
            </Badge>
          )}
        </div>
        {canCreate && (
          <Button onClick={() => setShowCreateModal(true)}>
            <Plus className="w-4 h-4 mr-2" />
            New Project
          </Button>
        )}
      </div>

      {/* Projects list */}
      <Card>
        {projects.length === 0 ? (
          <div className="py-12 text-center">
            <FolderKanban className="w-12 h-12 text-slate-300 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-slate-900 mb-2">No projects yet</h3>
            <p className="text-slate-500 mb-6">
              Create your first project to start organizing your work.
            </p>
            {canCreate && (
              <Button onClick={() => setShowCreateModal(true)}>
                <Plus className="w-4 h-4 mr-2" />
                Create Project
              </Button>
            )}
          </div>
        ) : (
          <div className="divide-y divide-slate-100">
            {flatProjects.map((project) => {
              const hasChildren = projects.some((p) => p.parent_id === project.id);
              const isExpanded = expandedProjects.has(project.id);
              const isDefault = project.settings?.is_default;

              return (
                <div
                  key={project.id}
                  className="flex items-center gap-4 p-4 hover:bg-slate-50 transition-colors"
                  style={{ paddingLeft: `${16 + project.depth * 24}px` }}
                >
                  {/* Expand toggle */}
                  <button
                    onClick={() => hasChildren && toggleExpand(project.id)}
                    className={`w-6 h-6 flex items-center justify-center rounded transition-colors ${
                      hasChildren
                        ? 'hover:bg-slate-200 text-slate-400'
                        : 'text-transparent cursor-default'
                    }`}
                  >
                    {hasChildren && (
                      <ChevronRight
                        className={`w-4 h-4 transition-transform ${isExpanded ? 'rotate-90' : ''}`}
                      />
                    )}
                  </button>

                  {/* Color indicator */}
                  <div
                    className="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                    style={{ backgroundColor: project.color }}
                  >
                    <FolderKanban className="w-5 h-5 text-white" />
                  </div>

                  {/* Project info */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <h3 className="font-medium text-slate-900">{project.name}</h3>
                      {isDefault && (
                        <Badge variant="default" size="sm">
                          Default
                        </Badge>
                      )}
                    </div>
                    {project.description && (
                      <p className="text-sm text-slate-500 truncate">{project.description}</p>
                    )}
                  </div>

                  {/* Stats */}
                  <div className="flex items-center gap-6 text-sm text-slate-500">
                    <div className="flex items-center gap-1.5" title="Links">
                      <Link2 className="w-4 h-4" />
                      <span>{project.entity_count ?? 0}</span>
                    </div>
                    <div className="flex items-center gap-1.5" title="Members">
                      <Users className="w-4 h-4" />
                      <span>{project.member_count ?? 0}</span>
                    </div>
                  </div>

                  {/* Actions */}
                  {isOwnerOrAdmin() && (
                    <div className="flex items-center gap-1">
                      <button
                        onClick={() => handleManageMembers(project)}
                        className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
                        title="Manage members"
                      >
                        <Users className="w-4 h-4" />
                      </button>
                      <button
                        onClick={() => handleEdit(project)}
                        className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
                        title="Edit"
                      >
                        <Edit2 className="w-4 h-4" />
                      </button>
                      {!isDefault && (
                        <button
                          onClick={() => handleDelete(project)}
                          className="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                          title="Delete"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      )}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        )}
      </Card>

      {/* Create Modal */}
      <Modal
        isOpen={showCreateModal}
        onClose={() => {
          setShowCreateModal(false);
          resetForm();
        }}
        title="Create Project"
      >
        <div className="space-y-4">
          <Input
            label="Project Name"
            value={formData.name}
            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
            placeholder="e.g., Client Campaign Q1"
            required
          />

          <Textarea
            label="Description"
            value={formData.description || ''}
            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
            placeholder="Optional description for this project"
            rows={3}
          />

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-2">Color</label>
            <div className="flex flex-wrap gap-2">
              {PROJECT_COLORS.map((color) => (
                <button
                  key={color}
                  onClick={() => setFormData({ ...formData, color })}
                  className={`w-8 h-8 rounded-lg transition-all ${
                    formData.color === color ? 'ring-2 ring-offset-2 ring-primary-500' : ''
                  }`}
                  style={{ backgroundColor: color }}
                />
              ))}
            </div>
          </div>

          {projects.length > 0 && (
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">
                Parent Project (Optional)
              </label>
              <select
                value={formData.parent_id || ''}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    parent_id: e.target.value ? Number(e.target.value) : null,
                  })
                }
                className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                <option value="">None (Top-level project)</option>
                {projects.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.name}
                  </option>
                ))}
              </select>
            </div>
          )}

          <div className="flex justify-end gap-3 pt-4">
            <Button
              variant="ghost"
              onClick={() => {
                setShowCreateModal(false);
                resetForm();
              }}
            >
              Cancel
            </Button>
            <Button onClick={handleCreate} loading={createMutation.isPending}>
              Create Project
            </Button>
          </div>
        </div>
      </Modal>

      {/* Edit Modal */}
      <Modal
        isOpen={showEditModal}
        onClose={() => {
          setShowEditModal(false);
          setSelectedProject(null);
          resetForm();
        }}
        title="Edit Project"
      >
        <div className="space-y-4">
          <Input
            label="Project Name"
            value={formData.name}
            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
            placeholder="e.g., Client Campaign Q1"
            required
          />

          <Textarea
            label="Description"
            value={formData.description || ''}
            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
            placeholder="Optional description for this project"
            rows={3}
          />

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-2">Color</label>
            <div className="flex flex-wrap gap-2">
              {PROJECT_COLORS.map((color) => (
                <button
                  key={color}
                  onClick={() => setFormData({ ...formData, color })}
                  className={`w-8 h-8 rounded-lg transition-all ${
                    formData.color === color ? 'ring-2 ring-offset-2 ring-primary-500' : ''
                  }`}
                  style={{ backgroundColor: color }}
                />
              ))}
            </div>
          </div>

          <div className="flex justify-end gap-3 pt-4">
            <Button
              variant="ghost"
              onClick={() => {
                setShowEditModal(false);
                setSelectedProject(null);
                resetForm();
              }}
            >
              Cancel
            </Button>
            <Button onClick={handleUpdate} loading={updateMutation.isPending}>
              Save Changes
            </Button>
          </div>
        </div>
      </Modal>

      {/* Delete Confirmation Modal */}
      <Modal
        isOpen={showDeleteModal}
        onClose={() => {
          setShowDeleteModal(false);
          setSelectedProject(null);
        }}
        title="Delete Project"
      >
        <div className="space-y-4">
          <div className="flex items-start gap-3 p-4 bg-red-50 rounded-lg">
            <AlertCircle className="w-5 h-5 text-red-600 mt-0.5" />
            <div>
              <p className="text-sm text-red-800">
                Are you sure you want to delete <strong>{selectedProject?.name}</strong>? This will
                also delete all data associated with this project.
              </p>
            </div>
          </div>

          <div className="flex justify-end gap-3 pt-2">
            <Button
              variant="ghost"
              onClick={() => {
                setShowDeleteModal(false);
                setSelectedProject(null);
              }}
            >
              Cancel
            </Button>
            <Button
              variant="danger"
              onClick={() => selectedProject && deleteMutation.mutate(selectedProject.id)}
              loading={deleteMutation.isPending}
            >
              Delete Project
            </Button>
          </div>
        </div>
      </Modal>

      {/* Members Modal */}
      <Modal
        isOpen={showMembersModal}
        onClose={() => {
          setShowMembersModal(false);
          setSelectedProject(null);
        }}
        title={`Members - ${selectedProject?.name}`}
        size="lg"
      >
        <div className="space-y-6">
          {/* Current members */}
          <div>
            <h4 className="text-sm font-medium text-slate-700 mb-3">Project Members</h4>
            {projectMembers.length === 0 ? (
              <p className="text-sm text-slate-500 py-4 text-center">
                No members assigned to this project yet.
              </p>
            ) : (
              <div className="space-y-2">
                {projectMembers.map((member: ProjectMember) => (
                  <div
                    key={member.id}
                    className="flex items-center justify-between p-3 bg-slate-50 rounded-lg"
                  >
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                        <Users className="w-4 h-4 text-primary-600" />
                      </div>
                      <div>
                        <p className="font-medium text-slate-900 text-sm">
                          {member.display_name || member.user_email}
                        </p>
                        <p className="text-xs text-slate-500">{member.user_email}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-3">
                      <Badge variant={ROLE_VARIANTS[member.role]} size="sm">
                        {member.role}
                      </Badge>
                      <button
                        onClick={() =>
                          removeMemberMutation.mutate({
                            projectId: selectedProject!.id,
                            userId: member.user_id,
                          })
                        }
                        className="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Add members */}
          {availableMembers.length > 0 && (
            <div>
              <h4 className="text-sm font-medium text-slate-700 mb-3">Add Team Members</h4>
              <div className="space-y-2">
                {availableMembers.map((member: AccountMember) => (
                  <div
                    key={member.user_id}
                    className="flex items-center justify-between p-3 border border-slate-200 rounded-lg"
                  >
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center">
                        <Users className="w-4 h-4 text-slate-500" />
                      </div>
                      <div>
                        <p className="font-medium text-slate-900 text-sm">{member.display_name}</p>
                        <p className="text-xs text-slate-500">{member.user_email}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <select
                        className="text-sm border border-slate-300 rounded px-2 py-1"
                        defaultValue="member"
                        id={`role-${member.user_id}`}
                      >
                        <option value="admin">Admin</option>
                        <option value="member">Member</option>
                        <option value="viewer">Viewer</option>
                      </select>
                      <Button
                        size="sm"
                        onClick={() => {
                          const select = document.getElementById(
                            `role-${member.user_id}`
                          ) as HTMLSelectElement;
                          addMemberMutation.mutate({
                            projectId: selectedProject!.id,
                            userId: member.user_id,
                            role: select.value as ProjectRole,
                          });
                        }}
                        loading={addMemberMutation.isPending}
                      >
                        Add
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          <div className="flex justify-end pt-2">
            <Button
              variant="ghost"
              onClick={() => {
                setShowMembersModal(false);
                setSelectedProject(null);
              }}
            >
              Close
            </Button>
          </div>
        </div>
      </Modal>
    </Layout>
  );
}

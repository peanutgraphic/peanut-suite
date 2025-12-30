import { useState, useEffect } from 'react';
import {
  Users,
  UserPlus,
  Crown,
  Shield,
  Eye,
  MoreVertical,
  Trash2,
  Edit2,
  Check,
  X,
  AlertCircle,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Input, Badge, Modal, SampleDataBanner } from '../components/common';
import { accountsApi } from '../api/endpoints';
import type { AccountMember, AccountRole, FeaturePermissions } from '../types';
import { sampleTeamMembers } from '../constants';

const ROLE_CONFIG: Record<AccountRole, { label: string; color: string; icon: typeof Crown }> = {
  owner: { label: 'Owner', color: 'amber', icon: Crown },
  admin: { label: 'Admin', color: 'purple', icon: Shield },
  member: { label: 'Member', color: 'blue', icon: Users },
  viewer: { label: 'Viewer', color: 'slate', icon: Eye },
};

const FEATURES = [
  { id: 'utm', name: 'UTM Builder', tier: 'free' },
  { id: 'links', name: 'Short Links', tier: 'free' },
  { id: 'contacts', name: 'Contacts', tier: 'free' },
  { id: 'webhooks', name: 'Webhooks', tier: 'free' },
  { id: 'visitors', name: 'Visitors', tier: 'pro' },
  { id: 'attribution', name: 'Attribution', tier: 'pro' },
  { id: 'analytics', name: 'Analytics', tier: 'pro' },
  { id: 'popups', name: 'Popups', tier: 'pro' },
  { id: 'monitor', name: 'Site Monitor', tier: 'agency' },
] as const;

type FeatureId = typeof FEATURES[number]['id'];

export default function Team() {
  const [members, setMembers] = useState<AccountMember[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showAddModal, setShowAddModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [selectedMember, setSelectedMember] = useState<AccountMember | null>(null);
  const [openDropdown, setOpenDropdown] = useState<number | null>(null);
  const [showSampleData, setShowSampleData] = useState(true);

  // TODO: Get from account context when implemented
  const accountId = 1;
  const currentUserRole: AccountRole = 'owner';

  // Determine if we should show sample data
  const hasNoRealData = !loading && members.length === 0;
  const displaySampleData = hasNoRealData && showSampleData;
  const displayMembers = displaySampleData ? sampleTeamMembers as AccountMember[] : members;

  useEffect(() => {
    loadMembers();
  }, []);

  const loadMembers = async () => {
    try {
      setLoading(true);
      const data = await accountsApi.getMembers(accountId);
      setMembers(data);
      setError(null);
    } catch (err) {
      setError('Failed to load team members');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleAddMember = async (email: string, role: AccountRole, permissions: FeaturePermissions) => {
    try {
      await accountsApi.addMember(accountId, { email, role, feature_permissions: permissions });
      await loadMembers();
      setShowAddModal(false);
    } catch (err) {
      console.error('Failed to add member:', err);
      throw err;
    }
  };

  const handleUpdatePermissions = async (userId: number, permissions: FeaturePermissions) => {
    try {
      await accountsApi.updateMemberPermissions(accountId, userId, permissions);
      await loadMembers();
      setShowEditModal(false);
      setSelectedMember(null);
    } catch (err) {
      console.error('Failed to update permissions:', err);
      throw err;
    }
  };

  const handleUpdateRole = async (userId: number, role: AccountRole) => {
    try {
      await accountsApi.updateMemberRole(accountId, userId, role);
      await loadMembers();
    } catch (err) {
      console.error('Failed to update role:', err);
    }
  };

  const handleRemoveMember = async (userId: number) => {
    if (!confirm('Are you sure you want to remove this team member?')) return;
    try {
      await accountsApi.removeMember(accountId, userId);
      await loadMembers();
    } catch (err) {
      console.error('Failed to remove member:', err);
    }
  };

  const canManageMembers = currentUserRole === 'owner' || currentUserRole === 'admin';

  return (
    <Layout
      title="Team"
      description="Manage your team members and their permissions"
      pageGuideId="team"
    >
      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Header with Add Button */}
      {canManageMembers && (
        <div className="flex justify-end mb-6">
          <Button icon={<UserPlus className="w-4 h-4" />} onClick={() => setShowAddModal(true)}>
            Add Member
          </Button>
        </div>
      )}

      {error && (
        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3">
          <AlertCircle className="w-5 h-5 text-red-500" />
          <span className="text-red-700">{error}</span>
        </div>
      )}

      <Card>
        {loading ? (
          <div className="py-12 text-center text-slate-500">Loading team members...</div>
        ) : displayMembers.length === 0 ? (
          <div className="py-12 text-center">
            <Users className="w-12 h-12 text-slate-300 mx-auto mb-4" />
            <p className="text-slate-500">No team members yet</p>
            {canManageMembers && (
              <Button
                variant="outline"
                size="sm"
                className="mt-4"
                onClick={() => setShowAddModal(true)}
              >
                Add your first team member
              </Button>
            )}
          </div>
        ) : (
          <div className="divide-y divide-slate-100">
            {displayMembers.map((member) => (
              <MemberRow
                key={member.user_id}
                member={member}
                canManage={canManageMembers && member.role !== 'owner' && !displaySampleData}
                isOpen={openDropdown === member.user_id}
                onToggleDropdown={() =>
                  setOpenDropdown(openDropdown === member.user_id ? null : member.user_id)
                }
                onEdit={() => {
                  setSelectedMember(member);
                  setShowEditModal(true);
                  setOpenDropdown(null);
                }}
                onRemove={() => handleRemoveMember(member.user_id)}
                onRoleChange={(role) => handleUpdateRole(member.user_id, role)}
              />
            ))}
          </div>
        )}
      </Card>

      {/* Add Member Modal */}
      <AddMemberModal
        isOpen={showAddModal}
        onClose={() => setShowAddModal(false)}
        onSubmit={handleAddMember}
      />

      {/* Edit Permissions Modal */}
      {selectedMember && (
        <EditPermissionsModal
          isOpen={showEditModal}
          onClose={() => {
            setShowEditModal(false);
            setSelectedMember(null);
          }}
          member={selectedMember}
          onSubmit={(permissions) => handleUpdatePermissions(selectedMember.user_id, permissions)}
        />
      )}
    </Layout>
  );
}

interface MemberRowProps {
  member: AccountMember;
  canManage: boolean;
  isOpen: boolean;
  onToggleDropdown: () => void;
  onEdit: () => void;
  onRemove: () => void;
  onRoleChange: (role: AccountRole) => void;
}

function MemberRow({
  member,
  canManage,
  isOpen,
  onToggleDropdown,
  onEdit,
  onRemove,
}: MemberRowProps) {
  const roleConfig = ROLE_CONFIG[member.role];
  const RoleIcon = roleConfig.icon;

  const enabledFeatures = member.feature_permissions
    ? Object.entries(member.feature_permissions)
        .filter(([, v]) => v?.access)
        .map(([k]) => k)
    : [];

  return (
    <div className="flex items-center justify-between p-4 hover:bg-slate-50 transition-colors">
      <div className="flex items-center gap-4">
        <div className="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
          <span className="text-primary-700 font-medium">
            {member.display_name?.charAt(0).toUpperCase() || member.user_email.charAt(0).toUpperCase()}
          </span>
        </div>
        <div>
          <div className="flex items-center gap-2">
            <span className="font-medium text-slate-900">{member.display_name || member.user_login}</span>
            <Badge
              variant={member.role === 'owner' ? 'warning' : member.role === 'admin' ? 'primary' : 'default'}
              size="sm"
            >
              <RoleIcon className="w-3 h-3 mr-1" />
              {roleConfig.label}
            </Badge>
          </div>
          <p className="text-sm text-slate-500">{member.user_email}</p>
        </div>
      </div>

      <div className="flex items-center gap-4">
        {/* Feature access summary */}
        {member.role !== 'owner' && member.role !== 'admin' && (
          <div className="hidden md:flex items-center gap-1">
            {enabledFeatures.length > 0 ? (
              <span className="text-xs text-slate-500">
                {enabledFeatures.length} feature{enabledFeatures.length !== 1 ? 's' : ''} enabled
              </span>
            ) : (
              <span className="text-xs text-slate-400">No specific permissions</span>
            )}
          </div>
        )}

        {/* Actions dropdown */}
        {canManage && (
          <div className="relative">
            <button
              onClick={onToggleDropdown}
              className="p-2 hover:bg-slate-100 rounded-lg transition-colors"
            >
              <MoreVertical className="w-4 h-4 text-slate-400" />
            </button>
            {isOpen && (
              <>
                <div className="fixed inset-0 z-10" onClick={onToggleDropdown} />
                <div className="absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-20">
                  <button
                    onClick={onEdit}
                    className="w-full flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                  >
                    <Edit2 className="w-4 h-4" />
                    Edit Permissions
                  </button>
                  <button
                    onClick={onRemove}
                    className="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                  >
                    <Trash2 className="w-4 h-4" />
                    Remove Member
                  </button>
                </div>
              </>
            )}
          </div>
        )}
      </div>
    </div>
  );
}

interface AddMemberModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (email: string, role: AccountRole, permissions: FeaturePermissions) => Promise<void>;
}

function AddMemberModal({ isOpen, onClose, onSubmit }: AddMemberModalProps) {
  const [email, setEmail] = useState('');
  const [role, setRole] = useState<AccountRole>('member');
  const [permissions, setPermissions] = useState<FeaturePermissions>({});
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isOpen) {
      setEmail('');
      setRole('member');
      setPermissions({});
      setError(null);
    }
  }, [isOpen]);

  // Set default permissions based on role
  useEffect(() => {
    if (role === 'admin') {
      // Admins get all permissions
      const allPerms: FeaturePermissions = {};
      FEATURES.forEach((f) => {
        allPerms[f.id as FeatureId] = { access: true };
      });
      setPermissions(allPerms);
    } else if (role === 'member') {
      // Members get free features by default
      const freePerms: FeaturePermissions = {};
      FEATURES.filter((f) => f.tier === 'free').forEach((f) => {
        freePerms[f.id as FeatureId] = { access: true };
      });
      setPermissions(freePerms);
    } else if (role === 'viewer') {
      // Viewers get read-only to free features
      const viewerPerms: FeaturePermissions = {};
      FEATURES.filter((f) => f.tier === 'free').forEach((f) => {
        viewerPerms[f.id as FeatureId] = { access: true };
      });
      setPermissions(viewerPerms);
    }
  }, [role]);

  const handleSubmit = async () => {
    if (!email) {
      setError('Email is required');
      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      await onSubmit(email, role, permissions);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to add member');
    } finally {
      setSubmitting(false);
    }
  };

  const togglePermission = (featureId: FeatureId) => {
    setPermissions((prev) => ({
      ...prev,
      [featureId]: { access: !prev[featureId]?.access },
    }));
  };

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="Add Team Member" size="md">
      <div className="space-y-6">
        {error && (
          <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            {error}
          </div>
        )}

        <Input
          label="Email Address"
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="colleague@company.com"
        />

        <div>
          <label className="block text-sm font-medium text-slate-700 mb-2">Role</label>
          <div className="grid grid-cols-3 gap-2">
            {(['admin', 'member', 'viewer'] as const).map((r) => {
              const config = ROLE_CONFIG[r];
              const Icon = config.icon;
              return (
                <button
                  key={r}
                  type="button"
                  onClick={() => setRole(r)}
                  className={`p-3 rounded-lg border-2 text-left transition-colors ${
                    role === r
                      ? 'border-primary-500 bg-primary-50'
                      : 'border-slate-200 hover:border-slate-300'
                  }`}
                >
                  <Icon className={`w-5 h-5 mb-1 ${role === r ? 'text-primary-600' : 'text-slate-400'}`} />
                  <div className="font-medium text-sm">{config.label}</div>
                </button>
              );
            })}
          </div>
        </div>

        {/* Feature Permissions */}
        {role !== 'admin' && (
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-2">
              Feature Access
            </label>
            <div className="space-y-2 max-h-64 overflow-y-auto">
              {FEATURES.map((feature) => (
                <label
                  key={feature.id}
                  className="flex items-center justify-between p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer"
                >
                  <div className="flex items-center gap-3">
                    <span className="text-sm font-medium text-slate-700">{feature.name}</span>
                    {feature.tier !== 'free' && (
                      <Badge variant="default" size="sm">
                        {feature.tier}
                      </Badge>
                    )}
                  </div>
                  <button
                    type="button"
                    onClick={() => togglePermission(feature.id as FeatureId)}
                    className={`w-10 h-6 rounded-full transition-colors ${
                      permissions[feature.id as FeatureId]?.access
                        ? 'bg-primary-600'
                        : 'bg-slate-200'
                    }`}
                  >
                    <span
                      className={`block w-5 h-5 bg-white rounded-full shadow transition-transform ${
                        permissions[feature.id as FeatureId]?.access
                          ? 'translate-x-[18px]'
                          : 'translate-x-0.5'
                      }`}
                    />
                  </button>
                </label>
              ))}
            </div>
          </div>
        )}

        <div className="flex justify-end gap-3 pt-4 border-t border-slate-200">
          <Button variant="outline" onClick={onClose} disabled={submitting}>
            Cancel
          </Button>
          <Button onClick={handleSubmit} disabled={submitting}>
            {submitting ? 'Adding...' : 'Add Member'}
          </Button>
        </div>
      </div>
    </Modal>
  );
}

interface EditPermissionsModalProps {
  isOpen: boolean;
  onClose: () => void;
  member: AccountMember;
  onSubmit: (permissions: FeaturePermissions) => Promise<void>;
}

function EditPermissionsModal({ isOpen, onClose, member, onSubmit }: EditPermissionsModalProps) {
  const [permissions, setPermissions] = useState<FeaturePermissions>({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (isOpen && member.feature_permissions) {
      setPermissions(member.feature_permissions);
    }
  }, [isOpen, member]);

  const handleSubmit = async () => {
    setSubmitting(true);
    try {
      await onSubmit(permissions);
    } finally {
      setSubmitting(false);
    }
  };

  const togglePermission = (featureId: FeatureId) => {
    setPermissions((prev) => ({
      ...prev,
      [featureId]: { access: !prev[featureId]?.access },
    }));
  };

  const enabledCount = Object.values(permissions).filter((p) => p?.access).length;

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={`Edit Permissions - ${member.display_name || member.user_login}`}
      size="md"
    >
      <div className="space-y-6">
        <div className="flex items-center gap-3 p-3 bg-slate-50 rounded-lg">
          <div className="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
            <span className="text-primary-700 font-medium">
              {member.display_name?.charAt(0).toUpperCase() || member.user_email.charAt(0).toUpperCase()}
            </span>
          </div>
          <div>
            <p className="font-medium text-slate-900">{member.display_name || member.user_login}</p>
            <p className="text-sm text-slate-500">{member.user_email}</p>
          </div>
        </div>

        <div>
          <div className="flex items-center justify-between mb-3">
            <label className="text-sm font-medium text-slate-700">Feature Access</label>
            <span className="text-xs text-slate-500">{enabledCount} of {FEATURES.length} enabled</span>
          </div>
          <div className="space-y-2">
            {FEATURES.map((feature) => (
              <label
                key={feature.id}
                className="flex items-center justify-between p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer"
              >
                <div className="flex items-center gap-3">
                  {permissions[feature.id as FeatureId]?.access ? (
                    <Check className="w-4 h-4 text-green-500" />
                  ) : (
                    <X className="w-4 h-4 text-slate-300" />
                  )}
                  <span className="text-sm font-medium text-slate-700">{feature.name}</span>
                  {feature.tier !== 'free' && (
                    <Badge variant="default" size="sm">
                      {feature.tier}
                    </Badge>
                  )}
                </div>
                <button
                  type="button"
                  onClick={() => togglePermission(feature.id as FeatureId)}
                  className={`w-10 h-6 rounded-full transition-colors ${
                    permissions[feature.id as FeatureId]?.access
                      ? 'bg-primary-600'
                      : 'bg-slate-200'
                  }`}
                >
                  <span
                    className={`block w-5 h-5 bg-white rounded-full shadow transition-transform ${
                      permissions[feature.id as FeatureId]?.access
                        ? 'translate-x-[18px]'
                        : 'translate-x-0.5'
                    }`}
                  />
                </button>
              </label>
            ))}
          </div>
        </div>

        <div className="flex justify-end gap-3 pt-4 border-t border-slate-200">
          <Button variant="outline" onClick={onClose} disabled={submitting}>
            Cancel
          </Button>
          <Button onClick={handleSubmit} disabled={submitting}>
            {submitting ? 'Saving...' : 'Save Permissions'}
          </Button>
        </div>
      </div>
    </Modal>
  );
}

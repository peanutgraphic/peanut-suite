import { useState, useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import { useNavigate } from 'react-router-dom';
import { getPortalRoot } from '../utils/portalRoot';
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
  Loader2,
  Settings,
  Copy,
  ExternalLink,
  ChevronDown,
  ChevronUp,
  LogIn,
  KeyRound,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Input, Badge, Modal, SampleDataBanner } from '../components/common';
import { accountsApi } from '../api/endpoints';
import { useAccountStore } from '../store/useAccountStore';
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

interface LoginSettings {
  login_page_id: number | null;
  login_page_url: string | null;
  logo_url: string;
  title: string;
  redirect_url: string;
  shortcode: string;
}

export default function Team() {
  const navigate = useNavigate();
  const [members, setMembers] = useState<AccountMember[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showAddModal, setShowAddModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [selectedMember, setSelectedMember] = useState<AccountMember | null>(null);
  const [openDropdown, setOpenDropdown] = useState<number | null>(null);
  const [showSampleData, setShowSampleData] = useState(true);
  const [showLoginSettings, setShowLoginSettings] = useState(false);
  const [loginSettings, setLoginSettings] = useState<LoginSettings | null>(null);
  const [loginSettingsLoading, setLoginSettingsLoading] = useState(false);

  // Get account context from store
  const { account, isInitialized, fetchCurrentUser } = useAccountStore();
  const accountId = account?.id ?? null;
  const currentUserRole: AccountRole = account?.role ?? 'owner';

  // Determine if we should show sample data
  const safeMembers = Array.isArray(members) ? members : [];
  const hasNoRealData = !loading && safeMembers.length === 0;
  const displaySampleData = hasNoRealData && showSampleData;
  const displayMembers = displaySampleData ? sampleTeamMembers as AccountMember[] : safeMembers;

  // Fetch account data if not initialized
  useEffect(() => {
    if (!isInitialized) {
      fetchCurrentUser();
    }
  }, [isInitialized, fetchCurrentUser]);

  // Load members when account is available
  useEffect(() => {
    if (isInitialized && accountId) {
      loadMembers();
    } else if (isInitialized && !accountId) {
      // No account, stop loading
      setLoading(false);
    }
  }, [isInitialized, accountId]);

  const loadMembers = async () => {
    if (!accountId) {
      setLoading(false);
      return;
    }

    try {
      setLoading(true);
      const data = await accountsApi.getMembers(accountId);
      setMembers(Array.isArray(data) ? data : []);
      setError(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load team members');
      setMembers([]);
    } finally {
      setLoading(false);
    }
  };

  const loadLoginSettings = async () => {
    if (!accountId) return;

    try {
      setLoginSettingsLoading(true);
      const data = await accountsApi.getLoginSettings(accountId);
      setLoginSettings(data);
    } catch {
      // Silently fail - login settings are optional
    } finally {
      setLoginSettingsLoading(false);
    }
  };

  // Load login settings when section is expanded
  useEffect(() => {
    if (showLoginSettings && !loginSettings && accountId) {
      loadLoginSettings();
    }
  }, [showLoginSettings, accountId]);

  const handleAddMember = async (email: string, role: AccountRole, permissions: FeaturePermissions) => {
    if (!accountId) return;
    await accountsApi.addMember(accountId, { email, role, feature_permissions: permissions });
    await loadMembers();
    setShowAddModal(false);
  };

  const handleUpdatePermissions = async (userId: number, permissions: FeaturePermissions) => {
    if (!accountId) return;
    await accountsApi.updateMemberPermissions(accountId, userId, permissions);
    await loadMembers();
    setShowEditModal(false);
    setSelectedMember(null);
  };

  const handleUpdateRole = async (userId: number, role: AccountRole) => {
    if (!accountId) return;
    await accountsApi.updateMemberRole(accountId, userId, role);
    await loadMembers();
  };

  const handleRemoveMember = async (userId: number) => {
    if (!accountId) return;
    if (!confirm('Are you sure you want to remove this team member?')) return;
    await accountsApi.removeMember(accountId, userId);
    await loadMembers();
  };

  const handleResetPassword = async (userId: number, email: string) => {
    if (!accountId) return;
    if (!confirm(`Send password reset email to ${email}?`)) return;
    try {
      await accountsApi.resetMemberPassword(accountId, userId);
      alert('Password reset email sent successfully!');
    } catch {
      alert('Failed to send password reset email. Please try again.');
    }
  };

  const canManageMembers = currentUserRole === 'owner' || currentUserRole === 'admin';

  // Show loading state while initializing account
  if (!isInitialized) {
    return (
      <Layout
        title="Team"
        description="Manage your team members and their permissions"
        pageGuideId="team"
      >
        <Card>
          <div className="py-12 text-center">
            <Loader2 className="w-8 h-8 text-primary-500 mx-auto mb-4 animate-spin" />
            <p className="text-slate-500">Loading account...</p>
          </div>
        </Card>
      </Layout>
    );
  }

  // Show message when no account is available
  if (!accountId) {
    return (
      <Layout
        title="Team"
        description="Manage your team members and their permissions"
        pageGuideId="team"
      >
        <Card>
          <div className="py-12 text-center">
            <Users className="w-12 h-12 text-slate-300 mx-auto mb-4" />
            <p className="text-slate-500 mb-2">No account found</p>
            <p className="text-sm text-slate-400">
              Team management requires an account. Please contact your administrator.
            </p>
          </div>
        </Card>
      </Layout>
    );
  }

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

      {error && !displaySampleData && (
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
                onResetPassword={() => handleResetPassword(member.user_id, member.user_email)}
                onRoleChange={(role) => handleUpdateRole(member.user_id, role)}
                onNavigateToProfile={() => {
                  if (!displaySampleData) {
                    navigate(`/team/${member.user_id}`);
                  }
                }}
              />
            ))}
          </div>
        )}
      </Card>

      {/* Login Page Settings */}
      {canManageMembers && (
        <Card className="mt-6">
          <button
            onClick={() => setShowLoginSettings(!showLoginSettings)}
            className="w-full flex items-center justify-between p-4 text-left"
          >
            <div className="flex items-center gap-3">
              <div className="p-2 bg-primary-100 rounded-lg">
                <LogIn className="w-5 h-5 text-primary-600" />
              </div>
              <div>
                <h3 className="font-medium text-slate-900">Team Login Page</h3>
                <p className="text-sm text-slate-500">Configure the login page for team members</p>
              </div>
            </div>
            {showLoginSettings ? (
              <ChevronUp className="w-5 h-5 text-slate-400" />
            ) : (
              <ChevronDown className="w-5 h-5 text-slate-400" />
            )}
          </button>

          {showLoginSettings && (
            <div className="border-t border-slate-200 p-4">
              {loginSettingsLoading ? (
                <div className="py-8 text-center">
                  <Loader2 className="w-6 h-6 text-primary-500 mx-auto animate-spin" />
                  <p className="text-sm text-slate-500 mt-2">Loading settings...</p>
                </div>
              ) : loginSettings ? (
                <LoginPageSettingsForm
                  settings={loginSettings}
                  accountId={accountId!}
                  onUpdate={(updated) => setLoginSettings(updated)}
                />
              ) : (
                <p className="text-sm text-slate-500 text-center py-4">
                  Unable to load login settings
                </p>
              )}
            </div>
          )}
        </Card>
      )}

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
  onResetPassword: () => void;
  onRoleChange: (role: AccountRole) => void;
  onNavigateToProfile: () => void;
}

function MemberRow({
  member,
  canManage,
  isOpen,
  onToggleDropdown,
  onEdit,
  onRemove,
  onResetPassword,
  onNavigateToProfile,
}: MemberRowProps) {
  const roleConfig = ROLE_CONFIG[member.role];
  const RoleIcon = roleConfig.icon;
  const buttonRef = useRef<HTMLButtonElement>(null);
  const [dropdownPosition, setDropdownPosition] = useState({ top: 0, left: 0 });

  const enabledFeatures = member.feature_permissions
    ? Object.entries(member.feature_permissions)
        .filter(([, v]) => v?.access)
        .map(([k]) => k)
    : [];

  // Calculate dropdown position when opened
  useEffect(() => {
    if (isOpen && buttonRef.current) {
      const rect = buttonRef.current.getBoundingClientRect();
      setDropdownPosition({
        top: rect.bottom + 4,
        left: rect.right - 192, // 192px = w-48
      });
    }
  }, [isOpen]);

  return (
    <div
      className="flex items-center justify-between p-4 hover:bg-slate-50 transition-colors cursor-pointer"
      onClick={onNavigateToProfile}
    >
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
              ref={buttonRef}
              onClick={(e) => {
                e.stopPropagation();
                onToggleDropdown();
              }}
              className="p-2 hover:bg-slate-100 rounded-lg transition-colors"
            >
              <MoreVertical className="w-4 h-4 text-slate-400" />
            </button>
            {isOpen && createPortal(
              <>
                <div className="fixed inset-0 z-[100]" onClick={(e) => { e.stopPropagation(); onToggleDropdown(); }} />
                <div
                  className="fixed w-48 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-[101]"
                  style={{ top: dropdownPosition.top, left: dropdownPosition.left }}
                >
                  <button
                    onClick={(e) => { e.stopPropagation(); onEdit(); }}
                    className="w-full flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                  >
                    <Edit2 className="w-4 h-4" />
                    Edit Permissions
                  </button>
                  <button
                    onClick={(e) => { e.stopPropagation(); onResetPassword(); }}
                    className="w-full flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                  >
                    <KeyRound className="w-4 h-4" />
                    Reset Password
                  </button>
                  <button
                    onClick={(e) => { e.stopPropagation(); onRemove(); }}
                    className="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                  >
                    <Trash2 className="w-4 h-4" />
                    Remove Member
                  </button>
                </div>
              </>,
              getPortalRoot()
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

interface LoginPageSettingsFormProps {
  settings: LoginSettings;
  accountId: number;
  onUpdate: (settings: LoginSettings) => void;
}

function LoginPageSettingsForm({ settings, accountId, onUpdate }: LoginPageSettingsFormProps) {
  const [logoUrl, setLogoUrl] = useState(settings.logo_url);
  const [title, setTitle] = useState(settings.title);
  const [redirectUrl, setRedirectUrl] = useState(settings.redirect_url);
  const [loginPageUrl, setLoginPageUrl] = useState(settings.login_page_url || '');
  const [saving, setSaving] = useState(false);
  const [copied, setCopied] = useState(false);
  const [success, setSuccess] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);

  const handleSave = async () => {
    setSaving(true);
    setSuccess(false);
    setSaveError(null);
    try {
      const updated = await accountsApi.updateLoginSettings(accountId, {
        logo_url: logoUrl,
        title,
        redirect_url: redirectUrl,
        login_page_url: loginPageUrl || null,
      });
      onUpdate(updated);
      setSuccess(true);
      setTimeout(() => setSuccess(false), 3000);
    } catch (err) {
      setSaveError(err instanceof Error ? err.message : 'Failed to save settings');
    } finally {
      setSaving(false);
    }
  };

  const copyShortcode = () => {
    navigator.clipboard.writeText(settings.shortcode);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="space-y-6">
      {/* Instructions */}
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 className="font-medium text-blue-800 mb-2">How to set up the login page</h4>
        <ol className="text-sm text-blue-700 space-y-1 list-decimal list-inside">
          <li>Create a new page in WordPress (e.g., "Team Login")</li>
          <li>Copy the shortcode below and paste it into the page</li>
          <li>Publish the page and share the URL with your team members</li>
        </ol>
      </div>

      {/* Shortcode */}
      <div>
        <label className="block text-sm font-medium text-slate-700 mb-2">
          Login Shortcode
        </label>
        <div className="flex gap-2">
          <code className="flex-1 bg-slate-100 px-4 py-3 rounded-lg text-sm font-mono text-slate-700 overflow-x-auto">
            {settings.shortcode}
          </code>
          <Button
            variant="outline"
            onClick={copyShortcode}
            icon={copied ? <Check className="w-4 h-4 text-green-500" /> : <Copy className="w-4 h-4" />}
          >
            {copied ? 'Copied!' : 'Copy'}
          </Button>
        </div>
      </div>

      {/* Login Page URL (optional) */}
      <Input
        label="Login Page URL (optional)"
        type="url"
        value={loginPageUrl}
        onChange={(e) => setLoginPageUrl(e.target.value)}
        placeholder="https://yoursite.com/team-login"
        helper="Enter the URL where you placed the shortcode. This helps team members find the login page."
      />

      {/* Customization */}
      <div className="border-t border-slate-200 pt-6">
        <h4 className="font-medium text-slate-900 mb-4 flex items-center gap-2">
          <Settings className="w-4 h-4" />
          Customize Login Form
        </h4>

        <div className="space-y-4">
          <Input
            label="Logo URL (optional)"
            type="url"
            value={logoUrl}
            onChange={(e) => setLogoUrl(e.target.value)}
            placeholder="https://yoursite.com/logo.png"
            helper="URL to your company logo (recommended: 200px width max)"
          />

          <Input
            label="Login Title"
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            placeholder="Team Login"
          />

          <Input
            label="Redirect URL"
            type="url"
            value={redirectUrl}
            onChange={(e) => setRedirectUrl(e.target.value)}
            placeholder="/wp-admin/admin.php?page=peanut-app"
            helper="Where to redirect users after successful login"
          />
        </div>
      </div>

      {/* Save button */}
      <div className="flex items-center justify-between pt-4 border-t border-slate-200">
        <div>
          {success && (
            <span className="text-sm text-green-600 flex items-center gap-1">
              <Check className="w-4 h-4" />
              Settings saved successfully
            </span>
          )}
          {saveError && (
            <span className="text-sm text-red-600 flex items-center gap-1">
              <AlertCircle className="w-4 h-4" />
              {saveError}
            </span>
          )}
        </div>
        <div className="flex gap-3">
          {loginPageUrl && (
            <Button
              variant="outline"
              onClick={() => window.open(loginPageUrl, '_blank')}
              icon={<ExternalLink className="w-4 h-4" />}
            >
              Preview
            </Button>
          )}
          <Button onClick={handleSave} disabled={saving}>
            {saving ? 'Saving...' : 'Save Settings'}
          </Button>
        </div>
      </div>
    </div>
  );
}

import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  ArrowLeft,
  Users,
  Crown,
  Shield,
  Eye,
  Check,
  X,
  Loader2,
  AlertCircle,
  KeyRound,
  Trash2,
  Calendar,
  Mail,
  Save,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Badge } from '../components/common';
import { accountsApi } from '../api/endpoints';
import { useAccountStore } from '../store/useAccountStore';
import type { AccountMember, AccountRole, FeaturePermissions } from '../types';

const ROLE_CONFIG: Record<AccountRole, { label: string; color: string; icon: typeof Crown; description: string }> = {
  owner: { label: 'Owner', color: 'amber', icon: Crown, description: 'Full access to all features and settings' },
  admin: { label: 'Admin', color: 'purple', icon: Shield, description: 'Manage team and all features' },
  member: { label: 'Member', color: 'blue', icon: Users, description: 'Access to assigned features only' },
  viewer: { label: 'Viewer', color: 'slate', icon: Eye, description: 'Read-only access to assigned features' },
};

const FEATURES = [
  { id: 'utm', name: 'UTM Builder', tier: 'free', description: 'Create and manage UTM campaign links' },
  { id: 'links', name: 'Short Links', tier: 'free', description: 'Create and track shortened URLs' },
  { id: 'contacts', name: 'Contacts', tier: 'free', description: 'Manage leads and customer contacts' },
  { id: 'webhooks', name: 'Webhooks', tier: 'free', description: 'Process incoming webhook data' },
  { id: 'visitors', name: 'Visitors', tier: 'pro', description: 'Track website visitor activity' },
  { id: 'attribution', name: 'Attribution', tier: 'pro', description: 'Multi-touch attribution tracking' },
  { id: 'analytics', name: 'Analytics', tier: 'pro', description: 'Advanced analytics and reporting' },
  { id: 'popups', name: 'Popups', tier: 'pro', description: 'Create and manage popups' },
  { id: 'monitor', name: 'Site Monitor', tier: 'agency', description: 'Monitor client sites remotely' },
] as const;

type FeatureId = typeof FEATURES[number]['id'];

export default function TeamMemberProfile() {
  const { userId } = useParams<{ userId: string }>();
  const navigate = useNavigate();
  const [member, setMember] = useState<AccountMember | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [permissions, setPermissions] = useState<FeaturePermissions>({});
  const [selectedRole, setSelectedRole] = useState<AccountRole>('member');
  const [hasChanges, setHasChanges] = useState(false);

  // Get account context from store
  const { account, isInitialized, fetchCurrentUser } = useAccountStore();
  const accountId = account?.id ?? null;
  const currentUserRole: AccountRole = account?.role ?? 'owner';
  const canManage = currentUserRole === 'owner' || currentUserRole === 'admin';

  // Fetch account data if not initialized
  useEffect(() => {
    if (!isInitialized) {
      fetchCurrentUser();
    }
  }, [isInitialized, fetchCurrentUser]);

  // Load member data
  useEffect(() => {
    if (isInitialized && accountId && userId) {
      loadMember();
    } else if (isInitialized && !accountId) {
      setLoading(false);
      setError('No account found');
    }
  }, [isInitialized, accountId, userId]);

  const loadMember = async () => {
    if (!accountId || !userId) return;

    try {
      setLoading(true);
      const members = await accountsApi.getMembers(accountId);
      const foundMember = members.find((m) => m.user_id === parseInt(userId, 10));

      if (foundMember) {
        setMember(foundMember);
        setSelectedRole(foundMember.role);
        setPermissions(foundMember.feature_permissions || {});
      } else {
        setError('Team member not found');
      }
    } catch (err) {
      setError('Failed to load team member');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const togglePermission = (featureId: FeatureId) => {
    setPermissions((prev) => ({
      ...prev,
      [featureId]: { access: !prev[featureId]?.access },
    }));
    setHasChanges(true);
  };

  const handleRoleChange = (role: AccountRole) => {
    setSelectedRole(role);
    setHasChanges(true);

    // Set default permissions based on role
    if (role === 'admin') {
      const allPerms: FeaturePermissions = {};
      FEATURES.forEach((f) => {
        allPerms[f.id as FeatureId] = { access: true };
      });
      setPermissions(allPerms);
    } else if (role === 'member' || role === 'viewer') {
      // Keep current permissions or set free features
      if (Object.keys(permissions).length === 0) {
        const freePerms: FeaturePermissions = {};
        FEATURES.filter((f) => f.tier === 'free').forEach((f) => {
          freePerms[f.id as FeatureId] = { access: true };
        });
        setPermissions(freePerms);
      }
    }
  };

  const handleSave = async () => {
    if (!accountId || !member) return;

    setSaving(true);
    setError(null);
    setSuccessMessage(null);

    try {
      // Update role if changed
      if (selectedRole !== member.role) {
        await accountsApi.updateMemberRole(accountId, member.user_id, selectedRole);
      }

      // Update permissions
      await accountsApi.updateMemberPermissions(accountId, member.user_id, permissions);

      setSuccessMessage('Changes saved successfully');
      setHasChanges(false);

      // Reload member data
      await loadMember();

      setTimeout(() => setSuccessMessage(null), 3000);
    } catch (err) {
      setError('Failed to save changes');
      console.error(err);
    } finally {
      setSaving(false);
    }
  };

  const handleResetPassword = async () => {
    if (!accountId || !member) return;
    if (!confirm(`Send password reset email to ${member.user_email}?`)) return;

    try {
      await accountsApi.resetMemberPassword(accountId, member.user_id);
      setSuccessMessage('Password reset email sent');
      setTimeout(() => setSuccessMessage(null), 3000);
    } catch (err) {
      setError('Failed to send password reset email');
      console.error(err);
    }
  };

  const handleRemoveMember = async () => {
    if (!accountId || !member) return;
    if (!confirm(`Are you sure you want to remove ${member.display_name || member.user_email} from the team?`)) return;

    try {
      await accountsApi.removeMember(accountId, member.user_id);
      navigate('/team');
    } catch (err) {
      setError('Failed to remove team member');
      console.error(err);
    }
  };

  const enabledCount = Object.values(permissions).filter((p) => p?.access).length;

  // Loading state
  if (!isInitialized || loading) {
    return (
      <Layout title="Team Member" description="Loading..." pageGuideId="team">
        <Card>
          <div className="py-12 text-center">
            <Loader2 className="w-8 h-8 text-primary-500 mx-auto mb-4 animate-spin" />
            <p className="text-slate-500">Loading team member...</p>
          </div>
        </Card>
      </Layout>
    );
  }

  // Error state
  if (error && !member) {
    return (
      <Layout title="Team Member" description="Error" pageGuideId="team">
        <Card>
          <div className="py-12 text-center">
            <AlertCircle className="w-12 h-12 text-red-400 mx-auto mb-4" />
            <p className="text-slate-900 font-medium mb-2">{error}</p>
            <Button variant="outline" onClick={() => navigate('/team')}>
              Back to Team
            </Button>
          </div>
        </Card>
      </Layout>
    );
  }

  if (!member) return null;

  const roleConfig = ROLE_CONFIG[member.role];
  const RoleIcon = roleConfig.icon;

  return (
    <Layout
      title={member.display_name || member.user_login}
      description="Manage team member permissions and access"
      pageGuideId="team"
    >
      {/* Back Link */}
      <button
        onClick={() => navigate('/team')}
        className="flex items-center gap-2 text-slate-600 hover:text-slate-900 mb-6 transition-colors"
      >
        <ArrowLeft className="w-4 h-4" />
        Back to Team
      </button>

      {/* Status Messages */}
      {error && (
        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3">
          <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0" />
          <span className="text-red-700">{error}</span>
        </div>
      )}

      {successMessage && (
        <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
          <Check className="w-5 h-5 text-green-500 flex-shrink-0" />
          <span className="text-green-700">{successMessage}</span>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column - Member Info */}
        <div className="lg:col-span-1 space-y-6">
          {/* Profile Card */}
          <Card className="p-6">
            <div className="text-center">
              <div className="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span className="text-3xl text-primary-700 font-medium">
                  {member.display_name?.charAt(0).toUpperCase() || member.user_email.charAt(0).toUpperCase()}
                </span>
              </div>
              <h2 className="text-xl font-semibold text-slate-900">
                {member.display_name || member.user_login}
              </h2>
              <p className="text-slate-500 mt-1">{member.user_email}</p>
              <Badge
                variant={member.role === 'owner' ? 'warning' : member.role === 'admin' ? 'primary' : 'default'}
                className="mt-3"
              >
                <RoleIcon className="w-3 h-3 mr-1" />
                {roleConfig.label}
              </Badge>
            </div>

            <div className="mt-6 pt-6 border-t border-slate-200 space-y-3">
              <div className="flex items-center gap-3 text-sm">
                <Mail className="w-4 h-4 text-slate-400" />
                <span className="text-slate-600">{member.user_email}</span>
              </div>
              {member.created_at && (
                <div className="flex items-center gap-3 text-sm">
                  <Calendar className="w-4 h-4 text-slate-400" />
                  <span className="text-slate-600">
                    Joined {new Date(member.created_at).toLocaleDateString()}
                  </span>
                </div>
              )}
            </div>
          </Card>

          {/* Quick Actions Card */}
          {canManage && member.role !== 'owner' && (
            <Card className="p-6">
              <h3 className="font-medium text-slate-900 mb-4">Quick Actions</h3>
              <div className="space-y-3">
                <Button
                  variant="outline"
                  className="w-full justify-start"
                  onClick={handleResetPassword}
                  icon={<KeyRound className="w-4 h-4" />}
                >
                  Send Password Reset
                </Button>
                <Button
                  variant="outline"
                  className="w-full justify-start text-red-600 hover:bg-red-50 hover:border-red-300"
                  onClick={handleRemoveMember}
                  icon={<Trash2 className="w-4 h-4" />}
                >
                  Remove from Team
                </Button>
              </div>
            </Card>
          )}
        </div>

        {/* Right Column - Permissions */}
        <div className="lg:col-span-2 space-y-6">
          {/* Role Selection */}
          {canManage && member.role !== 'owner' && (
            <Card className="p-6">
              <h3 className="font-medium text-slate-900 mb-4">Role</h3>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {(['admin', 'member', 'viewer'] as const).map((role) => {
                  const config = ROLE_CONFIG[role];
                  const Icon = config.icon;
                  const isSelected = selectedRole === role;
                  return (
                    <button
                      key={role}
                      type="button"
                      onClick={() => handleRoleChange(role)}
                      className={`p-4 rounded-lg border-2 text-left transition-all ${
                        isSelected
                          ? 'border-primary-500 bg-primary-50'
                          : 'border-slate-200 hover:border-slate-300'
                      }`}
                    >
                      <Icon className={`w-5 h-5 mb-2 ${isSelected ? 'text-primary-600' : 'text-slate-400'}`} />
                      <div className="font-medium text-sm">{config.label}</div>
                      <p className="text-xs text-slate-500 mt-1">{config.description}</p>
                    </button>
                  );
                })}
              </div>
            </Card>
          )}

          {/* Feature Permissions */}
          <Card className="p-6">
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-medium text-slate-900">Feature Access</h3>
              <span className="text-sm text-slate-500">{enabledCount} of {FEATURES.length} enabled</span>
            </div>

            {selectedRole === 'admin' ? (
              <div className="p-4 bg-purple-50 border border-purple-200 rounded-lg text-sm text-purple-700">
                Admins have full access to all features. Individual permissions cannot be customized.
              </div>
            ) : member.role === 'owner' ? (
              <div className="p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700">
                Owners have full access to all features and settings.
              </div>
            ) : (
              <div className="space-y-2">
                {FEATURES.map((feature) => {
                  const isEnabled = permissions[feature.id as FeatureId]?.access;
                  return (
                    <div
                      key={feature.id}
                      className={`flex items-center justify-between p-4 rounded-lg border transition-colors ${
                        isEnabled ? 'border-green-200 bg-green-50/50' : 'border-slate-200 hover:bg-slate-50'
                      } ${!canManage ? 'cursor-default' : 'cursor-pointer'}`}
                      onClick={() => canManage && togglePermission(feature.id as FeatureId)}
                    >
                      <div className="flex items-center gap-3">
                        {isEnabled ? (
                          <Check className="w-5 h-5 text-green-500" />
                        ) : (
                          <X className="w-5 h-5 text-slate-300" />
                        )}
                        <div>
                          <div className="flex items-center gap-2">
                            <span className="font-medium text-slate-700">{feature.name}</span>
                            {feature.tier !== 'free' && (
                              <Badge variant="default" size="sm">
                                {feature.tier}
                              </Badge>
                            )}
                          </div>
                          <p className="text-xs text-slate-500 mt-0.5">{feature.description}</p>
                        </div>
                      </div>
                      {canManage && (
                        <button
                          type="button"
                          onClick={(e) => {
                            e.stopPropagation();
                            togglePermission(feature.id as FeatureId);
                          }}
                          className={`w-11 h-6 rounded-full transition-colors flex-shrink-0 ${
                            isEnabled ? 'bg-green-500' : 'bg-slate-200'
                          }`}
                        >
                          <span
                            className={`block w-5 h-5 bg-white rounded-full shadow transition-transform ${
                              isEnabled ? 'translate-x-[22px]' : 'translate-x-0.5'
                            }`}
                          />
                        </button>
                      )}
                    </div>
                  );
                })}
              </div>
            )}
          </Card>

          {/* Save Button */}
          {canManage && member.role !== 'owner' && (
            <div className="flex justify-end">
              <Button
                onClick={handleSave}
                disabled={saving || !hasChanges}
                icon={saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
              >
                {saving ? 'Saving...' : 'Save Changes'}
              </Button>
            </div>
          )}
        </div>
      </div>
    </Layout>
  );
}

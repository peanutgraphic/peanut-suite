import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { createColumnHelper } from '@tanstack/react-table';
import {
  Plus,
  Trash2,
  UserCog,
  Crown,
  Shield,
  User,
  Eye,
  ArrowRightLeft,
} from 'lucide-react';
import { Layout } from '../components/layout';
import {
  Card,
  Button,
  Input,
  Table,
  Badge,
  Modal,
  ConfirmModal,
  Select,
  NoDataEmptyState,
} from '../components/common';
import { accountsApi } from '../api/endpoints';
import type { AccountMember, MemberRole } from '../types';
import { useCurrentAccount, useIsAccountOwner, useCanManageTeam, toast } from '../store';

const columnHelper = createColumnHelper<AccountMember>();

const roleOptions = [
  { value: 'admin', label: 'Admin' },
  { value: 'member', label: 'Member' },
  { value: 'viewer', label: 'Viewer' },
];

const roleIcons: Record<MemberRole, React.ReactNode> = {
  owner: <Crown className="w-4 h-4 text-amber-500" />,
  admin: <Shield className="w-4 h-4 text-blue-500" />,
  member: <User className="w-4 h-4 text-slate-500" />,
  viewer: <Eye className="w-4 h-4 text-slate-400" />,
};

const roleLabels: Record<MemberRole, string> = {
  owner: 'Owner',
  admin: 'Admin',
  member: 'Member',
  viewer: 'Viewer',
};

const roleBadgeVariants: Record<MemberRole, 'warning' | 'info' | 'default' | 'success'> = {
  owner: 'warning',
  admin: 'info',
  member: 'default',
  viewer: 'default',
};

export default function Team() {
  const queryClient = useQueryClient();
  const currentAccount = useCurrentAccount();
  const isOwner = useIsAccountOwner();
  const canManageTeam = useCanManageTeam();

  const [inviteModalOpen, setInviteModalOpen] = useState(false);
  const [editMember, setEditMember] = useState<AccountMember | null>(null);
  const [deleteMember, setDeleteMember] = useState<AccountMember | null>(null);
  const [transferOwner, setTransferOwner] = useState<AccountMember | null>(null);

  const [inviteForm, setInviteForm] = useState({
    email: '',
    role: 'member' as MemberRole,
  });

  const accountId = currentAccount?.id || 0;

  const { data, isLoading } = useQuery({
    queryKey: ['team-members', accountId],
    queryFn: () => accountsApi.getMembers(accountId),
    enabled: !!accountId,
  });

  const addMemberMutation = useMutation({
    mutationFn: ({ email, role }: { email: string; role: MemberRole }) =>
      accountsApi.addMember(accountId, email, role),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['team-members', accountId] });
      setInviteModalOpen(false);
      setInviteForm({ email: '', role: 'member' });
      toast.success('Team member added');
    },
    onError: (error) => {
      toast.error(error instanceof Error ? error.message : 'Failed to add member');
    },
  });

  const updateRoleMutation = useMutation({
    mutationFn: ({ userId, role }: { userId: number; role: MemberRole }) =>
      accountsApi.updateMember(accountId, userId, role),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['team-members', accountId] });
      setEditMember(null);
      toast.success('Role updated');
    },
    onError: (error) => {
      toast.error(error instanceof Error ? error.message : 'Failed to update role');
    },
  });

  const removeMemberMutation = useMutation({
    mutationFn: (userId: number) => accountsApi.removeMember(accountId, userId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['team-members', accountId] });
      setDeleteMember(null);
      toast.success('Member removed');
    },
    onError: (error) => {
      toast.error(error instanceof Error ? error.message : 'Failed to remove member');
    },
  });

  const transferOwnershipMutation = useMutation({
    mutationFn: (newOwnerId: number) =>
      accountsApi.transferOwnership(accountId, newOwnerId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['team-members', accountId] });
      setTransferOwner(null);
      toast.success('Ownership transferred');
      // Reload to refresh permissions
      window.location.reload();
    },
    onError: (error) => {
      toast.error(error instanceof Error ? error.message : 'Failed to transfer ownership');
    },
  });

  const members = data?.members || [];

  const columns = [
    columnHelper.accessor('display_name', {
      header: 'Member',
      cell: (info) => (
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center">
            <User className="w-5 h-5 text-primary-600 dark:text-primary-400" />
          </div>
          <div>
            <p className="font-medium text-slate-900 dark:text-slate-100">
              {info.getValue() || info.row.original.user_login}
            </p>
            <p className="text-sm text-slate-500 dark:text-slate-400">
              {info.row.original.user_email}
            </p>
          </div>
        </div>
      ),
    }),
    columnHelper.accessor('role', {
      header: 'Role',
      cell: (info) => (
        <div className="flex items-center gap-2">
          {roleIcons[info.getValue()]}
          <Badge variant={roleBadgeVariants[info.getValue()]}>
            {roleLabels[info.getValue()]}
          </Badge>
        </div>
      ),
    }),
    columnHelper.accessor('accepted_at', {
      header: 'Status',
      cell: (info) => (
        <Badge variant={info.getValue() ? 'success' : 'warning'}>
          {info.getValue() ? 'Active' : 'Pending'}
        </Badge>
      ),
    }),
    columnHelper.accessor('created_at', {
      header: 'Joined',
      cell: (info) => (
        <span className="text-slate-600 dark:text-slate-400">
          {new Date(info.getValue()).toLocaleDateString()}
        </span>
      ),
    }),
    columnHelper.display({
      id: 'actions',
      header: '',
      cell: (info) => {
        const member = info.row.original;
        const isOwnerMember = member.role === 'owner';

        if (!canManageTeam || isOwnerMember) {
          return null;
        }

        return (
          <div className="flex items-center gap-1 justify-end">
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setEditMember(member)}
              title="Change role"
            >
              <UserCog className="w-4 h-4" />
            </Button>
            {isOwner && (
              <Button
                variant="ghost"
                size="sm"
                onClick={() => setTransferOwner(member)}
                title="Transfer ownership"
              >
                <ArrowRightLeft className="w-4 h-4" />
              </Button>
            )}
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setDeleteMember(member)}
              className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
              title="Remove member"
            >
              <Trash2 className="w-4 h-4" />
            </Button>
          </div>
        );
      },
    }),
  ];

  return (
    <Layout
      title="Team"
      description={`Manage team members for ${currentAccount?.name || 'your account'}`}
    >
      <div className="p-4 md:p-6 space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h2 className="text-xl font-semibold text-slate-900 dark:text-slate-100">
              Team Members
            </h2>
            <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
              {members.length} member{members.length !== 1 ? 's' : ''} &middot;{' '}
              {currentAccount?.max_users || 1} max allowed
            </p>
          </div>

          {canManageTeam && (
            <Button onClick={() => setInviteModalOpen(true)}>
              <Plus className="w-4 h-4 mr-2" />
              Add Member
            </Button>
          )}
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <Card className="p-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                <Crown className="w-5 h-5 text-amber-600 dark:text-amber-400" />
              </div>
              <div>
                <p className="text-2xl font-semibold text-slate-900 dark:text-slate-100">
                  {members.filter((m) => m.role === 'owner').length}
                </p>
                <p className="text-sm text-slate-500 dark:text-slate-400">Owner</p>
              </div>
            </div>
          </Card>

          <Card className="p-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                <Shield className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <p className="text-2xl font-semibold text-slate-900 dark:text-slate-100">
                  {members.filter((m) => m.role === 'admin').length}
                </p>
                <p className="text-sm text-slate-500 dark:text-slate-400">Admins</p>
              </div>
            </div>
          </Card>

          <Card className="p-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-slate-100 dark:bg-slate-800 rounded-lg flex items-center justify-center">
                <User className="w-5 h-5 text-slate-600 dark:text-slate-400" />
              </div>
              <div>
                <p className="text-2xl font-semibold text-slate-900 dark:text-slate-100">
                  {members.filter((m) => m.role === 'member').length}
                </p>
                <p className="text-sm text-slate-500 dark:text-slate-400">Members</p>
              </div>
            </div>
          </Card>

          <Card className="p-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <Eye className="w-5 h-5 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <p className="text-2xl font-semibold text-slate-900 dark:text-slate-100">
                  {members.filter((m) => m.role === 'viewer').length}
                </p>
                <p className="text-sm text-slate-500 dark:text-slate-400">Viewers</p>
              </div>
            </div>
          </Card>
        </div>

        {/* Members Table */}
        <Card>
          {members.length === 0 && !isLoading ? (
            <NoDataEmptyState
              title="No team members"
              description="Add team members to collaborate on this account."
              action={
                <Button onClick={() => setInviteModalOpen(true)}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add Member
                </Button>
              }
            />
          ) : (
            <Table
              columns={columns}
              data={members}
              loading={isLoading}
            />
          )}
        </Card>

        {/* Role Permissions Info */}
        <Card className="p-6">
          <h3 className="font-semibold text-slate-900 dark:text-slate-100 mb-4">
            Role Permissions
          </h3>
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <Crown className="w-4 h-4 text-amber-500" />
                <span className="font-medium text-slate-900 dark:text-slate-100">Owner</span>
              </div>
              <ul className="text-sm text-slate-600 dark:text-slate-400 space-y-1 ml-6">
                <li>Full account access</li>
                <li>Manage team & billing</li>
                <li>Transfer ownership</li>
                <li>Delete account</li>
              </ul>
            </div>

            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <Shield className="w-4 h-4 text-blue-500" />
                <span className="font-medium text-slate-900 dark:text-slate-100">Admin</span>
              </div>
              <ul className="text-sm text-slate-600 dark:text-slate-400 space-y-1 ml-6">
                <li>Full data access</li>
                <li>Manage team members</li>
                <li>Manage API keys</li>
                <li>View audit log</li>
              </ul>
            </div>

            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <User className="w-4 h-4 text-slate-500" />
                <span className="font-medium text-slate-900 dark:text-slate-100">Member</span>
              </div>
              <ul className="text-sm text-slate-600 dark:text-slate-400 space-y-1 ml-6">
                <li>Create & edit data</li>
                <li>View all data</li>
                <li>Export data</li>
              </ul>
            </div>

            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <Eye className="w-4 h-4 text-slate-400" />
                <span className="font-medium text-slate-900 dark:text-slate-100">Viewer</span>
              </div>
              <ul className="text-sm text-slate-600 dark:text-slate-400 space-y-1 ml-6">
                <li>View all data</li>
                <li>Read-only access</li>
              </ul>
            </div>
          </div>
        </Card>
      </div>

      {/* Add Member Modal */}
      <Modal
        isOpen={inviteModalOpen}
        onClose={() => setInviteModalOpen(false)}
        title="Add Team Member"
      >
        <div className="space-y-4">
          <Input
            label="Email Address"
            type="email"
            value={inviteForm.email}
            onChange={(e) => setInviteForm({ ...inviteForm, email: e.target.value })}
            placeholder="user@example.com"
            helper="Enter the email of an existing WordPress user"
          />

          <Select
            label="Role"
            value={inviteForm.role}
            onChange={(e) => setInviteForm({ ...inviteForm, role: e.target.value as MemberRole })}
            options={roleOptions}
          />

          <div className="flex justify-end gap-3 pt-4">
            <Button variant="outline" onClick={() => setInviteModalOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={() =>
                addMemberMutation.mutate({
                  email: inviteForm.email,
                  role: inviteForm.role,
                })
              }
              disabled={!inviteForm.email || addMemberMutation.isPending}
            >
              {addMemberMutation.isPending ? 'Adding...' : 'Add Member'}
            </Button>
          </div>
        </div>
      </Modal>

      {/* Edit Role Modal */}
      <Modal
        isOpen={!!editMember}
        onClose={() => setEditMember(null)}
        title="Change Role"
      >
        {editMember && (
          <div className="space-y-4">
            <p className="text-slate-600 dark:text-slate-400">
              Change role for <strong>{editMember.display_name || editMember.user_email}</strong>
            </p>

            <Select
              label="New Role"
              value={editMember.role}
              onChange={(e) =>
                setEditMember({ ...editMember, role: e.target.value as MemberRole })
              }
              options={roleOptions}
            />

            <div className="flex justify-end gap-3 pt-4">
              <Button variant="outline" onClick={() => setEditMember(null)}>
                Cancel
              </Button>
              <Button
                onClick={() =>
                  updateRoleMutation.mutate({
                    userId: editMember.user_id,
                    role: editMember.role,
                  })
                }
                disabled={updateRoleMutation.isPending}
              >
                {updateRoleMutation.isPending ? 'Updating...' : 'Update Role'}
              </Button>
            </div>
          </div>
        )}
      </Modal>

      {/* Remove Member Confirmation */}
      <ConfirmModal
        isOpen={!!deleteMember}
        onClose={() => setDeleteMember(null)}
        onConfirm={() => deleteMember && removeMemberMutation.mutate(deleteMember.user_id)}
        title="Remove Team Member"
        message={`Are you sure you want to remove ${deleteMember?.display_name || deleteMember?.user_email} from this account? They will lose access immediately.`}
        confirmText="Remove"
        variant="danger"
        loading={removeMemberMutation.isPending}
      />

      {/* Transfer Ownership Confirmation */}
      <ConfirmModal
        isOpen={!!transferOwner}
        onClose={() => setTransferOwner(null)}
        onConfirm={() =>
          transferOwner && transferOwnershipMutation.mutate(transferOwner.user_id)
        }
        title="Transfer Ownership"
        message={`Are you sure you want to transfer ownership to ${transferOwner?.display_name || transferOwner?.user_email}? You will become an admin and they will become the owner.`}
        confirmText="Transfer Ownership"
        variant="danger"
        loading={transferOwnershipMutation.isPending}
      />
    </Layout>
  );
}

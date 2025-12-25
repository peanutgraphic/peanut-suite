import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { createColumnHelper } from '@tanstack/react-table';
import {
  Plus,
  Trash2,
  Key,
  Copy,
  RefreshCw,
  Eye,
  EyeOff,
  CheckCircle,
  AlertCircle,
  Clock,
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
  NoDataEmptyState,
  StatCard,
} from '../components/common';
import { accountsApi } from '../api/endpoints';
import type { ApiKey, ApiKeyWithSecret, ApiKeyScope } from '../types';
import { useCurrentAccount, useCanManageApiKeys, toast } from '../store';

const columnHelper = createColumnHelper<ApiKey>();

const scopeOptions = [
  { value: 'read', label: 'Read - View data' },
  { value: 'write', label: 'Write - Create and update' },
  { value: 'delete', label: 'Delete - Remove data' },
  { value: 'admin', label: 'Admin - Full access' },
];

const scopeBadgeColors: Record<ApiKeyScope, 'default' | 'info' | 'warning' | 'success'> = {
  read: 'default',
  write: 'info',
  delete: 'warning',
  admin: 'success',
};

export default function ApiKeys() {
  const queryClient = useQueryClient();
  const currentAccount = useCurrentAccount();
  const canManageApiKeys = useCanManageApiKeys();

  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [secretModal, setSecretModal] = useState<ApiKeyWithSecret | null>(null);
  const [showSecret, setShowSecret] = useState(false);
  const [revokeKey, setRevokeKey] = useState<ApiKey | null>(null);
  const [regenerateKey, setRegenerateKey] = useState<ApiKey | null>(null);
  const [includeRevoked, setIncludeRevoked] = useState(false);

  const [createForm, setCreateForm] = useState({
    name: '',
    scopes: ['read'] as ApiKeyScope[],
    expires_at: '',
  });

  const accountId = currentAccount?.id || 0;

  const { data, isLoading } = useQuery({
    queryKey: ['api-keys', accountId, includeRevoked],
    queryFn: () => accountsApi.getApiKeys(accountId, includeRevoked),
    enabled: !!accountId,
  });

  const createMutation = useMutation({
    mutationFn: () =>
      accountsApi.createApiKey(accountId, {
        name: createForm.name,
        scopes: createForm.scopes,
        expires_at: createForm.expires_at || undefined,
      }),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['api-keys', accountId] });
      setCreateModalOpen(false);
      setSecretModal(data.api_key);
      setCreateForm({ name: '', scopes: ['read'], expires_at: '' });
      toast.success('API key created');
    },
    onError: (error) => {
      toast.error(error instanceof Error ? error.message : 'Failed to create API key');
    },
  });

  const revokeMutation = useMutation({
    mutationFn: (keyId: number) => accountsApi.revokeApiKey(accountId, keyId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['api-keys', accountId] });
      setRevokeKey(null);
      toast.success('API key revoked');
    },
    onError: (error) => {
      toast.error(error instanceof Error ? error.message : 'Failed to revoke API key');
    },
  });

  const regenerateMutation = useMutation({
    mutationFn: (keyId: number) => accountsApi.regenerateApiKey(accountId, keyId),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['api-keys', accountId] });
      setRegenerateKey(null);
      setSecretModal(data.api_key);
      toast.success('API key regenerated');
    },
    onError: (error) => {
      toast.error(error instanceof Error ? error.message : 'Failed to regenerate API key');
    },
  });

  const copyToClipboard = async (text: string) => {
    try {
      await navigator.clipboard.writeText(text);
      toast.success('Copied to clipboard');
    } catch {
      toast.error('Failed to copy');
    }
  };

  const handleScopeToggle = (scope: ApiKeyScope) => {
    setCreateForm((prev) => ({
      ...prev,
      scopes: prev.scopes.includes(scope)
        ? prev.scopes.filter((s) => s !== scope)
        : [...prev.scopes, scope],
    }));
  };

  const apiKeys = data?.api_keys || [];
  const stats = data?.stats || { total: 0, active: 0, revoked: 0, used_last_30_days: 0 };

  const columns = [
    columnHelper.accessor('name', {
      header: 'Name',
      cell: (info) => (
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
            <Key className="w-5 h-5 text-primary-600 dark:text-primary-400" />
          </div>
          <div>
            <p className="font-medium text-slate-900 dark:text-slate-100">
              {info.getValue()}
            </p>
            <p className="text-sm text-slate-500 dark:text-slate-400 font-mono">
              {info.row.original.key_id}
            </p>
          </div>
        </div>
      ),
    }),
    columnHelper.accessor('scopes', {
      header: 'Permissions',
      cell: (info) => (
        <div className="flex flex-wrap gap-1">
          {info.getValue().map((scope) => (
            <Badge key={scope} variant={scopeBadgeColors[scope]}>
              {scope}
            </Badge>
          ))}
        </div>
      ),
    }),
    columnHelper.accessor('last_used_at', {
      header: 'Last Used',
      cell: (info) => {
        const value = info.getValue();
        if (!value) {
          return <span className="text-slate-400">Never</span>;
        }
        return (
          <span className="text-slate-600 dark:text-slate-400">
            {new Date(value).toLocaleDateString()}
          </span>
        );
      },
    }),
    columnHelper.accessor('expires_at', {
      header: 'Expires',
      cell: (info) => {
        const value = info.getValue();
        if (!value) {
          return <span className="text-slate-400">Never</span>;
        }
        const isExpired = new Date(value) < new Date();
        return (
          <span className={isExpired ? 'text-red-600' : 'text-slate-600 dark:text-slate-400'}>
            {new Date(value).toLocaleDateString()}
          </span>
        );
      },
    }),
    columnHelper.accessor('revoked_at', {
      header: 'Status',
      cell: (info) => {
        const revoked = info.getValue();
        const expires = info.row.original.expires_at;
        const isExpired = expires && new Date(expires) < new Date();

        if (revoked) {
          return (
            <Badge variant="danger" className="flex items-center gap-1 w-fit">
              <AlertCircle className="w-3 h-3" />
              Revoked
            </Badge>
          );
        }
        if (isExpired) {
          return (
            <Badge variant="warning" className="flex items-center gap-1 w-fit">
              <Clock className="w-3 h-3" />
              Expired
            </Badge>
          );
        }
        return (
          <Badge variant="success" className="flex items-center gap-1 w-fit">
            <CheckCircle className="w-3 h-3" />
            Active
          </Badge>
        );
      },
    }),
    columnHelper.display({
      id: 'actions',
      header: '',
      cell: (info) => {
        const key = info.row.original;
        const isRevoked = !!key.revoked_at;

        if (!canManageApiKeys || isRevoked) {
          return null;
        }

        return (
          <div className="flex items-center gap-1 justify-end">
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setRegenerateKey(key)}
              title="Regenerate key"
            >
              <RefreshCw className="w-4 h-4" />
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setRevokeKey(key)}
              className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
              title="Revoke key"
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
      title="API Keys"
      description="Manage API keys for programmatic access"
    >
      <div className="p-4 md:p-6 space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h2 className="text-xl font-semibold text-slate-900 dark:text-slate-100">
              API Keys
            </h2>
            <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
              Create and manage API keys for third-party integrations
            </p>
          </div>

          {canManageApiKeys && (
            <Button onClick={() => setCreateModalOpen(true)}>
              <Plus className="w-4 h-4 mr-2" />
              Create API Key
            </Button>
          )}
        </div>

        {/* Stats */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <StatCard
            title="Total Keys"
            value={stats.total}
            icon={<Key className="w-5 h-5" />}
          />
          <StatCard
            title="Active"
            value={stats.active}
            icon={<CheckCircle className="w-5 h-5" />}
          />
          <StatCard
            title="Revoked"
            value={stats.revoked}
            icon={<AlertCircle className="w-5 h-5" />}
          />
          <StatCard
            title="Used (30 days)"
            value={stats.used_last_30_days}
            icon={<Clock className="w-5 h-5" />}
          />
        </div>

        {/* Filter */}
        <div className="flex items-center gap-2">
          <label className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
            <input
              type="checkbox"
              checked={includeRevoked}
              onChange={(e) => setIncludeRevoked(e.target.checked)}
              className="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
            />
            Show revoked keys
          </label>
        </div>

        {/* Keys Table */}
        <Card>
          {apiKeys.length === 0 && !isLoading ? (
            <NoDataEmptyState
              title="No API keys"
              description="Create an API key to enable programmatic access to your account."
              action={
                <Button onClick={() => setCreateModalOpen(true)}>
                  <Plus className="w-4 h-4 mr-2" />
                  Create API Key
                </Button>
              }
            />
          ) : (
            <Table
              columns={columns}
              data={apiKeys}
              loading={isLoading}
            />
          )}
        </Card>

        {/* API Documentation Link */}
        <Card className="p-6">
          <h3 className="font-semibold text-slate-900 dark:text-slate-100 mb-2">
            Using API Keys
          </h3>
          <p className="text-sm text-slate-600 dark:text-slate-400 mb-4">
            Include your API key in the Authorization header of your requests:
          </p>
          <div className="bg-slate-900 dark:bg-slate-950 rounded-lg p-4 font-mono text-sm text-slate-100 overflow-x-auto">
            <code>Authorization: Bearer pk_xxxxxxxx:sk_xxxxxxxx</code>
          </div>
          <p className="text-xs text-slate-500 dark:text-slate-400 mt-2">
            The API key format is <code className="bg-slate-100 dark:bg-slate-800 px-1 rounded">key_id:secret</code>
          </p>
        </Card>
      </div>

      {/* Create API Key Modal */}
      <Modal
        isOpen={createModalOpen}
        onClose={() => setCreateModalOpen(false)}
        title="Create API Key"
      >
        <div className="space-y-4">
          <Input
            label="Key Name"
            value={createForm.name}
            onChange={(e) => setCreateForm({ ...createForm, name: e.target.value })}
            placeholder="e.g., Production Integration"
            helper="A descriptive name to identify this key"
          />

          <div>
            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Permissions
            </label>
            <div className="space-y-2">
              {scopeOptions.map((option) => (
                <label
                  key={option.value}
                  className="flex items-center gap-3 p-3 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer"
                >
                  <input
                    type="checkbox"
                    checked={createForm.scopes.includes(option.value as ApiKeyScope)}
                    onChange={() => handleScopeToggle(option.value as ApiKeyScope)}
                    className="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                  />
                  <span className="text-sm text-slate-700 dark:text-slate-300">
                    {option.label}
                  </span>
                </label>
              ))}
            </div>
          </div>

          <Input
            label="Expiration Date (Optional)"
            type="date"
            value={createForm.expires_at}
            onChange={(e) => setCreateForm({ ...createForm, expires_at: e.target.value })}
            helper="Leave empty for a key that never expires"
          />

          <div className="flex justify-end gap-3 pt-4">
            <Button variant="outline" onClick={() => setCreateModalOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={() => createMutation.mutate()}
              disabled={!createForm.name || createForm.scopes.length === 0 || createMutation.isPending}
            >
              {createMutation.isPending ? 'Creating...' : 'Create Key'}
            </Button>
          </div>
        </div>
      </Modal>

      {/* Secret Display Modal */}
      <Modal
        isOpen={!!secretModal}
        onClose={() => {
          setSecretModal(null);
          setShowSecret(false);
        }}
        title="API Key Created"
      >
        {secretModal && (
          <div className="space-y-4">
            <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
              <div className="flex items-start gap-3">
                <AlertCircle className="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                <div>
                  <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                    Save this secret key now!
                  </p>
                  <p className="text-sm text-amber-700 dark:text-amber-300 mt-1">
                    This is the only time you'll see this secret. Store it securely.
                  </p>
                </div>
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Full API Key
              </label>
              <div className="flex items-center gap-2">
                <div className="flex-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-3 font-mono text-sm break-all">
                  {showSecret ? secretModal.full_key : '••••••••••••••••••••••••••••••••'}
                </div>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => setShowSecret(!showSecret)}
                  title={showSecret ? 'Hide' : 'Show'}
                >
                  {showSecret ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => copyToClipboard(secretModal.full_key)}
                  title="Copy"
                >
                  <Copy className="w-4 h-4" />
                </Button>
              </div>
            </div>

            <div className="flex justify-end pt-4">
              <Button
                onClick={() => {
                  setSecretModal(null);
                  setShowSecret(false);
                }}
              >
                Done
              </Button>
            </div>
          </div>
        )}
      </Modal>

      {/* Revoke Confirmation */}
      <ConfirmModal
        isOpen={!!revokeKey}
        onClose={() => setRevokeKey(null)}
        onConfirm={() => revokeKey && revokeMutation.mutate(revokeKey.id)}
        title="Revoke API Key"
        message={`Are you sure you want to revoke "${revokeKey?.name}"? This action cannot be undone and any integrations using this key will stop working immediately.`}
        confirmText="Revoke Key"
        variant="danger"
        loading={revokeMutation.isPending}
      />

      {/* Regenerate Confirmation */}
      <ConfirmModal
        isOpen={!!regenerateKey}
        onClose={() => setRegenerateKey(null)}
        onConfirm={() => regenerateKey && regenerateMutation.mutate(regenerateKey.id)}
        title="Regenerate API Key"
        message={`This will revoke the current key and create a new one with the same settings. Any integrations using the current key will need to be updated. Continue?`}
        confirmText="Regenerate"
        variant="danger"
        loading={regenerateMutation.isPending}
      />
    </Layout>
  );
}

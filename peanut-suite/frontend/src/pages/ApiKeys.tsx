import { useState, useEffect } from 'react';
import {
  Key,
  Plus,
  Trash2,
  RefreshCw,
  Copy,
  Check,
  AlertCircle,
  Loader2,
  Eye,
  EyeOff,
  Clock,
  Shield,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Input, Badge, Modal, SampleDataBanner } from '../components/common';
import { apiKeysApi } from '../api/endpoints';
import { useAccountStore } from '../store/useAccountStore';
import type { ApiKey, ApiKeyScope, ApiKeyFormData } from '../types';

const SCOPE_CONFIG: Record<ApiKeyScope, { label: string; description: string; category: string }> = {
  'links:read': { label: 'Read Links', description: 'View short links and click data', category: 'Links' },
  'links:write': { label: 'Write Links', description: 'Create, update, and delete links', category: 'Links' },
  'utms:read': { label: 'Read UTMs', description: 'View UTM codes and analytics', category: 'UTMs' },
  'utms:write': { label: 'Write UTMs', description: 'Create, update, and delete UTMs', category: 'UTMs' },
  'contacts:read': { label: 'Read Contacts', description: 'View contact information', category: 'Contacts' },
  'contacts:write': { label: 'Write Contacts', description: 'Create, update, and delete contacts', category: 'Contacts' },
  'analytics:read': { label: 'Read Analytics', description: 'View analytics and reports', category: 'Analytics' },
};

const SAMPLE_API_KEYS: ApiKey[] = [
  {
    id: 1,
    account_id: 1,
    key_id: 'abc123def456',
    key_preview: 'pnut_abc123def456_****',
    name: 'Production API Key',
    scopes: ['links:read', 'links:write', 'utms:read'],
    created_by: 1,
    created_by_name: 'John Doe',
    last_used_at: new Date(Date.now() - 3600000).toISOString(),
    last_used_ip: '192.168.1.1',
    expires_at: null,
    revoked_at: null,
    revoked_by: null,
    created_at: new Date(Date.now() - 86400000 * 30).toISOString(),
  },
  {
    id: 2,
    account_id: 1,
    key_id: 'xyz789abc012',
    key_preview: 'pnut_xyz789abc012_****',
    name: 'Development Key',
    scopes: ['links:read', 'utms:read', 'contacts:read'],
    created_by: 1,
    created_by_name: 'John Doe',
    last_used_at: new Date(Date.now() - 86400000 * 7).toISOString(),
    last_used_ip: '10.0.0.1',
    expires_at: new Date(Date.now() + 86400000 * 90).toISOString(),
    revoked_at: null,
    revoked_by: null,
    created_at: new Date(Date.now() - 86400000 * 14).toISOString(),
  },
];

export default function ApiKeys() {
  const [keys, setKeys] = useState<ApiKey[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showSampleData, setShowSampleData] = useState(true);
  const [newKeySecret, setNewKeySecret] = useState<string | null>(null);

  const { account, isInitialized, fetchCurrentUser } = useAccountStore();
  const accountId = account?.id ?? null;
  const currentUserRole = account?.role ?? 'owner';

  const safeKeys = Array.isArray(keys) ? keys : [];
  const hasNoRealData = !loading && safeKeys.length === 0;
  const displaySampleData = hasNoRealData && showSampleData;
  const displayKeys = displaySampleData ? SAMPLE_API_KEYS : safeKeys;

  useEffect(() => {
    if (!isInitialized) {
      fetchCurrentUser();
    }
  }, [isInitialized, fetchCurrentUser]);

  useEffect(() => {
    if (isInitialized && accountId) {
      loadKeys();
    } else if (isInitialized && !accountId) {
      setLoading(false);
    }
  }, [isInitialized, accountId]);

  const loadKeys = async () => {
    if (!accountId) {
      setLoading(false);
      return;
    }

    try {
      setLoading(true);
      const data = await apiKeysApi.getAll(accountId);
      setKeys(Array.isArray(data) ? data : []);
      setError(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load API keys');
      setKeys([]);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateKey = async (formData: ApiKeyFormData) => {
    if (!accountId) return;
    const newKey = await apiKeysApi.create(accountId, formData);
    setNewKeySecret(newKey.key);
    await loadKeys();
    setShowCreateModal(false);
  };

  const handleRevokeKey = async (keyId: number) => {
    if (!accountId) return;
    if (!confirm('Are you sure you want to revoke this API key? This action cannot be undone.')) return;
    await apiKeysApi.revoke(accountId, keyId);
    await loadKeys();
  };

  const handleRegenerateKey = async (keyId: number) => {
    if (!accountId) return;
    if (!confirm('Are you sure you want to regenerate this API key? The old key will stop working immediately.')) return;
    const newKey = await apiKeysApi.regenerate(accountId, keyId);
    setNewKeySecret(newKey.key);
    await loadKeys();
  };

  const canManageKeys = currentUserRole === 'owner' || currentUserRole === 'admin';

  if (!isInitialized) {
    return (
      <Layout
        title="API Keys"
        description="Manage API keys for external integrations"
        pageGuideId="api-keys"
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

  if (!accountId) {
    return (
      <Layout
        title="API Keys"
        description="Manage API keys for external integrations"
        pageGuideId="api-keys"
      >
        <Card>
          <div className="py-12 text-center">
            <Key className="w-12 h-12 text-slate-300 mx-auto mb-4" />
            <p className="text-slate-500 mb-2">No account found</p>
            <p className="text-sm text-slate-400">
              API key management requires an account. Please contact your administrator.
            </p>
          </div>
        </Card>
      </Layout>
    );
  }

  return (
    <Layout
      title="API Keys"
      description="Manage API keys for external integrations"
      pageGuideId="api-keys"
    >
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* New Key Secret Display */}
      {newKeySecret && (
        <NewKeySecretBanner
          secret={newKeySecret}
          onDismiss={() => setNewKeySecret(null)}
        />
      )}

      {/* Header with Create Button */}
      {canManageKeys && (
        <div className="flex justify-end mb-6">
          <Button icon={<Plus className="w-4 h-4" />} onClick={() => setShowCreateModal(true)}>
            Create API Key
          </Button>
        </div>
      )}

      {error && !displaySampleData && (
        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3">
          <AlertCircle className="w-5 h-5 text-red-500" />
          <span className="text-red-700">{error}</span>
        </div>
      )}

      {/* API Documentation Link */}
      <Card className="mb-6">
        <div className="flex items-center gap-4 p-4">
          <div className="p-3 bg-primary-100 rounded-lg">
            <Shield className="w-6 h-6 text-primary-600" />
          </div>
          <div className="flex-1">
            <h3 className="font-medium text-slate-900">API Authentication</h3>
            <p className="text-sm text-slate-500">
              Use your API key in the Authorization header: <code className="bg-slate-100 px-1 rounded">Bearer YOUR_API_KEY</code>
            </p>
          </div>
        </div>
      </Card>

      <Card>
        {loading ? (
          <div className="py-12 text-center text-slate-500">Loading API keys...</div>
        ) : displayKeys.length === 0 ? (
          <div className="py-12 text-center">
            <Key className="w-12 h-12 text-slate-300 mx-auto mb-4" />
            <p className="text-slate-500">No API keys yet</p>
            {canManageKeys && (
              <Button
                variant="outline"
                size="sm"
                className="mt-4"
                onClick={() => setShowCreateModal(true)}
              >
                Create your first API key
              </Button>
            )}
          </div>
        ) : (
          <div className="divide-y divide-slate-100">
            {displayKeys.map((key) => (
              <ApiKeyRow
                key={key.id}
                apiKey={key}
                canManage={canManageKeys && !displaySampleData}
                onRevoke={() => handleRevokeKey(key.id)}
                onRegenerate={() => handleRegenerateKey(key.id)}
              />
            ))}
          </div>
        )}
      </Card>

      {/* Create Key Modal */}
      <CreateKeyModal
        isOpen={showCreateModal}
        onClose={() => setShowCreateModal(false)}
        onSubmit={handleCreateKey}
      />
    </Layout>
  );
}

interface ApiKeyRowProps {
  apiKey: ApiKey;
  canManage: boolean;
  onRevoke: () => void;
  onRegenerate: () => void;
}

function ApiKeyRow({ apiKey, canManage, onRevoke, onRegenerate }: ApiKeyRowProps) {
  const [copied, setCopied] = useState(false);

  const copyKeyPreview = () => {
    navigator.clipboard.writeText(apiKey.key_preview);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const formatDate = (dateStr: string | null) => {
    if (!dateStr) return 'Never';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  };

  const formatRelativeTime = (dateStr: string | null) => {
    if (!dateStr) return 'Never';
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    return formatDate(dateStr);
  };

  const isExpired = apiKey.expires_at && new Date(apiKey.expires_at) < new Date();

  return (
    <div className="p-4 hover:bg-slate-50 transition-colors">
      <div className="flex items-start justify-between">
        <div className="flex items-start gap-4">
          <div className="p-2 bg-primary-100 rounded-lg mt-1">
            <Key className="w-5 h-5 text-primary-600" />
          </div>
          <div>
            <div className="flex items-center gap-2">
              <span className="font-medium text-slate-900">{apiKey.name}</span>
              {isExpired && (
                <Badge variant="danger" size="sm">Expired</Badge>
              )}
            </div>
            <div className="flex items-center gap-2 mt-1">
              <code className="text-sm text-slate-500 bg-slate-100 px-2 py-0.5 rounded font-mono">
                {apiKey.key_preview}
              </code>
              <button
                onClick={copyKeyPreview}
                className="p-1 hover:bg-slate-200 rounded transition-colors"
                title="Copy key ID"
              >
                {copied ? (
                  <Check className="w-3.5 h-3.5 text-green-500" />
                ) : (
                  <Copy className="w-3.5 h-3.5 text-slate-400" />
                )}
              </button>
            </div>
            <div className="flex flex-wrap gap-1 mt-2">
              {apiKey.scopes.map((scope) => (
                <Badge key={scope} variant="default" size="sm">
                  {SCOPE_CONFIG[scope]?.label || scope}
                </Badge>
              ))}
            </div>
          </div>
        </div>

        {canManage && (
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              icon={<RefreshCw className="w-4 h-4" />}
              onClick={onRegenerate}
            >
              Regenerate
            </Button>
            <Button
              variant="outline"
              size="sm"
              icon={<Trash2 className="w-4 h-4" />}
              onClick={onRevoke}
              className="text-red-600 hover:bg-red-50 border-red-200"
            >
              Revoke
            </Button>
          </div>
        )}
      </div>

      <div className="flex items-center gap-6 mt-3 text-xs text-slate-500 ml-12">
        <span className="flex items-center gap-1">
          <Clock className="w-3.5 h-3.5" />
          Created {formatDate(apiKey.created_at)}
        </span>
        {apiKey.last_used_at && (
          <span>Last used {formatRelativeTime(apiKey.last_used_at)}</span>
        )}
        {apiKey.expires_at && (
          <span>
            {isExpired ? 'Expired' : 'Expires'} {formatDate(apiKey.expires_at)}
          </span>
        )}
        {apiKey.created_by_name && (
          <span>Created by {apiKey.created_by_name}</span>
        )}
      </div>
    </div>
  );
}

interface NewKeySecretBannerProps {
  secret: string;
  onDismiss: () => void;
}

function NewKeySecretBanner({ secret, onDismiss }: NewKeySecretBannerProps) {
  const [copied, setCopied] = useState(false);
  const [visible, setVisible] = useState(false);

  const copySecret = () => {
    navigator.clipboard.writeText(secret);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
      <div className="flex items-start gap-3">
        <Check className="w-5 h-5 text-green-500 mt-0.5" />
        <div className="flex-1">
          <h3 className="font-medium text-green-800">API Key Created Successfully</h3>
          <p className="text-sm text-green-700 mt-1">
            Copy your API key now. For security reasons, it won't be displayed again.
          </p>
          <div className="flex items-center gap-2 mt-3">
            <code className="flex-1 bg-white px-3 py-2 rounded border border-green-200 font-mono text-sm text-slate-700 overflow-x-auto">
              {visible ? secret : '•'.repeat(Math.min(secret.length, 60))}
            </code>
            <button
              onClick={() => setVisible(!visible)}
              className="p-2 hover:bg-green-100 rounded transition-colors"
              title={visible ? 'Hide key' : 'Show key'}
            >
              {visible ? (
                <EyeOff className="w-4 h-4 text-green-600" />
              ) : (
                <Eye className="w-4 h-4 text-green-600" />
              )}
            </button>
            <Button
              variant="outline"
              size="sm"
              onClick={copySecret}
              icon={copied ? <Check className="w-4 h-4 text-green-500" /> : <Copy className="w-4 h-4" />}
            >
              {copied ? 'Copied!' : 'Copy'}
            </Button>
          </div>
        </div>
        <button
          onClick={onDismiss}
          className="p-1 hover:bg-green-100 rounded transition-colors"
        >
          <AlertCircle className="w-4 h-4 text-green-600 rotate-45" />
        </button>
      </div>
    </div>
  );
}

interface CreateKeyModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (data: ApiKeyFormData) => Promise<void>;
}

function CreateKeyModal({ isOpen, onClose, onSubmit }: CreateKeyModalProps) {
  const [name, setName] = useState('');
  const [scopes, setScopes] = useState<ApiKeyScope[]>([]);
  const [expiresAt, setExpiresAt] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isOpen) {
      setName('');
      setScopes([]);
      setExpiresAt('');
      setError(null);
    }
  }, [isOpen]);

  const handleSubmit = async () => {
    if (!name.trim()) {
      setError('Name is required');
      return;
    }
    if (scopes.length === 0) {
      setError('Select at least one scope');
      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      await onSubmit({
        name: name.trim(),
        scopes,
        expires_at: expiresAt || null,
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create API key');
    } finally {
      setSubmitting(false);
    }
  };

  const toggleScope = (scope: ApiKeyScope) => {
    setScopes((prev) =>
      prev.includes(scope)
        ? prev.filter((s) => s !== scope)
        : [...prev, scope]
    );
  };

  const scopesByCategory = Object.entries(SCOPE_CONFIG).reduce(
    (acc, [scope, config]) => {
      if (!acc[config.category]) acc[config.category] = [];
      acc[config.category].push({ scope: scope as ApiKeyScope, ...config });
      return acc;
    },
    {} as Record<string, Array<{ scope: ApiKeyScope; label: string; description: string }>>
  );

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="Create API Key" size="md">
      <div className="space-y-6">
        {error && (
          <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            {error}
          </div>
        )}

        <Input
          label="Key Name"
          value={name}
          onChange={(e) => setName(e.target.value)}
          placeholder="e.g., Production API Key"
          helper="A descriptive name to identify this key"
        />

        <div>
          <label className="block text-sm font-medium text-slate-700 mb-3">
            Permissions (Scopes)
          </label>
          <div className="space-y-4">
            {Object.entries(scopesByCategory).map(([category, categoryScopes]) => (
              <div key={category}>
                <p className="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">
                  {category}
                </p>
                <div className="space-y-2">
                  {categoryScopes.map(({ scope, label, description }) => (
                    <label
                      key={scope}
                      className="flex items-center justify-between p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer"
                    >
                      <div className="flex items-center gap-3">
                        <input
                          type="checkbox"
                          checked={scopes.includes(scope)}
                          onChange={() => toggleScope(scope)}
                          className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                        />
                        <div>
                          <span className="text-sm font-medium text-slate-700">{label}</span>
                          <p className="text-xs text-slate-500">{description}</p>
                        </div>
                      </div>
                    </label>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>

        <Input
          label="Expiration Date (Optional)"
          type="date"
          value={expiresAt}
          onChange={(e) => setExpiresAt(e.target.value)}
          helper="Leave empty for a key that never expires"
          min={new Date().toISOString().split('T')[0]}
        />

        <div className="flex justify-end gap-3 pt-4 border-t border-slate-200">
          <Button variant="outline" onClick={onClose} disabled={submitting}>
            Cancel
          </Button>
          <Button onClick={handleSubmit} disabled={submitting}>
            {submitting ? 'Creating...' : 'Create API Key'}
          </Button>
        </div>
      </div>
    </Modal>
  );
}

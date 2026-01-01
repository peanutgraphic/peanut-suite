import { useState, useEffect } from 'react';
import {
  ScrollText,
  Download,
  RefreshCw,
  User,
  Clock,
  Activity,
  AlertCircle,
  Loader2,
  ChevronLeft,
  ChevronRight,
  UserPlus,
  Edit2,
  Trash2,
  Settings,
  LogIn,
  LogOut,
  Link2,
  Tag,
  Users,
  MessageSquare,
  KeyRound,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Badge, SampleDataBanner } from '../components/common';
import { accountsApi } from '../api/endpoints';
import { useAccountStore } from '../store/useAccountStore';
import type { AuditLogEntry, AuditLogAction, AuditLogResource } from '../types';

// Sample data for empty state
const sampleAuditEntries: AuditLogEntry[] = [
  {
    id: 1,
    account_id: 1,
    user_id: 1,
    user_name: 'John Doe',
    user_email: 'john@example.com',
    api_key_id: null,
    action: 'create',
    resource_type: 'member',
    resource_id: 5,
    details: { email: 'newuser@example.com', role: 'member' },
    ip_address: '192.168.1.100',
    user_agent: 'Mozilla/5.0',
    created_at: new Date(Date.now() - 1000 * 60 * 30).toISOString(),
  },
  {
    id: 2,
    account_id: 1,
    user_id: 1,
    user_name: 'John Doe',
    user_email: 'john@example.com',
    api_key_id: null,
    action: 'update',
    resource_type: 'member',
    resource_id: 3,
    details: { action: 'password_reset_sent', email: 'member@example.com' },
    ip_address: '192.168.1.100',
    user_agent: 'Mozilla/5.0',
    created_at: new Date(Date.now() - 1000 * 60 * 60).toISOString(),
  },
  {
    id: 3,
    account_id: 1,
    user_id: 2,
    user_name: 'Jane Smith',
    user_email: 'jane@example.com',
    api_key_id: null,
    action: 'create',
    resource_type: 'link',
    resource_id: 42,
    details: { slug: 'summer-sale', url: 'https://example.com/sale' },
    ip_address: '10.0.0.50',
    user_agent: 'Mozilla/5.0',
    created_at: new Date(Date.now() - 1000 * 60 * 60 * 2).toISOString(),
  },
  {
    id: 4,
    account_id: 1,
    user_id: 1,
    user_name: 'John Doe',
    user_email: 'john@example.com',
    api_key_id: null,
    action: 'update',
    resource_type: 'settings',
    resource_id: null,
    details: { setting: 'notification_email', new_value: 'team@example.com' },
    ip_address: '192.168.1.100',
    user_agent: 'Mozilla/5.0',
    created_at: new Date(Date.now() - 1000 * 60 * 60 * 24).toISOString(),
  },
  {
    id: 5,
    account_id: 1,
    user_id: 3,
    user_name: 'Bob Wilson',
    user_email: 'bob@example.com',
    api_key_id: null,
    action: 'delete',
    resource_type: 'utm',
    resource_id: 15,
    details: { campaign: 'old-campaign-2023' },
    ip_address: '172.16.0.25',
    user_agent: 'Mozilla/5.0',
    created_at: new Date(Date.now() - 1000 * 60 * 60 * 48).toISOString(),
  },
];

const ACTION_CONFIG: Record<AuditLogAction, { label: string; color: string; icon: typeof Activity }> = {
  create: { label: 'Created', color: 'green', icon: UserPlus },
  update: { label: 'Updated', color: 'blue', icon: Edit2 },
  delete: { label: 'Deleted', color: 'red', icon: Trash2 },
  login: { label: 'Login', color: 'slate', icon: LogIn },
  logout: { label: 'Logout', color: 'slate', icon: LogOut },
  invite: { label: 'Invited', color: 'green', icon: UserPlus },
  revoke: { label: 'Revoked', color: 'amber', icon: KeyRound },
  export: { label: 'Exported', color: 'blue', icon: Activity },
  access_denied: { label: 'Access Denied', color: 'red', icon: AlertCircle },
  rate_limited: { label: 'Rate Limited', color: 'amber', icon: AlertCircle },
};

const RESOURCE_CONFIG: Record<AuditLogResource, { label: string; icon: typeof Users }> = {
  account: { label: 'Account', icon: Settings },
  member: { label: 'Team Member', icon: Users },
  api_key: { label: 'API Key', icon: KeyRound },
  utm: { label: 'UTM', icon: Tag },
  link: { label: 'Link', icon: Link2 },
  contact: { label: 'Contact', icon: User },
  popup: { label: 'Popup', icon: MessageSquare },
  settings: { label: 'Settings', icon: Settings },
  audit_log: { label: 'Audit Log', icon: ScrollText },
};

function formatRelativeTime(dateString: string): string {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / (1000 * 60));
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  return date.toLocaleDateString();
}

function getActionDescription(entry: AuditLogEntry): string {
  const resourceLabel = RESOURCE_CONFIG[entry.resource_type]?.label || entry.resource_type;
  const actionLabel = ACTION_CONFIG[entry.action]?.label.toLowerCase() || entry.action;
  const details = entry.details as Record<string, unknown> | null;

  // Special handling for specific actions
  if (details?.action === 'password_reset_sent') {
    return `Sent password reset email to ${details.email || 'team member'}`;
  }

  if (entry.action === 'create' && entry.resource_type === 'member') {
    return `Added ${details?.email || 'new member'} to the team`;
  }

  if (entry.action === 'invite' && entry.resource_type === 'member') {
    return `Invited ${details?.email || 'new member'} to the team`;
  }

  if (entry.action === 'delete' && entry.resource_type === 'member') {
    return `Removed ${details?.email || 'member'} from the team`;
  }

  if (entry.action === 'update' && entry.resource_type === 'member' && details?.role) {
    return `Changed role to ${details.role} for a team member`;
  }

  if (entry.action === 'update' && entry.resource_type === 'settings') {
    return `Updated ${details?.setting || 'a setting'}`;
  }

  if (entry.action === 'revoke' && entry.resource_type === 'api_key') {
    return `Revoked API key ${details?.name || ''}`;
  }

  if (entry.action === 'create' && entry.resource_type === 'api_key') {
    return `Created API key ${details?.name || ''}`;
  }

  if (entry.action === 'export' && entry.resource_type === 'audit_log') {
    return `Exported ${details?.exported_count || ''} audit log entries`;
  }

  return `${actionLabel.charAt(0).toUpperCase() + actionLabel.slice(1)} ${resourceLabel.toLowerCase()}`;
}

export default function AuditLog() {
  const [entries, setEntries] = useState<AuditLogEntry[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [total, setTotal] = useState(0);
  const [showSampleData, setShowSampleData] = useState(true);
  const [actionFilter, setActionFilter] = useState<string>('');
  const [resourceFilter, setResourceFilter] = useState<string>('');

  // Get account context from store - with safety checks
  const store = useAccountStore();
  const account = store?.account ?? null;
  const isInitialized = store?.isInitialized ?? false;
  const fetchCurrentUser = store?.fetchCurrentUser ?? (() => Promise.resolve());
  const accountId = account?.id ?? null;

  // Determine if we should show sample data
  const safeEntries = Array.isArray(entries) ? entries : [];
  const hasNoRealData = !loading && safeEntries.length === 0;
  const displaySampleData = hasNoRealData && showSampleData;
  const displayEntries = displaySampleData ? sampleAuditEntries : safeEntries;

  // Fetch account data if not initialized
  useEffect(() => {
    if (!isInitialized) {
      fetchCurrentUser();
    }
  }, [isInitialized, fetchCurrentUser]);

  // Load entries when account is available
  useEffect(() => {
    if (isInitialized && accountId) {
      loadEntries();
    } else if (isInitialized && !accountId) {
      setLoading(false);
    }
  }, [isInitialized, accountId, page, actionFilter, resourceFilter]);

  const loadEntries = async () => {
    if (!accountId) {
      setLoading(false);
      return;
    }

    try {
      setLoading(true);
      const data = await accountsApi.getAuditLog(accountId, {
        page,
        per_page: 20,
        action: actionFilter || undefined,
        resource_type: resourceFilter || undefined,
      });
      // API returns normalized format with items, total, page, per_page, total_pages
      const items = data?.items || [];
      setEntries(Array.isArray(items) ? items : []);
      setTotalPages(data?.total_pages || 1);
      setTotal(data?.total || 0);
      setError(null);
    } catch {
      // API endpoint may not exist yet - show empty state instead of error
      setEntries([]);
      setTotalPages(1);
      setTotal(0);
      setError(null); // Don't show error, just show empty/sample data
    } finally {
      setLoading(false);
    }
  };

  const handleExport = async (format: 'csv' | 'json') => {
    if (!accountId) return;

    try {
      const items = await accountsApi.exportAuditLog(accountId, format);
      let content: string;
      let mimeType: string;

      if (format === 'json') {
        content = JSON.stringify(items, null, 2);
        mimeType = 'application/json';
      } else {
        // Generate CSV
        const headers = ['ID', 'User', 'Email', 'Action', 'Resource', 'Details', 'IP Address', 'Date'];
        const rows = items.map(item => [
          item.id,
          item.user_name || '',
          item.user_email || '',
          item.action,
          item.resource_type,
          item.details ? JSON.stringify(item.details) : '',
          item.ip_address || '',
          item.created_at,
        ]);
        content = [headers.join(','), ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))].join('\n');
        mimeType = 'text/csv';
      }

      const blob = new Blob([content], { type: mimeType });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `audit-log-${new Date().toISOString().split('T')[0]}.${format}`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch {
      setError('Failed to export audit log. Please try again.');
    }
  };

  // Show loading state while initializing account
  if (!isInitialized) {
    return (
      <Layout
        title="Audit Log"
        description="Track all activity and changes in your account"
        pageGuideId="audit-log"
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
        title="Audit Log"
        description="Track all activity and changes in your account"
        pageGuideId="audit-log"
      >
        <Card>
          <div className="py-12 text-center">
            <ScrollText className="w-12 h-12 text-slate-300 mx-auto mb-4" />
            <p className="text-slate-500 mb-2">No account found</p>
            <p className="text-sm text-slate-400">
              Audit log requires an account. Please contact your administrator.
            </p>
          </div>
        </Card>
      </Layout>
    );
  }

  return (
    <Layout
      title="Audit Log"
      description="Track all activity and changes in your account"
      pageGuideId="audit-log"
    >
      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Header with Filters and Export */}
      <div className="flex flex-col sm:flex-row gap-4 justify-between mb-6">
        <div className="flex gap-2">
          <select
            value={actionFilter}
            onChange={(e) => {
              setActionFilter(e.target.value);
              setPage(1);
            }}
            className="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="">All Actions</option>
            <option value="create">Created</option>
            <option value="update">Updated</option>
            <option value="delete">Deleted</option>
            <option value="login">Login</option>
            <option value="logout">Logout</option>
          </select>
          <select
            value={resourceFilter}
            onChange={(e) => {
              setResourceFilter(e.target.value);
              setPage(1);
            }}
            className="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="">All Resources</option>
            <option value="member">Team Members</option>
            <option value="utm">UTMs</option>
            <option value="link">Links</option>
            <option value="contact">Contacts</option>
            <option value="popup">Popups</option>
            <option value="setting">Settings</option>
          </select>
          <Button
            variant="outline"
            size="sm"
            onClick={loadEntries}
            icon={<RefreshCw className="w-4 h-4" />}
          >
            Refresh
          </Button>
        </div>
        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => handleExport('csv')}
            icon={<Download className="w-4 h-4" />}
            disabled={displaySampleData}
          >
            Export CSV
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => handleExport('json')}
            icon={<Download className="w-4 h-4" />}
            disabled={displaySampleData}
          >
            Export JSON
          </Button>
        </div>
      </div>

      {error && !displaySampleData && (
        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3">
          <AlertCircle className="w-5 h-5 text-red-500" />
          <span className="text-red-700">{error}</span>
        </div>
      )}

      <Card>
        {loading ? (
          <div className="py-12 text-center">
            <Loader2 className="w-8 h-8 text-primary-500 mx-auto mb-4 animate-spin" />
            <p className="text-slate-500">Loading audit log...</p>
          </div>
        ) : displayEntries.length === 0 ? (
          <div className="py-12 text-center">
            <ScrollText className="w-12 h-12 text-slate-300 mx-auto mb-4" />
            <p className="text-slate-500">No activity recorded yet</p>
            <p className="text-sm text-slate-400 mt-1">
              Actions like adding team members, creating links, and changing settings will appear here.
            </p>
          </div>
        ) : (
          <div className="divide-y divide-slate-100">
            {displayEntries.map((entry) => (
              <AuditLogRow key={entry.id} entry={entry} />
            ))}
          </div>
        )}

        {/* Pagination */}
        {!loading && displayEntries.length > 0 && !displaySampleData && (
          <div className="flex items-center justify-between p-4 border-t border-slate-200">
            <p className="text-sm text-slate-500">
              Showing {((page - 1) * 20) + 1} - {Math.min(page * 20, total)} of {total} entries
            </p>
            <div className="flex gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => setPage(p => Math.max(1, p - 1))}
                disabled={page <= 1}
                icon={<ChevronLeft className="w-4 h-4" />}
              >
                Previous
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                disabled={page >= totalPages}
              >
                Next
                <ChevronRight className="w-4 h-4 ml-1" />
              </Button>
            </div>
          </div>
        )}
      </Card>
    </Layout>
  );
}

interface AuditLogRowProps {
  entry: AuditLogEntry;
}

function AuditLogRow({ entry }: AuditLogRowProps) {
  const actionConfig = ACTION_CONFIG[entry.action] || { label: entry.action, color: 'slate', icon: Activity };
  const resourceConfig = RESOURCE_CONFIG[entry.resource_type] || { label: entry.resource_type, icon: Activity };
  const ActionIcon = actionConfig.icon;
  const ResourceIcon = resourceConfig.icon;

  const badgeVariant = entry.action === 'create' ? 'success'
    : entry.action === 'delete' ? 'danger'
    : entry.action === 'update' ? 'primary'
    : 'default';

  return (
    <div className="flex items-start gap-4 p-4 hover:bg-slate-50 transition-colors">
      {/* Icon */}
      <div className={`p-2 rounded-lg flex-shrink-0 ${
        entry.action === 'create' ? 'bg-green-100' :
        entry.action === 'delete' ? 'bg-red-100' :
        entry.action === 'update' ? 'bg-blue-100' :
        'bg-slate-100'
      }`}>
        <ActionIcon className={`w-4 h-4 ${
          entry.action === 'create' ? 'text-green-600' :
          entry.action === 'delete' ? 'text-red-600' :
          entry.action === 'update' ? 'text-blue-600' :
          'text-slate-600'
        }`} />
      </div>

      {/* Content */}
      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2 flex-wrap">
          <span className="font-medium text-slate-900">{entry.user_name}</span>
          <Badge variant={badgeVariant} size="sm">
            {actionConfig.label}
          </Badge>
          <Badge variant="default" size="sm">
            <ResourceIcon className="w-3 h-3 mr-1" />
            {resourceConfig.label}
          </Badge>
        </div>
        <p className="text-sm text-slate-600 mt-1">
          {getActionDescription(entry)}
        </p>
        <div className="flex items-center gap-4 mt-2 text-xs text-slate-400">
          <span className="flex items-center gap-1">
            <Clock className="w-3 h-3" />
            {formatRelativeTime(entry.created_at)}
          </span>
          {entry.ip_address && (
            <span>{entry.ip_address}</span>
          )}
        </div>
      </div>

      {/* Details indicator */}
      {entry.details && typeof entry.details === 'object' && Object.keys(entry.details).length > 0 && (
        <div className="text-xs text-slate-400">
          {(entry.details as Record<string, unknown>).action === 'password_reset_sent' && (
            <KeyRound className="w-4 h-4 text-amber-500" />
          )}
        </div>
      )}
    </div>
  );
}

import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { createColumnHelper } from '@tanstack/react-table';
import {
  Download,
  Filter,
  Clock,
  User,
  Key,
  Plus,
  Pencil,
  Trash2,
  Eye,
  LogIn,
  LogOut,
  FileDown,
  FileUp,
  Zap,
  X,
} from 'lucide-react';
import { Layout } from '../components/layout';
import {
  Card,
  Button,
  Input,
  Table,
  Pagination,
  Badge,
  Select,
  NoDataEmptyState,
} from '../components/common';
import { accountsApi } from '../api/endpoints';
import type { AuditLogEntry, AuditAction, AuditResourceType } from '../types';
import { useCurrentAccount, toast } from '../store';

const columnHelper = createColumnHelper<AuditLogEntry>();

const actionIcons: Record<AuditAction, React.ReactNode> = {
  create: <Plus className="w-4 h-4 text-green-500" />,
  update: <Pencil className="w-4 h-4 text-blue-500" />,
  delete: <Trash2 className="w-4 h-4 text-red-500" />,
  view: <Eye className="w-4 h-4 text-slate-400" />,
  login: <LogIn className="w-4 h-4 text-green-500" />,
  logout: <LogOut className="w-4 h-4 text-amber-500" />,
  export: <FileDown className="w-4 h-4 text-purple-500" />,
  import: <FileUp className="w-4 h-4 text-indigo-500" />,
  api_call: <Zap className="w-4 h-4 text-amber-500" />,
};

const actionLabels: Record<AuditAction, string> = {
  create: 'Created',
  update: 'Updated',
  delete: 'Deleted',
  view: 'Viewed',
  login: 'Logged in',
  logout: 'Logged out',
  export: 'Exported',
  import: 'Imported',
  api_call: 'API Call',
};

const actionBadgeVariants: Record<AuditAction, 'success' | 'info' | 'danger' | 'warning' | 'default'> = {
  create: 'success',
  update: 'info',
  delete: 'danger',
  view: 'default',
  login: 'success',
  logout: 'warning',
  export: 'info',
  import: 'info',
  api_call: 'warning',
};

const resourceLabels: Record<AuditResourceType, string> = {
  account: 'Account',
  member: 'Team Member',
  api_key: 'API Key',
  utm: 'UTM',
  link: 'Link',
  contact: 'Contact',
  tag: 'Tag',
  popup: 'Popup',
  webhook: 'Webhook',
  settings: 'Settings',
};

const actionOptions = [
  { value: '', label: 'All Actions' },
  { value: 'create', label: 'Created' },
  { value: 'update', label: 'Updated' },
  { value: 'delete', label: 'Deleted' },
  { value: 'view', label: 'Viewed' },
  { value: 'login', label: 'Login' },
  { value: 'logout', label: 'Logout' },
  { value: 'export', label: 'Export' },
  { value: 'import', label: 'Import' },
  { value: 'api_call', label: 'API Call' },
];

const resourceOptions = [
  { value: '', label: 'All Resources' },
  { value: 'account', label: 'Account' },
  { value: 'member', label: 'Team Member' },
  { value: 'api_key', label: 'API Key' },
  { value: 'utm', label: 'UTM' },
  { value: 'link', label: 'Link' },
  { value: 'contact', label: 'Contact' },
  { value: 'tag', label: 'Tag' },
  { value: 'popup', label: 'Popup' },
  { value: 'webhook', label: 'Webhook' },
  { value: 'settings', label: 'Settings' },
];

export default function AuditLog() {
  const currentAccount = useCurrentAccount();
  const accountId = currentAccount?.id || 0;

  const [page, setPage] = useState(1);
  const [filters, setFilters] = useState({
    action: '',
    resource_type: '',
    date_from: '',
    date_to: '',
  });
  const [showFilters, setShowFilters] = useState(false);
  const [expandedRow, setExpandedRow] = useState<number | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['audit-log', accountId, page, filters],
    queryFn: () =>
      accountsApi.getAuditLog(accountId, {
        page,
        per_page: 25,
        action: (filters.action || undefined) as AuditAction | undefined,
        resource_type: (filters.resource_type || undefined) as AuditResourceType | undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
      }),
    enabled: !!accountId,
  });

  const handleExport = async (format: 'csv' | 'json') => {
    try {
      const result = await accountsApi.exportAuditLog(
        accountId,
        format,
        filters.date_from,
        filters.date_to
      );

      // Create download link
      const blob = new Blob([result.content], { type: result.mime_type });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = result.filename;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);

      toast.success(`Audit log exported as ${format.toUpperCase()}`);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Failed to export');
    }
  };

  const clearFilters = () => {
    setFilters({
      action: '',
      resource_type: '',
      date_from: '',
      date_to: '',
    });
  };

  const hasFilters = Object.values(filters).some((v) => v !== '');

  const entries = data?.data || [];
  const total = data?.total || 0;
  const totalPages = data?.total_pages || 1;

  const columns = [
    columnHelper.accessor('created_at', {
      header: 'Time',
      cell: (info) => {
        const date = new Date(info.getValue());
        return (
          <div className="flex items-center gap-2">
            <Clock className="w-4 h-4 text-slate-400" />
            <div>
              <p className="text-sm text-slate-900 dark:text-slate-100">
                {date.toLocaleDateString()}
              </p>
              <p className="text-xs text-slate-500 dark:text-slate-400">
                {date.toLocaleTimeString()}
              </p>
            </div>
          </div>
        );
      },
    }),
    columnHelper.accessor('action', {
      header: 'Action',
      cell: (info) => (
        <div className="flex items-center gap-2">
          {actionIcons[info.getValue()]}
          <Badge variant={actionBadgeVariants[info.getValue()]}>
            {actionLabels[info.getValue()]}
          </Badge>
        </div>
      ),
    }),
    columnHelper.accessor('resource_type', {
      header: 'Resource',
      cell: (info) => (
        <div>
          <p className="text-sm text-slate-900 dark:text-slate-100">
            {resourceLabels[info.getValue()]}
          </p>
          {info.row.original.resource_id && (
            <p className="text-xs text-slate-500 dark:text-slate-400">
              ID: {info.row.original.resource_id}
            </p>
          )}
        </div>
      ),
    }),
    columnHelper.display({
      id: 'actor',
      header: 'Actor',
      cell: (info) => {
        const entry = info.row.original;
        const isApiKey = !!entry.api_key_id;

        return (
          <div className="flex items-center gap-2">
            {isApiKey ? (
              <>
                <Key className="w-4 h-4 text-amber-500" />
                <div>
                  <p className="text-sm text-slate-900 dark:text-slate-100">
                    {entry.api_key_name || 'API Key'}
                  </p>
                  <p className="text-xs text-slate-500 dark:text-slate-400">
                    via API
                  </p>
                </div>
              </>
            ) : (
              <>
                <User className="w-4 h-4 text-slate-400" />
                <div>
                  <p className="text-sm text-slate-900 dark:text-slate-100">
                    {entry.user_email || 'System'}
                  </p>
                  {entry.ip_address && (
                    <p className="text-xs text-slate-500 dark:text-slate-400">
                      {entry.ip_address}
                    </p>
                  )}
                </div>
              </>
            )}
          </div>
        );
      },
    }),
    columnHelper.accessor('details', {
      header: 'Details',
      cell: (info) => {
        const details = info.getValue();
        const entryId = info.row.original.id;
        const isExpanded = expandedRow === entryId;

        if (!details || Object.keys(details).length === 0) {
          return <span className="text-slate-400">-</span>;
        }

        return (
          <div>
            <button
              onClick={() => setExpandedRow(isExpanded ? null : entryId)}
              className="text-sm text-primary-600 dark:text-primary-400 hover:underline"
            >
              {isExpanded ? 'Hide details' : 'View details'}
            </button>
            {isExpanded && (
              <pre className="mt-2 p-2 bg-slate-100 dark:bg-slate-800 rounded text-xs overflow-x-auto max-w-md">
                {JSON.stringify(details, null, 2)}
              </pre>
            )}
          </div>
        );
      },
    }),
  ];

  return (
    <Layout
      title="Audit Log"
      description="Track all activity in your account"
    >
      <div className="p-4 md:p-6 space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h2 className="text-xl font-semibold text-slate-900 dark:text-slate-100">
              Audit Log
            </h2>
            <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
              {total.toLocaleString()} events recorded
            </p>
          </div>

          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              onClick={() => setShowFilters(!showFilters)}
              className={showFilters ? 'bg-primary-50 dark:bg-primary-900/20' : ''}
            >
              <Filter className="w-4 h-4 mr-2" />
              Filter
              {hasFilters && (
                <span className="ml-2 w-5 h-5 rounded-full bg-primary-600 text-white text-xs flex items-center justify-center">
                  !
                </span>
              )}
            </Button>

            <div className="relative group">
              <Button variant="outline">
                <Download className="w-4 h-4 mr-2" />
                Export
              </Button>
              <div className="absolute right-0 top-full mt-1 hidden group-hover:block z-10">
                <Card className="p-1 shadow-lg min-w-[120px]">
                  <button
                    onClick={() => handleExport('csv')}
                    className="w-full px-3 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800 rounded"
                  >
                    Export as CSV
                  </button>
                  <button
                    onClick={() => handleExport('json')}
                    className="w-full px-3 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800 rounded"
                  >
                    Export as JSON
                  </button>
                </Card>
              </div>
            </div>
          </div>
        </div>

        {/* Filters */}
        {showFilters && (
          <Card className="p-4">
            <div className="flex flex-wrap gap-4">
              <div className="flex-1 min-w-[200px]">
                <Select
                  label="Action"
                  value={filters.action}
                  onChange={(e) => setFilters({ ...filters, action: e.target.value })}
                  options={actionOptions}
                />
              </div>

              <div className="flex-1 min-w-[200px]">
                <Select
                  label="Resource"
                  value={filters.resource_type}
                  onChange={(e) => setFilters({ ...filters, resource_type: e.target.value })}
                  options={resourceOptions}
                />
              </div>

              <div className="flex-1 min-w-[150px]">
                <Input
                  label="From Date"
                  type="date"
                  value={filters.date_from}
                  onChange={(e) => setFilters({ ...filters, date_from: e.target.value })}
                />
              </div>

              <div className="flex-1 min-w-[150px]">
                <Input
                  label="To Date"
                  type="date"
                  value={filters.date_to}
                  onChange={(e) => setFilters({ ...filters, date_to: e.target.value })}
                />
              </div>

              {hasFilters && (
                <div className="flex items-end">
                  <Button variant="ghost" onClick={clearFilters}>
                    <X className="w-4 h-4 mr-1" />
                    Clear
                  </Button>
                </div>
              )}
            </div>
          </Card>
        )}

        {/* Log Table */}
        <Card>
          {entries.length === 0 && !isLoading ? (
            <NoDataEmptyState
              title="No audit entries"
              description={
                hasFilters
                  ? 'No entries match your filters. Try adjusting the criteria.'
                  : 'Activity will be logged here as you use the platform.'
              }
            />
          ) : (
            <>
              <Table
                columns={columns}
                data={entries}
                loading={isLoading}
              />
              <div className="p-4 border-t border-slate-200 dark:border-slate-700">
                <Pagination
                  page={page}
                  totalPages={totalPages}
                  total={total}
                  perPage={25}
                  onPageChange={setPage}
                />
              </div>
            </>
          )}
        </Card>

        {/* Info Card */}
        <Card className="p-6">
          <h3 className="font-semibold text-slate-900 dark:text-slate-100 mb-2">
            About the Audit Log
          </h3>
          <p className="text-sm text-slate-600 dark:text-slate-400">
            The audit log records all significant actions in your account, including data changes,
            team member actions, and API usage. This helps you maintain security, comply with
            regulations, and troubleshoot issues. Audit logs are retained for 90 days by default.
          </p>
        </Card>
      </div>
    </Layout>
  );
}

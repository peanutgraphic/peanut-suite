import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { createColumnHelper } from '@tanstack/react-table';
import {
  Plus,
  Trash2,
  RefreshCw,
  ExternalLink,
  Search,
  Activity,
  Server,
  AlertTriangle,
  CheckCircle,
  XCircle,
  Clock,
} from 'lucide-react';
import { Layout } from '../components/layout';
import {
  Card,
  Button,
  Input,
  Table,
  Pagination,
  Badge,
  Modal,
  ConfirmModal,
  createCheckboxColumn,
  InfoTooltip,
  SampleDataBanner,
} from '../components/common';
import { monitorApi } from '../api/endpoints';
import type { MonitorSite } from '../types';
import { helpContent, pageDescriptions, sampleMonitorSites, sampleStats } from '../constants';

const columnHelper = createColumnHelper<MonitorSite>();

function getHealthBadge(score: number) {
  if (score >= 80) return { variant: 'success' as const, label: 'Healthy', icon: CheckCircle };
  if (score >= 50) return { variant: 'warning' as const, label: 'Warning', icon: AlertTriangle };
  return { variant: 'danger' as const, label: 'Critical', icon: XCircle };
}

function getUptimeBadge(uptime: number) {
  if (uptime >= 99.9) return { variant: 'success' as const, label: `${uptime.toFixed(2)}%` };
  if (uptime >= 99) return { variant: 'warning' as const, label: `${uptime.toFixed(2)}%` };
  return { variant: 'danger' as const, label: `${uptime.toFixed(2)}%` };
}

export default function Monitor() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [selectedRows, setSelectedRows] = useState<Record<string, boolean>>({});
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const [addModalOpen, setAddModalOpen] = useState(false);
  const [newSite, setNewSite] = useState({ url: '', name: '' });
  const [showSampleData, setShowSampleData] = useState(true);

  const { data, isLoading } = useQuery({
    queryKey: ['monitor-sites', page, search],
    queryFn: () =>
      monitorApi.getSites({
        page,
        per_page: 20,
        search: search || undefined,
      }),
  });

  const addMutation = useMutation({
    mutationFn: monitorApi.addSite,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['monitor-sites'] });
      setAddModalOpen(false);
      setNewSite({ url: '', name: '' });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: monitorApi.removeSite,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['monitor-sites'] });
      setDeleteId(null);
    },
  });

  const refreshMutation = useMutation({
    mutationFn: monitorApi.refreshSite,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['monitor-sites'] });
    },
  });

  // Determine if we should show sample data
  const hasNoRealData = !isLoading && (!data?.data || data.data.length === 0);
  const displaySampleData = hasNoRealData && showSampleData;
  const sites = displaySampleData ? sampleMonitorSites : (data?.data || []);

  // Calculate summary stats
  const healthyCount = sites.filter((s) => (s.health_score || 0) >= 80).length;
  const warningCount = sites.filter((s) => (s.health_score || 0) >= 50 && (s.health_score || 0) < 80).length;
  const criticalCount = sites.filter((s) => (s.health_score || 0) < 50).length;
  const totalUpdates = sites.reduce((acc, s) => acc + (s.updates_available || 0), 0);

  const columns = [
    createCheckboxColumn<MonitorSite>(),
    columnHelper.accessor('name', {
      header: 'Site',
      cell: (info) => (
        <Link to={`/monitor/sites/${info.row.original.id}`} className="block">
          <p className="font-medium text-slate-900 hover:text-primary-600">
            {info.getValue() || 'Unnamed Site'}
          </p>
          <p className="text-xs text-slate-500">{info.row.original.url}</p>
        </Link>
      ),
    }),
    columnHelper.accessor('health_score', {
      header: 'Health',
      cell: (info) => {
        const score = info.getValue() || 0;
        const { variant, label, icon: Icon } = getHealthBadge(score);
        return (
          <div className="flex items-center gap-2">
            <Icon className={`w-4 h-4 ${variant === 'success' ? 'text-green-500' : variant === 'warning' ? 'text-amber-500' : 'text-red-500'}`} />
            <Badge variant={variant}>{label}</Badge>
            <span className="text-sm text-slate-600">{score}/100</span>
          </div>
        );
      },
    }),
    columnHelper.accessor('uptime_percent', {
      header: 'Uptime (30d)',
      cell: (info) => {
        const uptime = info.getValue() || 100;
        const { variant, label } = getUptimeBadge(uptime);
        return <Badge variant={variant}>{label}</Badge>;
      },
    }),
    columnHelper.accessor('wp_version', {
      header: 'WordPress',
      cell: (info) => (
        <span className="text-slate-700">{info.getValue() || '-'}</span>
      ),
    }),
    columnHelper.accessor('php_version', {
      header: 'PHP',
      cell: (info) => (
        <span className="text-slate-700">{info.getValue() || '-'}</span>
      ),
    }),
    columnHelper.accessor('updates_available', {
      header: 'Updates',
      cell: (info) => {
        const updates = info.getValue() || 0;
        return updates > 0 ? (
          <Badge variant="warning">{updates} available</Badge>
        ) : (
          <Badge variant="success">Up to date</Badge>
        );
      },
    }),
    columnHelper.accessor('last_checked', {
      header: 'Last Check',
      cell: (info) => {
        const date = info.getValue();
        return date ? (
          <div className="flex items-center gap-1 text-slate-500 text-sm">
            <Clock className="w-3 h-3" />
            {new Date(date).toLocaleString()}
          </div>
        ) : (
          <span className="text-slate-400">Never</span>
        );
      },
    }),
    columnHelper.display({
      id: 'actions',
      header: '',
      cell: (info) => (
        <div className="flex items-center gap-1">
          <button
            onClick={() => refreshMutation.mutate(info.row.original.id)}
            className="p-1.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
            title="Refresh"
            disabled={refreshMutation.isPending}
          >
            <RefreshCw className={`w-4 h-4 ${refreshMutation.isPending ? 'animate-spin' : ''}`} />
          </button>
          <a
            href={info.row.original.url}
            target="_blank"
            rel="noopener noreferrer"
            className="p-1.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
            title="Visit site"
          >
            <ExternalLink className="w-4 h-4" />
          </a>
          <button
            onClick={() => setDeleteId(info.row.original.id)}
            className="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
            title="Remove"
          >
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      ),
    }),
  ];

  const pageInfo = pageDescriptions.monitor;
  const pageHelpContent = { howTo: pageInfo.howTo, tips: pageInfo.tips, useCases: pageInfo.useCases };

  return (
    <Layout title={pageInfo.title} description={pageInfo.description} helpContent={pageHelpContent} pageGuideId="monitor">
      {/* Header Actions */}
      <div className="flex items-center gap-4 mb-6">
        <div className="flex items-center gap-4 flex-1">
          <div className="max-w-64 flex-1">
            <Input
              type="text"
              placeholder="Search sites..."
              leftIcon={<Search className="w-4 h-4" />}
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <InfoTooltip content="Search by site name or URL" />
        </div>
        <button
          onClick={() => setAddModalOpen(true)}
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '8px',
            padding: '10px 20px',
            backgroundColor: '#16a34a',
            color: 'white',
            fontWeight: 500,
            borderRadius: '8px',
            border: 'none',
            cursor: 'pointer',
            flexShrink: 0,
          }}
        >
          <Plus className="w-5 h-5" />
          Add Site
        </button>
      </div>

      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <Card className="!p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
              <Server className="w-5 h-5 text-primary-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">{displaySampleData ? sampleStats.monitor.total : (data?.total || 0)}</p>
              <p className="text-sm text-slate-500 flex items-center gap-1">
                Total Sites
                <InfoTooltip content={helpContent.monitor.overview} />
              </p>
            </div>
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
              <CheckCircle className="w-5 h-5 text-green-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">{healthyCount}</p>
              <p className="text-sm text-slate-500 flex items-center gap-1">
                Healthy
                <InfoTooltip content={helpContent.monitor.health} />
              </p>
            </div>
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
              <AlertTriangle className="w-5 h-5 text-amber-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">{warningCount + criticalCount}</p>
              <p className="text-sm text-slate-500 flex items-center gap-1">
                Need Attention
                <InfoTooltip content="Sites with health scores below 80% that may need updates, security patches, or performance optimization." />
              </p>
            </div>
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <Activity className="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">{totalUpdates}</p>
              <p className="text-sm text-slate-500 flex items-center gap-1">
                Updates Available
                <InfoTooltip content={helpContent.monitor.updates} />
              </p>
            </div>
          </div>
        </Card>
      </div>

      {/* Table */}
      <Card>
        <Table
          data={sites}
          columns={columns}
          loading={isLoading}
          rowSelection={selectedRows}
          onRowSelectionChange={setSelectedRows}
        />
        {data && data.total_pages > 1 && (
          <Pagination
            page={page}
            totalPages={data.total_pages}
            total={data.total}
            perPage={20}
            onPageChange={setPage}
          />
        )}
      </Card>

      {/* Add Site Modal */}
      <Modal
        isOpen={addModalOpen}
        onClose={() => setAddModalOpen(false)}
        title="Add Site"
        description="Connect a new WordPress site to monitor"
        size="md"
      >
        <div className="space-y-4">
          <Input
            label="Site URL"
            placeholder="https://example.com"
            value={newSite.url}
            onChange={(e) => setNewSite({ ...newSite, url: e.target.value })}
            required
          />
          <Input
            label="Display Name"
            placeholder="My Website"
            value={newSite.name}
            onChange={(e) => setNewSite({ ...newSite, name: e.target.value })}
          />
          <div className="p-4 bg-slate-50 rounded-lg border border-slate-200">
            <h4 className="font-medium text-slate-900 mb-2">Setup Instructions</h4>
            <ol className="text-sm text-slate-600 space-y-1 list-decimal list-inside">
              <li>Install the Peanut Connect plugin on the target site</li>
              <li>Copy the Site Key from the plugin settings</li>
              <li>Enter it below to connect</li>
            </ol>
          </div>
          <Input
            label="Site Key"
            placeholder="xxxx-xxxx-xxxx-xxxx"
            helper="Get this from the Peanut Connect plugin on the target site"
          />
        </div>
        <div className="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-200">
          <Button variant="outline" onClick={() => setAddModalOpen(false)}>
            Cancel
          </Button>
          <Button
            onClick={() => addMutation.mutate(newSite)}
            loading={addMutation.isPending}
            disabled={!newSite.url}
          >
            Add Site
          </Button>
        </div>
      </Modal>

      {/* Delete Confirmation */}
      <ConfirmModal
        isOpen={deleteId !== null}
        onClose={() => setDeleteId(null)}
        onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
        title="Remove Site"
        message="Are you sure you want to remove this site from monitoring? This won't affect the actual website."
        confirmText="Remove"
        variant="danger"
        loading={deleteMutation.isPending}
      />
    </Layout>
  );
}

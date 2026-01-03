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
  SampleDataBanner,
} from '../components/common';
import { serversApi } from '../api/endpoints';
import type { PleskServer, HealthGrade } from '../types';

const columnHelper = createColumnHelper<PleskServer>();

// Grade badge configuration
const GRADE_CONFIG: Record<HealthGrade, { variant: 'success' | 'warning' | 'danger'; color: string }> = {
  A: { variant: 'success', color: 'text-green-600 bg-green-100' },
  B: { variant: 'success', color: 'text-blue-600 bg-blue-100' },
  C: { variant: 'warning', color: 'text-yellow-600 bg-yellow-100' },
  D: { variant: 'warning', color: 'text-orange-600 bg-orange-100' },
  F: { variant: 'danger', color: 'text-red-600 bg-red-100' },
};

function GradeBadge({ grade, score }: { grade: HealthGrade; score: number }) {
  const config = GRADE_CONFIG[grade] || GRADE_CONFIG.F;
  return (
    <div className="flex items-center gap-2">
      <span className={`inline-flex items-center justify-center w-8 h-8 rounded-full font-bold text-lg ${config.color}`}>
        {grade}
      </span>
      <span className="text-sm text-slate-600">{score}/100</span>
    </div>
  );
}

function getStatusBadge(status: string) {
  switch (status) {
    case 'active':
      return { variant: 'success' as const, label: 'Active', icon: CheckCircle };
    case 'error':
      return { variant: 'danger' as const, label: 'Error', icon: XCircle };
    case 'disconnected':
      return { variant: 'warning' as const, label: 'Disconnected', icon: AlertTriangle };
    default:
      return { variant: 'default' as const, label: status, icon: Server };
  }
}

// Sample data for demo
const sampleServers: PleskServer[] = [
  {
    id: 1,
    user_id: 1,
    server_name: 'Production Server',
    server_host: 'server1.example.com',
    server_port: 8443,
    status: 'active',
    last_check: new Date().toISOString(),
    last_health: { score: 94, grade: 'A', status: 'healthy', checks: {} },
    plesk_version: '18.0.52',
    os_info: 'Ubuntu 22.04',
    created_at: new Date().toISOString(),
  },
  {
    id: 2,
    user_id: 1,
    server_name: 'Staging Server',
    server_host: 'staging.example.com',
    server_port: 8443,
    status: 'active',
    last_check: new Date().toISOString(),
    last_health: { score: 78, grade: 'C', status: 'warning', checks: {} },
    plesk_version: '18.0.50',
    os_info: 'CentOS 8',
    created_at: new Date().toISOString(),
  },
  {
    id: 3,
    user_id: 1,
    server_name: 'Client Server',
    server_host: 'client.example.com',
    server_port: 8443,
    status: 'error',
    last_check: new Date(Date.now() - 86400000).toISOString(),
    last_health: { score: 45, grade: 'F', status: 'critical', checks: {} },
    plesk_version: '18.0.48',
    os_info: 'Debian 11',
    created_at: new Date().toISOString(),
  },
];

export default function Servers() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [selectedRows, setSelectedRows] = useState<Record<string, boolean>>({});
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const [addModalOpen, setAddModalOpen] = useState(false);
  const [newServer, setNewServer] = useState({
    server_name: '',
    server_host: '',
    server_port: 8443,
    api_key: '',
  });
  const [addError, setAddError] = useState<string | null>(null);
  const [showSampleData, setShowSampleData] = useState(true);

  const { data, isLoading } = useQuery({
    queryKey: ['servers', page, search],
    queryFn: () =>
      serversApi.getServers({
        page,
        per_page: 20,
        search: search || undefined,
      }),
  });

  const { data: overview } = useQuery({
    queryKey: ['servers-overview'],
    queryFn: serversApi.getOverview,
  });

  const addMutation = useMutation({
    mutationFn: serversApi.addServer,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['servers'] });
      queryClient.invalidateQueries({ queryKey: ['servers-overview'] });
      setAddModalOpen(false);
      setNewServer({ server_name: '', server_host: '', server_port: 8443, api_key: '' });
      setAddError(null);
    },
    onError: (error: Error) => {
      setAddError(error.message || 'Failed to add server');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: serversApi.deleteServer,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['servers'] });
      queryClient.invalidateQueries({ queryKey: ['servers-overview'] });
      setDeleteId(null);
    },
  });

  const refreshMutation = useMutation({
    mutationFn: serversApi.checkHealth,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['servers'] });
      queryClient.invalidateQueries({ queryKey: ['servers-overview'] });
    },
  });

  // Determine if we should show sample data
  const hasNoRealData = !isLoading && (!data?.data || data.data.length === 0);
  const displaySampleData = hasNoRealData && showSampleData;
  const servers = displaySampleData ? sampleServers : (data?.data || []);

  // Calculate summary stats from real data or sample
  const stats = displaySampleData
    ? {
        total_servers: sampleServers.length,
        active_servers: sampleServers.filter((s) => s.status === 'active').length,
        servers_with_errors: sampleServers.filter((s) => s.status === 'error').length,
        servers_needing_attention: sampleServers.filter((s) => (s.last_health?.score || 0) < 80).length,
      }
    : overview;

  const columns = [
    createCheckboxColumn<PleskServer>(),
    columnHelper.accessor('server_name', {
      header: 'Server',
      cell: (info) => (
        <Link to={`/servers/${info.row.original.id}`} className="block">
          <p className="font-medium text-slate-900 hover:text-primary-600">
            {info.getValue() || 'Unnamed Server'}
          </p>
          <p className="text-xs text-slate-500">{info.row.original.server_host}</p>
        </Link>
      ),
    }),
    columnHelper.display({
      id: 'health',
      header: 'Health',
      cell: (info) => {
        const health = info.row.original.last_health;
        if (!health) {
          return <span className="text-slate-400">Not checked</span>;
        }
        return <GradeBadge grade={health.grade} score={health.score} />;
      },
    }),
    columnHelper.accessor('status', {
      header: 'Status',
      cell: (info) => {
        const { variant, label, icon: Icon } = getStatusBadge(info.getValue());
        return (
          <div className="flex items-center gap-2">
            <Icon className={`w-4 h-4 ${variant === 'success' ? 'text-green-500' : variant === 'warning' ? 'text-amber-500' : 'text-red-500'}`} />
            <Badge variant={variant}>{label}</Badge>
          </div>
        );
      },
    }),
    columnHelper.accessor('plesk_version', {
      header: 'Plesk',
      cell: (info) => (
        <span className="text-slate-700">{info.getValue() || '-'}</span>
      ),
    }),
    columnHelper.accessor('os_info', {
      header: 'OS',
      cell: (info) => (
        <span className="text-slate-700 text-sm">{info.getValue() || '-'}</span>
      ),
    }),
    columnHelper.accessor('last_check', {
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
            title="Refresh health"
            disabled={refreshMutation.isPending}
          >
            <RefreshCw className={`w-4 h-4 ${refreshMutation.isPending ? 'animate-spin' : ''}`} />
          </button>
          <a
            href={`https://${info.row.original.server_host}:${info.row.original.server_port}`}
            target="_blank"
            rel="noopener noreferrer"
            className="p-1.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
            title="Open Plesk panel"
          >
            <ExternalLink className="w-4 h-4" />
          </a>
          <button
            onClick={() => setDeleteId(info.row.original.id)}
            className="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
            title="Remove server"
          >
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      ),
    }),
  ];

  const handleAddServer = () => {
    if (!newServer.server_host || !newServer.api_key) {
      setAddError('Server host and API key are required');
      return;
    }
    addMutation.mutate(newServer);
  };

  return (
    <Layout
      title="Servers"
      description="Monitor your Plesk servers"
      pageGuideId="servers"
    >
      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-blue-100 rounded-lg">
              <Server className="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">{stats?.total_servers || 0}</p>
              <p className="text-sm text-slate-500">Total Servers</p>
            </div>
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-green-100 rounded-lg">
              <CheckCircle className="w-5 h-5 text-green-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">{stats?.active_servers || 0}</p>
              <p className="text-sm text-slate-500">Active</p>
            </div>
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-amber-100 rounded-lg">
              <AlertTriangle className="w-5 h-5 text-amber-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">{stats?.servers_needing_attention || 0}</p>
              <p className="text-sm text-slate-500">Need Attention</p>
            </div>
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-red-100 rounded-lg">
              <XCircle className="w-5 h-5 text-red-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">{stats?.servers_with_errors || 0}</p>
              <p className="text-sm text-slate-500">Errors</p>
            </div>
          </div>
        </Card>
      </div>

      {/* Search and Actions */}
      <Card>
        <div className="p-4 border-b border-slate-200 flex flex-col sm:flex-row gap-4 justify-between">
          <div className="flex-1 max-w-md">
            <Input
              type="text"
              placeholder="Search servers..."
              value={search}
              onChange={(e) => {
                setSearch(e.target.value);
                setPage(1);
              }}
              leftIcon={<Search className="w-4 h-4" />}
            />
          </div>
          <Button
            icon={<Plus className="w-4 h-4" />}
            onClick={() => setAddModalOpen(true)}
          >
            Add Server
          </Button>
        </div>

        {/* Table */}
        <Table
          data={servers}
          columns={columns}
          loading={isLoading}
          rowSelection={selectedRows}
          onRowSelectionChange={setSelectedRows}
        />

        {/* Pagination */}
        {!displaySampleData && data && data.total_pages > 1 && (
          <Pagination
            page={page}
            totalPages={data.total_pages}
            total={data.total || 0}
            perPage={20}
            onPageChange={setPage}
          />
        )}
      </Card>

      {/* Add Server Modal */}
      <Modal
        isOpen={addModalOpen}
        onClose={() => {
          setAddModalOpen(false);
          setAddError(null);
        }}
        title="Add Plesk Server"
      >
        <div className="space-y-4">
          <p className="text-sm text-slate-600">
            Connect a Plesk server for monitoring. You'll need to generate an API key in Plesk under Tools & Settings → API Keys.
          </p>

          {addError && (
            <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
              {addError}
            </div>
          )}

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">
              Server Name (optional)
            </label>
            <Input
              type="text"
              placeholder="e.g., Production Server"
              value={newServer.server_name}
              onChange={(e) => setNewServer({ ...newServer, server_name: e.target.value })}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">
              Server Host <span className="text-red-500">*</span>
            </label>
            <Input
              type="text"
              placeholder="e.g., server.example.com"
              value={newServer.server_host}
              onChange={(e) => setNewServer({ ...newServer, server_host: e.target.value })}
            />
            <p className="text-xs text-slate-500 mt-1">Domain or IP address of your Plesk server</p>
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">
              Port
            </label>
            <Input
              type="number"
              placeholder="8443"
              value={newServer.server_port}
              onChange={(e) => setNewServer({ ...newServer, server_port: parseInt(e.target.value) || 8443 })}
            />
            <p className="text-xs text-slate-500 mt-1">Default Plesk port is 8443</p>
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">
              API Key <span className="text-red-500">*</span>
            </label>
            <Input
              type="password"
              placeholder="Enter your Plesk API key"
              value={newServer.api_key}
              onChange={(e) => setNewServer({ ...newServer, api_key: e.target.value })}
            />
            <p className="text-xs text-slate-500 mt-1">
              Generate in Plesk: Tools & Settings → API Keys
            </p>
          </div>

          <div className="flex justify-end gap-3 pt-4">
            <Button
              variant="secondary"
              onClick={() => {
                setAddModalOpen(false);
                setAddError(null);
              }}
            >
              Cancel
            </Button>
            <Button
              onClick={handleAddServer}
              disabled={addMutation.isPending}
            >
              {addMutation.isPending ? 'Connecting...' : 'Add Server'}
            </Button>
          </div>
        </div>
      </Modal>

      {/* Delete Confirmation */}
      <ConfirmModal
        isOpen={deleteId !== null}
        onClose={() => setDeleteId(null)}
        onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
        title="Remove Server"
        message="Are you sure you want to stop monitoring this server? This will delete all health history for this server."
        confirmText="Remove"
        variant="danger"
        loading={deleteMutation.isPending}
      />
    </Layout>
  );
}

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { createColumnHelper } from '@tanstack/react-table';
import {
  Link2,
  ExternalLink,
  Search,
  RefreshCw,
  Trash2,
  CheckCircle,
  XCircle,
  AlertCircle,
  Clock,
  Globe,
  TrendingUp,
  TrendingDown,
} from 'lucide-react';
import { Layout } from '../components/layout';
import {
  Card,
  Button,
  Input,
  Table,
  Pagination,
  Badge,
  useToast,
  ConfirmModal,
} from '../components/common';
import { backlinksApi } from '../api/endpoints';
import { pageDescriptions } from '../constants';

interface Backlink {
  id: number;
  source_url: string;
  source_domain: string;
  target_url: string;
  anchor_text: string;
  link_type: 'dofollow' | 'nofollow' | 'ugc' | 'sponsored';
  status: 'active' | 'lost' | 'broken' | 'pending';
  first_seen: string;
  last_checked: string;
  domain_authority: number | null;
}

const columnHelper = createColumnHelper<Backlink>();

export default function Backlinks() {
  const queryClient = useQueryClient();
  const toast = useToast();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [deleteId, setDeleteId] = useState<number | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['backlinks', page, search, statusFilter],
    queryFn: () => backlinksApi.getAll({
      page,
      per_page: 20,
      search: search || undefined,
      status: statusFilter as 'active' | 'lost' | 'broken' | 'pending' | undefined,
    }),
  });

  const discoverMutation = useMutation({
    mutationFn: backlinksApi.triggerDiscovery,
    onSuccess: (result) => {
      queryClient.invalidateQueries({ queryKey: ['backlinks'] });
      toast.success(`Discovery complete! Found ${result.discovered} new backlinks.`);
    },
    onError: () => {
      toast.error('Discovery failed. Please try again.');
    },
  });

  const verifyMutation = useMutation({
    mutationFn: backlinksApi.triggerVerify,
    onSuccess: (result) => {
      queryClient.invalidateQueries({ queryKey: ['backlinks'] });
      toast.success(`Verification complete! Verified ${result.verified}, lost ${result.lost}.`);
    },
    onError: () => {
      toast.error('Verification failed. Please try again.');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: backlinksApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['backlinks'] });
      setDeleteId(null);
      toast.success('Backlink removed');
    },
    onError: () => {
      toast.error('Failed to remove backlink');
    },
  });

  const getStatusBadge = (status: Backlink['status']) => {
    switch (status) {
      case 'active':
        return <Badge variant="success"><CheckCircle className="w-3 h-3 mr-1" />Active</Badge>;
      case 'lost':
        return <Badge variant="danger"><XCircle className="w-3 h-3 mr-1" />Lost</Badge>;
      case 'broken':
        return <Badge variant="warning"><AlertCircle className="w-3 h-3 mr-1" />Broken</Badge>;
      case 'pending':
        return <Badge variant="default"><Clock className="w-3 h-3 mr-1" />Pending</Badge>;
      default:
        return <Badge variant="default">{status}</Badge>;
    }
  };

  const getLinkTypeBadge = (type: Backlink['link_type']) => {
    switch (type) {
      case 'dofollow':
        return <Badge variant="success" size="sm">dofollow</Badge>;
      case 'nofollow':
        return <Badge variant="default" size="sm">nofollow</Badge>;
      case 'ugc':
        return <Badge variant="warning" size="sm">ugc</Badge>;
      case 'sponsored':
        return <Badge variant="info" size="sm">sponsored</Badge>;
      default:
        return <Badge variant="default" size="sm">{type}</Badge>;
    }
  };

  const columns = [
    columnHelper.accessor('source_domain', {
      header: 'Source',
      cell: (info) => (
        <div className="max-w-xs">
          <div className="flex items-center gap-2">
            <Globe className="w-4 h-4 text-slate-400 flex-shrink-0" />
            <span className="font-medium text-slate-900 truncate">{info.getValue()}</span>
          </div>
          <a
            href={info.row.original.source_url}
            target="_blank"
            rel="noopener noreferrer"
            className="text-xs text-primary-600 hover:underline truncate block"
          >
            {info.row.original.source_url}
          </a>
        </div>
      ),
    }),
    columnHelper.accessor('anchor_text', {
      header: 'Anchor Text',
      cell: (info) => (
        <span className="text-sm text-slate-600 truncate max-w-[200px] block">
          {info.getValue() || '(no anchor)'}
        </span>
      ),
    }),
    columnHelper.accessor('link_type', {
      header: 'Type',
      cell: (info) => getLinkTypeBadge(info.getValue()),
    }),
    columnHelper.accessor('status', {
      header: 'Status',
      cell: (info) => getStatusBadge(info.getValue()),
    }),
    columnHelper.accessor('domain_authority', {
      header: 'DA',
      cell: (info) => (
        <span className="font-medium text-slate-900">
          {info.getValue() !== null ? info.getValue() : '-'}
        </span>
      ),
    }),
    columnHelper.accessor('first_seen', {
      header: 'Discovered',
      cell: (info) => (
        <span className="text-sm text-slate-500">
          {new Date(info.getValue()).toLocaleDateString()}
        </span>
      ),
    }),
    columnHelper.display({
      id: 'actions',
      header: '',
      cell: (info) => (
        <div className="flex items-center gap-2">
          <a
            href={info.row.original.source_url}
            target="_blank"
            rel="noopener noreferrer"
            className="p-1.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
            title="Open source"
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

  const stats = data?.stats || {
    total: 0,
    active: 0,
    lost: 0,
    broken: 0,
    dofollow: 0,
    nofollow: 0,
    unique_domains: 0,
    new_30_days: 0,
    lost_7_days: 0,
  };

  const pageInfo = pageDescriptions.backlinks || {
    title: 'Backlinks',
    description: 'Discover and monitor sites linking to your content',
    howTo: ['Run discovery to find new backlinks', 'Verify existing backlinks are still active'],
    tips: ['Focus on dofollow links for SEO value', 'Monitor for lost backlinks'],
    useCases: ['Track link building progress', 'Monitor competitor links'],
  };

  return (
    <Layout title={pageInfo.title} description={pageInfo.description} helpContent={{ howTo: pageInfo.howTo, tips: pageInfo.tips, useCases: pageInfo.useCases }}>
      {/* Header Actions */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-4">
          <Input
            type="text"
            placeholder="Search backlinks..."
            leftIcon={<Search className="w-4 h-4" />}
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            fullWidth={false}
            className="w-64"
          />
          <select
            className="border border-slate-200 rounded-lg px-3 py-2 text-sm"
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="lost">Lost</option>
            <option value="broken">Broken</option>
            <option value="pending">Pending</option>
          </select>
        </div>

        <div className="flex items-center gap-3">
          <Button
            variant="outline"
            icon={<RefreshCw className="w-4 h-4" />}
            onClick={() => verifyMutation.mutate()}
            loading={verifyMutation.isPending}
          >
            Verify All
          </Button>
          <Button
            icon={<Search className="w-4 h-4" />}
            onClick={() => discoverMutation.mutate()}
            loading={discoverMutation.isPending}
          >
            Discover New
          </Button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <Card className="!p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-slate-900">{stats.total}</p>
              <p className="text-sm text-slate-500">Total Backlinks</p>
            </div>
            <Link2 className="w-8 h-8 text-primary-500" />
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-green-600">{stats.active}</p>
              <p className="text-sm text-slate-500">Active Links</p>
            </div>
            <CheckCircle className="w-8 h-8 text-green-500" />
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-slate-900">{stats.unique_domains}</p>
              <p className="text-sm text-slate-500">Unique Domains</p>
            </div>
            <Globe className="w-8 h-8 text-blue-500" />
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-slate-900">{stats.dofollow}</p>
              <p className="text-sm text-slate-500">Dofollow Links</p>
            </div>
            <TrendingUp className="w-8 h-8 text-emerald-500" />
          </div>
        </Card>
      </div>

      {/* Changes Summary */}
      <div className="grid grid-cols-2 gap-4 mb-6">
        <Card className="!p-4 bg-green-50 border-green-200">
          <div className="flex items-center gap-3">
            <TrendingUp className="w-6 h-6 text-green-600" />
            <div>
              <p className="text-lg font-bold text-green-700">+{stats.new_30_days}</p>
              <p className="text-sm text-green-600">New backlinks (30 days)</p>
            </div>
          </div>
        </Card>
        <Card className="!p-4 bg-red-50 border-red-200">
          <div className="flex items-center gap-3">
            <TrendingDown className="w-6 h-6 text-red-600" />
            <div>
              <p className="text-lg font-bold text-red-700">-{stats.lost_7_days}</p>
              <p className="text-sm text-red-600">Lost backlinks (7 days)</p>
            </div>
          </div>
        </Card>
      </div>

      {/* Table */}
      <Card>
        <Table
          data={data?.backlinks || []}
          columns={columns}
          loading={isLoading}
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

      {/* Delete Confirmation */}
      <ConfirmModal
        isOpen={deleteId !== null}
        onClose={() => setDeleteId(null)}
        onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
        title="Remove Backlink"
        message="Are you sure you want to remove this backlink from tracking? This won't affect the actual link on the external site."
        confirmText="Remove"
        variant="danger"
        loading={deleteMutation.isPending}
      />
    </Layout>
  );
}

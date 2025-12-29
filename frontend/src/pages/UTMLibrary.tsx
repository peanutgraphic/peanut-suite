import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { createColumnHelper } from '@tanstack/react-table';
import {
  Plus,
  Copy,
  Trash2,
  ExternalLink,
  Search,
  Filter,
  Download,
} from 'lucide-react';
import { Layout } from '../components/layout';
import {
  Card,
  Button,
  Table,
  Pagination,
  Badge,
  ConfirmModal,
  createCheckboxColumn,
} from '../components/common';
import { utmApi } from '../api/endpoints';
import type { UTM } from '../types';
import { useFilterStore } from '../store';
import { exportToCSV, utmExportColumns } from '../utils';

const columnHelper = createColumnHelper<UTM>();

export default function UTMLibrary() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [selectedRows, setSelectedRows] = useState<Record<string, boolean>>({});
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const [copiedId, setCopiedId] = useState<number | null>(null);

  const { utmFilters, setUTMFilter, resetUTMFilters } = useFilterStore();

  const { data, isLoading } = useQuery({
    queryKey: ['utms', page, utmFilters],
    queryFn: () =>
      utmApi.getAll({
        page,
        per_page: 20,
        search: utmFilters.search || undefined,
        utm_source: utmFilters.source || undefined,
        utm_medium: utmFilters.medium || undefined,
        utm_campaign: utmFilters.campaign || undefined,
        program: utmFilters.program || undefined,
      }),
  });

  const deleteMutation = useMutation({
    mutationFn: utmApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['utms'] });
      setDeleteId(null);
    },
  });

  const handleCopy = async (utm: UTM) => {
    await navigator.clipboard.writeText(utm.full_url);
    setCopiedId(utm.id);
    setTimeout(() => setCopiedId(null), 2000);
  };

  const columns = [
    createCheckboxColumn<UTM>(),
    columnHelper.accessor('utm_campaign', {
      header: 'Campaign',
      cell: (info) => (
        <div>
          <p className="font-medium text-slate-900">{info.getValue()}</p>
          <p className="text-xs text-slate-500 truncate max-w-xs">{info.row.original.base_url}</p>
        </div>
      ),
    }),
    columnHelper.accessor('utm_source', {
      header: 'Source',
      cell: (info) => <Badge variant="info">{info.getValue()}</Badge>,
    }),
    columnHelper.accessor('utm_medium', {
      header: 'Medium',
      cell: (info) => <Badge>{info.getValue()}</Badge>,
    }),
    columnHelper.accessor('click_count', {
      header: 'Clicks',
      cell: (info) => (
        <span className="font-medium text-slate-900">{info.getValue()}</span>
      ),
    }),
    columnHelper.accessor('created_at', {
      header: 'Created',
      cell: (info) => (
        <span className="text-slate-500">
          {new Date(info.getValue()).toLocaleDateString()}
        </span>
      ),
    }),
    columnHelper.display({
      id: 'actions',
      header: '',
      cell: (info) => (
        <div className="flex items-center gap-1">
          <button
            onClick={() => handleCopy(info.row.original)}
            className="p-1.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
            title="Copy URL"
          >
            {copiedId === info.row.original.id ? (
              <span className="text-xs text-green-600">Copied!</span>
            ) : (
              <Copy className="w-4 h-4" />
            )}
          </button>
          <a
            href={info.row.original.full_url}
            target="_blank"
            rel="noopener noreferrer"
            className="p-1.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
            title="Open URL"
          >
            <ExternalLink className="w-4 h-4" />
          </a>
          <button
            onClick={() => setDeleteId(info.row.original.id)}
            className="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
            title="Delete"
          >
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      ),
    }),
  ];

  const selectedCount = Object.values(selectedRows).filter(Boolean).length;

  return (
    <Layout title="UTM Library" description="Manage your tracked URLs">
      {/* Header Actions */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input
              type="text"
              placeholder="Search campaigns..."
              className="pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              value={utmFilters.search}
              onChange={(e) => setUTMFilter('search', e.target.value)}
            />
          </div>
          <Button
            variant="outline"
            size="sm"
            icon={<Filter className="w-4 h-4" />}
            onClick={resetUTMFilters}
          >
            Clear Filters
          </Button>
        </div>

        <div className="flex items-center gap-3">
          {selectedCount > 0 && (
            <Button variant="danger" size="sm" icon={<Trash2 className="w-4 h-4" />}>
              Delete ({selectedCount})
            </Button>
          )}
          <Button
            variant="outline"
            size="sm"
            icon={<Download className="w-4 h-4" />}
            onClick={() => data?.data && exportToCSV(data.data, utmExportColumns, 'utm-codes')}
            disabled={!data?.data?.length}
          >
            Export CSV
          </Button>
          <Link to="/utm">
            <Button icon={<Plus className="w-4 h-4" />}>Create UTM</Button>
          </Link>
        </div>
      </div>

      {/* Filters */}
      <Card className="mb-6">
        <div className="flex flex-wrap gap-4">
          <div className="flex-1 min-w-[150px]">
            <label className="block text-xs font-medium text-slate-500 mb-1">Source</label>
            <select
              className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
              value={utmFilters.source}
              onChange={(e) => setUTMFilter('source', e.target.value)}
            >
              <option value="">All Sources</option>
              <option value="google">Google</option>
              <option value="facebook">Facebook</option>
              <option value="instagram">Instagram</option>
              <option value="email">Email</option>
            </select>
          </div>
          <div className="flex-1 min-w-[150px]">
            <label className="block text-xs font-medium text-slate-500 mb-1">Medium</label>
            <select
              className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
              value={utmFilters.medium}
              onChange={(e) => setUTMFilter('medium', e.target.value)}
            >
              <option value="">All Mediums</option>
              <option value="cpc">CPC</option>
              <option value="organic">Organic</option>
              <option value="social">Social</option>
              <option value="email">Email</option>
            </select>
          </div>
          <div className="flex-1 min-w-[150px]">
            <label className="block text-xs font-medium text-slate-500 mb-1">Program</label>
            <select
              className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
              value={utmFilters.program}
              onChange={(e) => setUTMFilter('program', e.target.value)}
            >
              <option value="">All Programs</option>
            </select>
          </div>
        </div>
      </Card>

      {/* Table */}
      <Card>
        <Table
          data={data?.data || []}
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

      {/* Delete Confirmation */}
      <ConfirmModal
        isOpen={deleteId !== null}
        onClose={() => setDeleteId(null)}
        onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
        title="Delete UTM Code"
        message="Are you sure you want to delete this UTM code? This action cannot be undone."
        confirmText="Delete"
        variant="danger"
        loading={deleteMutation.isPending}
      />
    </Layout>
  );
}

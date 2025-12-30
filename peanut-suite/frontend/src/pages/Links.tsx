import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { createColumnHelper } from '@tanstack/react-table';
import {
  Plus,
  Copy,
  Trash2,
  ExternalLink,
  Search,
  QrCode,
  BarChart2,
  Link2,
  Download,
} from 'lucide-react';
import { Layout } from '../components/layout';
import {
  Card,
  Button,
  Input,
  Table,
  Pagination,
  StatusBadge,
  Modal,
  ConfirmModal,
  QRCodeModal,
  createCheckboxColumn,
  InfoTooltip,
  SampleDataBanner,
  useToast,
  BulkActionsBar,
  bulkActions,
} from '../components/common';
import { linksApi } from '../api/endpoints';
import type { Link as LinkType } from '../types';
import { useFilterStore } from '../store';
import { exportToCSV, linksExportColumns } from '../utils';
import { helpContent, pageDescriptions, sampleLinks, sampleStats } from '../constants';

const columnHelper = createColumnHelper<LinkType>();

export default function Links() {
  const queryClient = useQueryClient();
  const toast = useToast();
  const [page, setPage] = useState(1);
  const [selectedRows, setSelectedRows] = useState<Record<string, boolean>>({});
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [copiedId, setCopiedId] = useState<number | null>(null);
  const [qrLink, setQrLink] = useState<LinkType | null>(null);

  // Form state
  const [newLink, setNewLink] = useState({
    original_url: '',
    slug: '',
    title: '',
  });

  const { linkFilters, setLinkFilter } = useFilterStore();
  const [showSampleData, setShowSampleData] = useState(true);

  const { data, isLoading } = useQuery({
    queryKey: ['links', page, linkFilters],
    queryFn: () =>
      linksApi.getAll({
        page,
        per_page: 20,
        search: linkFilters.search || undefined,
        status: linkFilters.status || undefined,
      }),
  });

  const createMutation = useMutation({
    mutationFn: linksApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['links'] });
      setCreateModalOpen(false);
      setNewLink({ original_url: '', slug: '', title: '' });
      toast.success('Link created successfully');
    },
    onError: () => {
      toast.error('Failed to create link');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: linksApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['links'] });
      setDeleteId(null);
      toast.success('Link deleted');
    },
    onError: () => {
      toast.error('Failed to delete link');
    },
  });

  // Determine if we should show sample data
  const hasNoRealData = !isLoading && (!data?.data || data.data.length === 0);
  const displaySampleData = hasNoRealData && showSampleData;
  const displayData = displaySampleData ? sampleLinks : (data?.data || []);
  const displayStats = displaySampleData ? sampleStats.links : {
    total: data?.total || 0,
    total_clicks: data?.data?.reduce((acc, link) => acc + link.click_count, 0) || 0,
    active: data?.data?.filter((l) => l.status === 'active').length || 0,
  };

  const handleCopy = async (link: LinkType) => {
    await navigator.clipboard.writeText(link.short_url);
    setCopiedId(link.id);
    toast.success('Link copied to clipboard');
    setTimeout(() => setCopiedId(null), 2000);
  };

  const handleCreate = () => {
    createMutation.mutate({
      original_url: newLink.original_url,
      slug: newLink.slug || undefined,
      title: newLink.title || undefined,
    });
  };

  const columns = [
    createCheckboxColumn<LinkType>(),
    columnHelper.accessor('title', {
      header: 'Link',
      cell: (info) => (
        <div>
          <p className="font-medium text-slate-900">
            {info.getValue() || 'Untitled'}
          </p>
          <p className="text-xs text-primary-600 truncate max-w-xs">
            {info.row.original.short_url}
          </p>
        </div>
      ),
    }),
    columnHelper.accessor('original_url', {
      header: 'Destination',
      cell: (info) => (
        <p className="text-sm text-slate-500 truncate max-w-xs">{info.getValue()}</p>
      ),
    }),
    columnHelper.accessor('status', {
      header: 'Status',
      cell: (info) => <StatusBadge status={info.getValue()} />,
    }),
    columnHelper.accessor('click_count', {
      header: 'Clicks',
      cell: (info) => (
        <div className="flex items-center gap-2">
          <BarChart2 className="w-4 h-4 text-slate-400" />
          <span className="font-medium text-slate-900">{info.getValue()}</span>
        </div>
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
        <div className="flex items-center gap-2">
          {/* Prominent copy button */}
          <button
            onClick={() => handleCopy(info.row.original)}
            className={`flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg transition-all ${
              copiedId === info.row.original.id
                ? 'bg-green-100 text-green-700 border border-green-200'
                : 'bg-primary-50 text-primary-700 hover:bg-primary-100 border border-primary-200'
            }`}
            title="Copy short URL"
          >
            <Copy className="w-3.5 h-3.5" />
            {copiedId === info.row.original.id ? 'Copied!' : 'Copy'}
          </button>
          <button
            onClick={() => setQrLink(info.row.original)}
            className="p-1.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
            title="QR Code"
          >
            <QrCode className="w-4 h-4" />
          </button>
          <a
            href={info.row.original.short_url}
            target="_blank"
            rel="noopener noreferrer"
            className="p-1.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
            title="Open link"
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

  const pageInfo = pageDescriptions.links;
  const pageHelpContent = { howTo: pageInfo.howTo, tips: pageInfo.tips, useCases: pageInfo.useCases };

  return (
    <Layout title={pageInfo.title} description={pageInfo.description} helpContent={pageHelpContent} pageGuideId="links">
      {/* Header Actions */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-4">
          <Input
            type="text"
            placeholder="Search links..."
            leftIcon={<Search className="w-4 h-4" />}
            value={linkFilters.search}
            onChange={(e) => setLinkFilter('search', e.target.value)}
            fullWidth={false}
            className="w-64"
          />
        </div>

        <div className="flex items-center gap-3">
          <Button
            variant="outline"
            icon={<Download className="w-4 h-4" />}
            onClick={() => data?.data && exportToCSV(data.data, linksExportColumns, 'links')}
            disabled={!data?.data?.length}
          >
            Export CSV
          </Button>
          <Button
            data-tour="links-create"
            icon={<Plus className="w-4 h-4" />}
            onClick={() => setCreateModalOpen(true)}
          >
            Create Link
          </Button>
        </div>
      </div>

      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <Card className="!p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
              <Link2 className="w-5 h-5 text-primary-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">{displayStats.total}</p>
              <p className="text-sm text-slate-500 flex items-center gap-1">
                Total Links
                <InfoTooltip content={helpContent.links.shortUrl} />
              </p>
            </div>
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
              <BarChart2 className="w-5 h-5 text-green-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">
                {displayStats.total_clicks}
              </p>
              <p className="text-sm text-slate-500 flex items-center gap-1">
                Total Clicks
                <InfoTooltip content={helpContent.links.clickCount} />
              </p>
            </div>
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <QrCode className="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">
                {displayStats.active}
              </p>
              <p className="text-sm text-slate-500 flex items-center gap-1">
                Active Links
                <InfoTooltip content={helpContent.links.status} />
              </p>
            </div>
          </div>
        </Card>
      </div>

      {/* Table */}
      <Card>
        <Table
          data={displayData}
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

      {/* Create Modal */}
      <Modal
        isOpen={createModalOpen}
        onClose={() => setCreateModalOpen(false)}
        title="Create Short Link"
        size="md"
      >
        <div className="space-y-4">
          <Input
            label="Destination URL"
            placeholder="https://example.com/your-long-url"
            value={newLink.original_url}
            onChange={(e) => setNewLink({ ...newLink, original_url: e.target.value })}
            tooltip={helpContent.links.shortUrl}
            required
          />
          <Input
            label="Custom Slug"
            placeholder="my-custom-slug"
            value={newLink.slug}
            onChange={(e) => setNewLink({ ...newLink, slug: e.target.value })}
            helper="Leave empty for auto-generated slug"
            tooltip={helpContent.links.slug}
          />
          <Input
            label="Title"
            placeholder="Link title for reference"
            value={newLink.title}
            onChange={(e) => setNewLink({ ...newLink, title: e.target.value })}
          />
        </div>
        <div className="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-200">
          <Button variant="outline" onClick={() => setCreateModalOpen(false)}>
            Cancel
          </Button>
          <Button
            onClick={handleCreate}
            loading={createMutation.isPending}
            disabled={!newLink.original_url}
          >
            Create Link
          </Button>
        </div>
      </Modal>

      {/* Delete Confirmation */}
      <ConfirmModal
        isOpen={deleteId !== null}
        onClose={() => setDeleteId(null)}
        onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
        title="Delete Link"
        message="Are you sure you want to delete this link? This will break any existing references to this short URL."
        confirmText="Delete"
        variant="danger"
        loading={deleteMutation.isPending}
      />

      {/* QR Code Modal */}
      <QRCodeModal
        isOpen={qrLink !== null}
        onClose={() => setQrLink(null)}
        value={qrLink?.short_url || ''}
        title={qrLink?.title || 'Short Link'}
      />

      {/* Bulk Actions Bar */}
      <BulkActionsBar
        selectedCount={Object.values(selectedRows).filter(Boolean).length}
        onClear={() => setSelectedRows({})}
        entityName="links"
        actions={[
          bulkActions.export(() => {
            const selectedIds = Object.entries(selectedRows)
              .filter(([, selected]) => selected)
              .map(([id]) => parseInt(id));
            const selectedData = displayData.filter(link => selectedIds.includes(link.id));
            if (selectedData.length > 0) {
              exportToCSV(selectedData, linksExportColumns, 'selected-links');
              toast.success(`Exported ${selectedData.length} links`);
            }
          }),
          bulkActions.delete(() => {
            const selectedIds = Object.entries(selectedRows)
              .filter(([, selected]) => selected)
              .map(([id]) => parseInt(id));
            // For now, show a toast - bulk delete would need a separate mutation
            toast.info(`Would delete ${selectedIds.length} links (feature coming soon)`);
          }),
        ]}
      />
    </Layout>
  );
}

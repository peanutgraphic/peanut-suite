import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { createColumnHelper } from '@tanstack/react-table';
import { useNavigate } from 'react-router-dom';
import {
  Search,
  Filter,
  Eye,
  Trash2,
  Monitor,
  Smartphone,
  Tablet,
  Users,
  UserCheck,
  UserX,
  MousePointer,
  Calendar,
  Copy,
  Code,
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
  InfoTooltip,
  SampleDataBanner,
} from '../components/common';
import { visitorsApi } from '../api/endpoints';
import type { Visitor } from '../types';
import { helpContent, pageDescriptions, sampleVisitors, sampleStats } from '../constants';

const columnHelper = createColumnHelper<Visitor>();

const deviceIcons: Record<string, typeof Monitor> = {
  desktop: Monitor,
  mobile: Smartphone,
  tablet: Tablet,
};

export default function Visitors() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [identifiedOnly, setIdentifiedOnly] = useState(false);
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const [showSnippet, setShowSnippet] = useState(false);
  const [showSampleData, setShowSampleData] = useState(true);

  // Fetch visitors
  const { data, isLoading } = useQuery({
    queryKey: ['visitors', page, search, identifiedOnly],
    queryFn: () =>
      visitorsApi.getAll({
        page,
        per_page: 20,
        search: search || undefined,
        identified_only: identifiedOnly || undefined,
      }),
  });

  // Fetch stats
  const { data: stats } = useQuery({
    queryKey: ['visitors-stats'],
    queryFn: visitorsApi.getStats,
  });

  // Fetch snippet
  const { data: snippetData } = useQuery({
    queryKey: ['visitors-snippet'],
    queryFn: visitorsApi.getSnippet,
    enabled: showSnippet,
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: visitorsApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['visitors'] });
      queryClient.invalidateQueries({ queryKey: ['visitors-stats'] });
      setDeleteId(null);
    },
  });

  // Determine if we should show sample data
  const hasNoRealData = !isLoading && (!data?.data || data.data.length === 0);
  const displaySampleData = hasNoRealData && showSampleData;
  const displayData = displaySampleData ? sampleVisitors : (data?.data || []);
  const displayStats = displaySampleData ? sampleStats.visitors : stats;

  const columns = [
    columnHelper.accessor('visitor_id', {
      header: 'Visitor',
      cell: (info) => {
        const visitor = info.row.original;
        const DeviceIcon = deviceIcons[visitor.device_type || 'desktop'] || Monitor;
        return (
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
              <DeviceIcon className="w-4 h-4 text-slate-500" />
            </div>
            <div>
              {visitor.email ? (
                <div className="font-medium text-slate-900">{visitor.email}</div>
              ) : (
                <div className="font-mono text-xs text-slate-500">
                  {info.getValue().slice(0, 8)}...
                </div>
              )}
              <div className="text-xs text-slate-400">
                {visitor.browser || 'Unknown'} / {visitor.os || 'Unknown'}
              </div>
            </div>
          </div>
        );
      },
    }),
    columnHelper.accessor('email', {
      header: 'Status',
      cell: (info) => (
        <Badge variant={info.getValue() ? 'success' : 'default'}>
          {info.getValue() ? 'Identified' : 'Anonymous'}
        </Badge>
      ),
    }),
    columnHelper.accessor('total_visits', {
      header: 'Visits',
      cell: (info) => (
        <span className="font-medium">{info.getValue()}</span>
      ),
    }),
    columnHelper.accessor('total_pageviews', {
      header: 'Pageviews',
      cell: (info) => (
        <span className="text-slate-500">{info.getValue()}</span>
      ),
    }),
    columnHelper.accessor('first_seen', {
      header: 'First Seen',
      cell: (info) => (
        <span className="text-sm text-slate-500">
          {new Date(info.getValue()).toLocaleDateString()}
        </span>
      ),
    }),
    columnHelper.accessor('last_seen', {
      header: 'Last Seen',
      cell: (info) => (
        <span className="text-sm text-slate-500">
          {new Date(info.getValue()).toLocaleString()}
        </span>
      ),
    }),
    columnHelper.display({
      id: 'actions',
      header: '',
      cell: (info) => {
        const visitor = info.row.original;
        return (
          <div className="flex items-center gap-2 justify-end">
            <Button
              variant="ghost"
              size="sm"
              onClick={() => navigate(`/visitors/${visitor.id}`)}
              title="View Details"
            >
              <Eye className="w-4 h-4" />
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setDeleteId(visitor.id)}
              title="Delete"
            >
              <Trash2 className="w-4 h-4 text-red-500" />
            </Button>
          </div>
        );
      },
    }),
  ];

  const copySnippet = () => {
    if (snippetData?.snippet) {
      navigator.clipboard.writeText(snippetData.snippet);
    }
  };

  const pageInfo = pageDescriptions.visitors;
  const pageHelpContent = { howTo: pageInfo.howTo, tips: pageInfo.tips, useCases: pageInfo.useCases };

  return (
    <Layout title={pageInfo.title} description={pageInfo.description} helpContent={pageHelpContent} pageGuideId="visitors">
      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Stats Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
        <Card className="p-4">
          <div className="flex items-center gap-2 mb-1">
            <Users className="w-4 h-4 text-slate-400" />
            <span className="text-sm text-slate-500">Total</span>
            <InfoTooltip content={helpContent.visitors.overview} />
          </div>
          <div className="text-2xl font-bold">{displayStats?.total ?? 0}</div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-2 mb-1">
            <UserCheck className="w-4 h-4 text-green-500" />
            <span className="text-sm text-slate-500">Identified</span>
            <InfoTooltip content={helpContent.visitors.identified} />
          </div>
          <div className="text-2xl font-bold text-green-600">{displayStats?.identified ?? 0}</div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-2 mb-1">
            <UserX className="w-4 h-4 text-slate-400" />
            <span className="text-sm text-slate-500">Anonymous</span>
            <InfoTooltip content={helpContent.visitors.anonymous} />
          </div>
          <div className="text-2xl font-bold text-slate-500">{displayStats?.anonymous ?? 0}</div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-2 mb-1">
            <Calendar className="w-4 h-4 text-blue-500" />
            <span className="text-sm text-slate-500">Today</span>
          </div>
          <div className="text-2xl font-bold text-blue-600">{displayStats?.today ?? 0}</div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-2 mb-1">
            <Calendar className="w-4 h-4 text-purple-500" />
            <span className="text-sm text-slate-500">This Week</span>
          </div>
          <div className="text-2xl font-bold text-purple-600">{displayStats?.this_week ?? 0}</div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-2 mb-1">
            <MousePointer className="w-4 h-4 text-amber-500" />
            <span className="text-sm text-slate-500">Pageviews</span>
            <InfoTooltip content={helpContent.visitors.pageviews} />
          </div>
          <div className="text-2xl font-bold text-amber-600">{displayStats?.total_pageviews ?? 0}</div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-2 mb-1">
            <MousePointer className="w-4 h-4 text-cyan-500" />
            <span className="text-sm text-slate-500">Events Today</span>
          </div>
          <div className="text-2xl font-bold text-cyan-600">{displayStats?.events_today ?? 0}</div>
        </Card>
      </div>

      {/* Filters & Table */}
      <Card>
        <div className="p-4 border-b border-slate-100">
          <div className="flex items-center gap-4">
            <div className="flex-1">
              <Input
                placeholder="Search by email or visitor ID..."
                leftIcon={<Search className="w-4 h-4" />}
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </div>
            <Button
              variant={identifiedOnly ? 'primary' : 'outline'}
              onClick={() => setIdentifiedOnly(!identifiedOnly)}
            >
              <Filter className="w-4 h-4 mr-2" />
              {identifiedOnly ? 'Identified Only' : 'All Visitors'}
            </Button>
            <Button variant="outline" onClick={() => setShowSnippet(true)}>
              <Code className="w-4 h-4 mr-2" />
              Get Code
            </Button>
          </div>
        </div>

        {/* Table */}
        <Table
          data={displayData}
          columns={columns}
          loading={isLoading}
        />

        {/* Pagination */}
        {data && data.total_pages > 1 && (
          <div className="p-4 border-t border-slate-100">
            <Pagination
              page={page}
              totalPages={data.total_pages}
              total={data.total}
              perPage={data.per_page}
              onPageChange={setPage}
            />
          </div>
        )}
      </Card>

      {/* Snippet Modal */}
      <Modal
        isOpen={showSnippet}
        onClose={() => setShowSnippet(false)}
        title="Tracking Code"
        size="lg"
      >
        <div className="space-y-4">
          <p className="text-sm text-slate-600">
            Add this code snippet to your website to start tracking visitors. Place it just before the closing <code className="bg-slate-100 px-1 rounded">&lt;/body&gt;</code> tag.
          </p>

          <div>
            <div className="flex items-center justify-between mb-2">
              <label className="text-sm font-medium text-slate-500">Embed Code</label>
              <Button variant="ghost" size="sm" onClick={copySnippet}>
                <Copy className="w-4 h-4 mr-1" />
                Copy
              </Button>
            </div>
            <pre className="p-4 bg-slate-900 text-slate-100 rounded-lg text-xs overflow-auto">
              {snippetData?.snippet || 'Loading...'}
            </pre>
          </div>

          <div className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <label className="text-slate-500">Script URL</label>
              <div className="font-mono text-xs mt-1 truncate">
                {snippetData?.script_url || '-'}
              </div>
            </div>
            <div>
              <label className="text-slate-500">Site ID</label>
              <div className="font-mono text-xs mt-1">
                {snippetData?.site_id || '-'}
              </div>
            </div>
          </div>

          <div className="pt-4 border-t border-slate-100">
            <h4 className="font-medium mb-2">JavaScript API</h4>
            <div className="space-y-2 text-sm text-slate-600">
              <p><code className="bg-slate-100 px-1 rounded">peanut.track('event_name', {'{data}'});</code> - Track custom events</p>
              <p><code className="bg-slate-100 px-1 rounded">peanut.identify('email@example.com');</code> - Identify visitor</p>
              <p><code className="bg-slate-100 px-1 rounded">peanut.pageview();</code> - Track pageview (auto on load)</p>
            </div>
          </div>

          <div className="flex justify-end pt-4">
            <Button variant="secondary" onClick={() => setShowSnippet(false)}>
              Close
            </Button>
          </div>
        </div>
      </Modal>

      {/* Delete Confirmation */}
      <ConfirmModal
        isOpen={deleteId !== null}
        onClose={() => setDeleteId(null)}
        onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
        title="Delete Visitor"
        message="Are you sure you want to delete this visitor and all their events? This action cannot be undone."
        confirmText="Delete"
        loading={deleteMutation.isPending}
      />
    </Layout>
  );
}

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  Search,
  Plus,
  Trash2,
  TrendingUp,
  TrendingDown,
  Minus,
  RefreshCw,
  Target,
  Globe,
  BarChart2,
  AlertCircle,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Input, Badge, Modal, ConfirmModal, useToast, SampleDataBanner } from '../components/common';
import { seoApi } from '../api/endpoints';
import { pageDescriptions, sampleKeywords } from '../constants';

interface Keyword {
  id: number;
  keyword: string;
  target_url: string;
  search_engine: string;
  location: string;
  current_position: number | null;
  previous_position: number | null;
  change: number;
  last_checked: string;
  created_at: string;
}

export default function Keywords() {
  const queryClient = useQueryClient();
  const toast = useToast();
  const [addModalOpen, setAddModalOpen] = useState(false);
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const [selectedKeyword, setSelectedKeyword] = useState<Keyword | null>(null);
  const [showSampleData, setShowSampleData] = useState(true);

  const { data, isLoading } = useQuery({
    queryKey: ['keywords'],
    queryFn: seoApi.getKeywords,
  });

  // Determine if we should show sample data
  const realKeywords = data?.keywords || [];
  const hasNoRealData = !isLoading && realKeywords.length === 0;
  const displaySampleData = hasNoRealData && showSampleData;
  const keywords = displaySampleData ? sampleKeywords as Keyword[] : realKeywords;

  const [newKeyword, setNewKeyword] = useState({
    keyword: '',
    target_url: '',
    search_engine: 'google',
    location: 'us',
  });

  const addMutation = useMutation({
    mutationFn: seoApi.addKeyword,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['keywords'] });
      setAddModalOpen(false);
      setNewKeyword({ keyword: '', target_url: '', search_engine: 'google', location: 'us' });
      toast.success('Keyword added and ranking check queued');
    },
    onError: () => {
      toast.error('Failed to add keyword');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: seoApi.deleteKeyword,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['keywords'] });
      setDeleteId(null);
      toast.success('Keyword removed');
    },
    onError: () => {
      toast.error('Failed to remove keyword');
    },
  });

  const checkRankingsMutation = useMutation({
    mutationFn: seoApi.checkRankings,
    onSuccess: (result) => {
      queryClient.invalidateQueries({ queryKey: ['keywords'] });
      toast.success(`Checked rankings for ${result.checked} keywords`);
    },
    onError: () => {
      toast.error('Failed to check rankings');
    },
  });

  const getPositionBadge = (position: number | null) => {
    if (position === null) {
      return <Badge variant="default">Not ranked</Badge>;
    }
    if (position <= 3) {
      return <Badge variant="success">#{position}</Badge>;
    }
    if (position <= 10) {
      return <Badge variant="info">#{position}</Badge>;
    }
    if (position <= 20) {
      return <Badge variant="warning">#{position}</Badge>;
    }
    return <Badge variant="default">#{position}</Badge>;
  };

  const getChangeIndicator = (change: number) => {
    if (change > 0) {
      return (
        <span className="flex items-center text-green-600 text-sm">
          <TrendingUp className="w-4 h-4 mr-1" />
          +{change}
        </span>
      );
    }
    if (change < 0) {
      return (
        <span className="flex items-center text-red-600 text-sm">
          <TrendingDown className="w-4 h-4 mr-1" />
          {change}
        </span>
      );
    }
    return (
      <span className="flex items-center text-slate-400 text-sm">
        <Minus className="w-4 h-4 mr-1" />
        0
      </span>
    );
  };

  const rankedKeywords = keywords.filter((k) => k.current_position !== null);
  const avgPosition = rankedKeywords.length
    ? Math.round(rankedKeywords.reduce((sum, k) => sum + (k.current_position || 0), 0) / rankedKeywords.length)
    : 0;
  const top10Count = keywords.filter((k) => k.current_position !== null && k.current_position <= 10).length;
  const improvedCount = keywords.filter((k) => k.change > 0).length;
  const declinedCount = keywords.filter((k) => k.change < 0).length;

  const pageInfo = pageDescriptions.keywords || {
    title: 'Keyword Rankings',
    description: 'Track your search engine rankings',
    howTo: ['Add keywords to track', 'Monitor position changes'],
    tips: ['Focus on keywords you can realistically rank for', 'Track branded and non-branded terms'],
    useCases: ['SEO performance monitoring', 'Competitor tracking'],
  };

  return (
    <Layout title={pageInfo.title} description={pageInfo.description} helpContent={{ howTo: pageInfo.howTo, tips: pageInfo.tips, useCases: pageInfo.useCases }} pageGuideId="keywords">
      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Header Actions */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-4">
          <p className="text-sm text-slate-500">
            Tracking <span className="font-medium text-slate-900">{keywords.length}</span> keywords
          </p>
        </div>
        <div className="flex items-center gap-3">
          <Button
            variant="outline"
            icon={<RefreshCw className="w-4 h-4" />}
            onClick={() => checkRankingsMutation.mutate()}
            loading={checkRankingsMutation.isPending}
          >
            Check Rankings
          </Button>
          <Button icon={<Plus className="w-4 h-4" />} onClick={() => setAddModalOpen(true)}>
            Add Keyword
          </Button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <Card className="!p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-slate-900">{keywords.length}</p>
              <p className="text-sm text-slate-500">Total Keywords</p>
            </div>
            <Target className="w-8 h-8 text-primary-500" />
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-slate-900">{avgPosition || '-'}</p>
              <p className="text-sm text-slate-500">Avg Position</p>
            </div>
            <BarChart2 className="w-8 h-8 text-blue-500" />
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-green-600">{top10Count}</p>
              <p className="text-sm text-slate-500">Top 10 Rankings</p>
            </div>
            <TrendingUp className="w-8 h-8 text-green-500" />
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-slate-900">
                <span className="text-green-600">{improvedCount}</span>
                {' / '}
                <span className="text-red-600">{declinedCount}</span>
              </p>
              <p className="text-sm text-slate-500">Improved / Declined</p>
            </div>
            <TrendingDown className="w-8 h-8 text-slate-400" />
          </div>
        </Card>
      </div>

      {/* API Notice */}
      <Card className="!p-4 bg-amber-50 border-amber-200 mb-6">
        <div className="flex items-start gap-3">
          <AlertCircle className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
          <div>
            <p className="font-medium text-amber-800">Rank checking requires API configuration</p>
            <p className="text-sm text-amber-700">
              Configure your DataForSEO API credentials in Settings to enable automatic rank checking.
              Without an API key, positions will show as "Not ranked".
            </p>
          </div>
        </div>
      </Card>

      {/* Keywords Table */}
      <Card>
        {isLoading ? (
          <div className="animate-pulse space-y-3 p-4">
            {[1, 2, 3, 4, 5].map((i) => (
              <div key={i} className="h-12 bg-slate-100 rounded-lg" />
            ))}
          </div>
        ) : keywords.length === 0 ? (
          <div className="text-center py-12 text-slate-500">
            <Search className="w-16 h-16 mx-auto mb-4 text-slate-300" />
            <p className="text-lg font-medium">No keywords tracked yet</p>
            <p className="text-sm">Add keywords to start tracking your search rankings</p>
            <Button className="mt-4" onClick={() => setAddModalOpen(true)}>
              Add Your First Keyword
            </Button>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-slate-200">
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Keyword</th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Position</th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Change</th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Search Engine</th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Last Checked</th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500"></th>
                </tr>
              </thead>
              <tbody>
                {keywords.map((keyword) => (
                  <tr
                    key={keyword.id}
                    className="border-b border-slate-100 hover:bg-slate-50 cursor-pointer"
                    onClick={() => setSelectedKeyword(keyword)}
                  >
                    <td className="py-3 px-4">
                      <div>
                        <p className="font-medium text-slate-900">{keyword.keyword}</p>
                        <p className="text-xs text-slate-500 truncate max-w-xs">
                          {keyword.target_url || 'Homepage'}
                        </p>
                      </div>
                    </td>
                    <td className="py-3 px-4">{getPositionBadge(keyword.current_position)}</td>
                    <td className="py-3 px-4">{getChangeIndicator(keyword.change)}</td>
                    <td className="py-3 px-4">
                      <div className="flex items-center gap-2">
                        <Globe className="w-4 h-4 text-slate-400" />
                        <span className="text-sm text-slate-600 capitalize">
                          {keyword.search_engine}
                        </span>
                        <span className="text-xs text-slate-400 uppercase">
                          ({keyword.location})
                        </span>
                      </div>
                    </td>
                    <td className="py-3 px-4 text-sm text-slate-500">
                      {keyword.last_checked
                        ? new Date(keyword.last_checked).toLocaleDateString()
                        : 'Never'}
                    </td>
                    <td className="py-3 px-4">
                      {!displaySampleData && (
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            setDeleteId(keyword.id);
                          }}
                          className="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      {/* Add Keyword Modal */}
      <Modal
        isOpen={addModalOpen}
        onClose={() => setAddModalOpen(false)}
        title="Add Keyword to Track"
        size="md"
      >
        <div className="space-y-4">
          <Input
            label="Keyword"
            placeholder="e.g., wordpress marketing plugin"
            value={newKeyword.keyword}
            onChange={(e) => setNewKeyword({ ...newKeyword, keyword: e.target.value })}
            required
          />
          <Input
            label="Target URL"
            placeholder="https://example.com/page (leave empty for homepage)"
            value={newKeyword.target_url}
            onChange={(e) => setNewKeyword({ ...newKeyword, target_url: e.target.value })}
            helper="The page you want to rank for this keyword"
          />
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">
                Search Engine
              </label>
              <select
                className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                value={newKeyword.search_engine}
                onChange={(e) => setNewKeyword({ ...newKeyword, search_engine: e.target.value })}
              >
                <option value="google">Google</option>
                <option value="bing">Bing</option>
                <option value="yahoo">Yahoo</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">Location</label>
              <select
                className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                value={newKeyword.location}
                onChange={(e) => setNewKeyword({ ...newKeyword, location: e.target.value })}
              >
                <option value="us">United States</option>
                <option value="uk">United Kingdom</option>
                <option value="ca">Canada</option>
                <option value="au">Australia</option>
                <option value="de">Germany</option>
                <option value="fr">France</option>
              </select>
            </div>
          </div>
        </div>
        <div className="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-200">
          <Button variant="outline" onClick={() => setAddModalOpen(false)}>
            Cancel
          </Button>
          <Button
            onClick={() => addMutation.mutate(newKeyword)}
            loading={addMutation.isPending}
            disabled={!newKeyword.keyword}
          >
            Add Keyword
          </Button>
        </div>
      </Modal>

      {/* Keyword History Modal */}
      {selectedKeyword && (
        <KeywordHistoryModal
          keyword={selectedKeyword}
          onClose={() => setSelectedKeyword(null)}
        />
      )}

      {/* Delete Confirmation */}
      <ConfirmModal
        isOpen={deleteId !== null}
        onClose={() => setDeleteId(null)}
        onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
        title="Remove Keyword"
        message="Are you sure you want to stop tracking this keyword? Historical data will be deleted."
        confirmText="Remove"
        variant="danger"
        loading={deleteMutation.isPending}
      />
    </Layout>
  );
}

function KeywordHistoryModal({
  keyword,
  onClose,
}: {
  keyword: Keyword;
  onClose: () => void;
}) {
  const { data: historyData, isLoading } = useQuery({
    queryKey: ['keyword-history', keyword.id],
    queryFn: () => seoApi.getKeywordHistory(keyword.id, 30),
  });

  return (
    <Modal isOpen onClose={onClose} title={`Ranking History: "${keyword.keyword}"`} size="lg">
      <div className="space-y-4">
        <div className="flex items-center gap-4 p-4 bg-slate-50 rounded-lg">
          <div>
            <p className="text-sm text-slate-500">Current Position</p>
            <p className="text-2xl font-bold text-slate-900">
              {keyword.current_position !== null ? `#${keyword.current_position}` : 'Not ranked'}
            </p>
          </div>
          <div className="w-px h-12 bg-slate-200" />
          <div>
            <p className="text-sm text-slate-500">Previous Position</p>
            <p className="text-2xl font-bold text-slate-900">
              {keyword.previous_position !== null ? `#${keyword.previous_position}` : 'N/A'}
            </p>
          </div>
          <div className="w-px h-12 bg-slate-200" />
          <div>
            <p className="text-sm text-slate-500">Change</p>
            <p
              className={`text-2xl font-bold ${
                keyword.change > 0
                  ? 'text-green-600'
                  : keyword.change < 0
                  ? 'text-red-600'
                  : 'text-slate-400'
              }`}
            >
              {keyword.change > 0 ? '+' : ''}
              {keyword.change}
            </p>
          </div>
        </div>

        <div>
          <h4 className="font-medium text-slate-900 mb-3">Position History (Last 30 Days)</h4>
          {isLoading ? (
            <div className="animate-pulse h-32 bg-slate-100 rounded-lg" />
          ) : !historyData?.history?.length ? (
            <p className="text-sm text-slate-500 italic">No history data available</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-slate-200">
                    <th className="text-left py-2 px-3 text-sm font-medium text-slate-500">Date</th>
                    <th className="text-left py-2 px-3 text-sm font-medium text-slate-500">
                      Position
                    </th>
                    <th className="text-left py-2 px-3 text-sm font-medium text-slate-500">
                      Ranking URL
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {historyData.history.map((entry, index) => (
                    <tr key={index} className="border-b border-slate-100">
                      <td className="py-2 px-3 text-sm">
                        {new Date(entry.checked_at).toLocaleDateString()}
                      </td>
                      <td className="py-2 px-3">
                        {entry.position !== null ? (
                          <Badge
                            variant={entry.position <= 10 ? 'success' : 'default'}
                            size="sm"
                          >
                            #{entry.position}
                          </Badge>
                        ) : (
                          <span className="text-sm text-slate-400">Not ranked</span>
                        )}
                      </td>
                      <td className="py-2 px-3 text-sm text-slate-500 truncate max-w-xs">
                        {entry.ranking_url || '-'}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
      <div className="flex justify-end mt-6 pt-4 border-t border-slate-200">
        <Button variant="outline" onClick={onClose}>
          Close
        </Button>
      </div>
    </Modal>
  );
}

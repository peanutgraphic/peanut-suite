import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  FileText,
  Settings,
  RefreshCw,
  Send,
  Clock,
  TrendingUp,
  TrendingDown,
  Minus,
  AlertTriangle,
  CheckCircle,
} from 'lucide-react';
import { Layout } from '../components/layout';
import {
  Card,
  Button,
  Input,
  Textarea,
  Select,
  Badge,
  SampleDataBanner,
} from '../components/common';
import { healthReportsApi } from '../api/endpoints';
import type { HealthReport, HealthReportSettings, HealthGrade, HealthReportItem, HealthReportRecommendation } from '../types';

// Grade badge configuration
const GRADE_CONFIG: Record<HealthGrade, { color: string; bgColor: string }> = {
  A: { color: 'text-green-600', bgColor: 'bg-green-100' },
  B: { color: 'text-blue-600', bgColor: 'bg-blue-100' },
  C: { color: 'text-yellow-600', bgColor: 'bg-yellow-100' },
  D: { color: 'text-orange-600', bgColor: 'bg-orange-100' },
  F: { color: 'text-red-600', bgColor: 'bg-red-100' },
};

function GradeCircle({ grade, score, size = 'lg' }: { grade: HealthGrade | string; score: number; size?: 'sm' | 'lg' }) {
  const config = GRADE_CONFIG[grade as HealthGrade] || { color: 'text-slate-600', bgColor: 'bg-slate-100' };
  const sizeClasses = size === 'lg' ? 'w-24 h-24 text-4xl' : 'w-12 h-12 text-xl';

  return (
    <div className="flex flex-col items-center">
      <div className={`${sizeClasses} rounded-full ${config.bgColor} flex items-center justify-center`}>
        <span className={`font-bold ${config.color}`}>{grade}</span>
      </div>
      <span className={`mt-2 font-medium text-slate-700 ${size === 'lg' ? 'text-lg' : 'text-sm'}`}>{score}/100</span>
    </div>
  );
}

function TrendIndicator({ change }: { change?: string | null }) {
  if (!change) {
    return <span className="text-slate-400 text-sm">First report</span>;
  }

  const numChange = parseInt(change, 10);

  if (numChange > 0) {
    return (
      <div className="flex items-center gap-1 text-green-600">
        <TrendingUp className="w-4 h-4" />
        <span className="text-sm font-medium">{change} from last</span>
      </div>
    );
  }

  if (numChange < 0) {
    return (
      <div className="flex items-center gap-1 text-red-600">
        <TrendingDown className="w-4 h-4" />
        <span className="text-sm font-medium">{change} from last</span>
      </div>
    );
  }

  return (
    <div className="flex items-center gap-1 text-slate-500">
      <Minus className="w-4 h-4" />
      <span className="text-sm font-medium">No change</span>
    </div>
  );
}

const PRIORITY_CONFIG = {
  critical: { color: 'text-red-700', bg: 'bg-red-100', border: 'border-red-200' },
  high: { color: 'text-orange-700', bg: 'bg-orange-100', border: 'border-orange-200' },
  medium: { color: 'text-yellow-700', bg: 'bg-yellow-100', border: 'border-yellow-200' },
  low: { color: 'text-slate-700', bg: 'bg-slate-100', border: 'border-slate-200' },
};

type TabType = 'overview' | 'history' | 'settings';

// Sample data for demo
const sampleReport: HealthReport = {
  id: 1,
  user_id: 1,
  report_type: 'weekly',
  period_start: '2025-01-20',
  period_end: '2025-01-26',
  overall_grade: 'B',
  overall_score: 84,
  sites_data: {
    summary: { total: 5, healthy: 3, warning: 1, critical: 1 },
    items: [
      { name: 'example.com', score: 95, grade: 'A', issues: [] },
      { name: 'blog.example.com', score: 72, grade: 'C', issues: ['3 plugin updates', 'PHP 7.4'] },
      { name: 'shop.example.com', score: 45, grade: 'F', issues: ['WordPress outdated', 'SSL expiring'] },
    ],
  },
  servers_data: {
    summary: { total: 2, healthy: 1, warning: 1, critical: 0 },
    items: [
      { name: 'Production Server', score: 91, grade: 'A', issues: [] },
      { name: 'Staging Server', score: 82, grade: 'B', issues: ['Disk at 85%'] },
    ],
  },
  recommendations: [
    { priority: 'critical', message: 'SSL expires in 7 days on shop.example.com' },
    { priority: 'high', message: 'Update WordPress core on shop.example.com' },
    { priority: 'high', message: 'Disk usage at 85% on Staging Server' },
    { priority: 'medium', message: 'Update 3 plugins on blog.example.com' },
  ],
  trends: { this_week: 84, last_week: 81, change: '+3' },
  sent_at: null,
  created_at: new Date().toISOString(),
};

const sampleHistory: HealthReport[] = [
  { ...sampleReport },
  { ...sampleReport, id: 2, overall_grade: 'B', overall_score: 81, period_start: '2025-01-13', period_end: '2025-01-19', sent_at: new Date().toISOString(), created_at: new Date(Date.now() - 7 * 86400000).toISOString() },
  { ...sampleReport, id: 3, overall_grade: 'C', overall_score: 78, period_start: '2025-01-06', period_end: '2025-01-12', sent_at: null, created_at: new Date(Date.now() - 14 * 86400000).toISOString() },
];

const defaultSettings: HealthReportSettings = {
  id: 0,
  user_id: 1,
  enabled: true,
  frequency: 'weekly',
  day_of_week: 1,
  send_time: '08:00',
  recipients: [],
  include_sites: true,
  include_servers: true,
  include_recommendations: true,
  selected_site_ids: [],
  selected_server_ids: [],
};

export default function HealthReports() {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState<TabType>('overview');
  const [showSampleData, setShowSampleData] = useState(true);

  const { data: latestReport, isLoading: reportLoading } = useQuery({
    queryKey: ['health-report-latest'],
    queryFn: healthReportsApi.getLatest,
  });

  const { data: reportHistory } = useQuery({
    queryKey: ['health-report-history'],
    queryFn: () => healthReportsApi.getHistory({ per_page: 10 }),
    enabled: activeTab === 'history',
  });

  const { data: settings } = useQuery({
    queryKey: ['health-report-settings'],
    queryFn: healthReportsApi.getSettings,
  });

  const { data: availableItems } = useQuery({
    queryKey: ['health-report-available-items'],
    queryFn: healthReportsApi.getAvailableItems,
    enabled: activeTab === 'settings',
  });

  const [localSettings, setLocalSettings] = useState<HealthReportSettings | null>(null);

  // Initialize local settings when data loads
  if (settings && !localSettings) {
    setLocalSettings(settings);
  }

  const generateMutation = useMutation({
    mutationFn: healthReportsApi.generate,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['health-report-latest'] });
      queryClient.invalidateQueries({ queryKey: ['health-report-history'] });
    },
  });

  const sendMutation = useMutation({
    mutationFn: (reportId?: number) => healthReportsApi.send(reportId),
  });

  const saveSettingsMutation = useMutation({
    mutationFn: healthReportsApi.updateSettings,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['health-report-settings'] });
    },
  });

  // Determine if we should show sample data
  const hasNoRealData = !reportLoading && !latestReport;
  const displaySampleData = hasNoRealData && showSampleData;
  const report = displaySampleData ? sampleReport : latestReport;
  const history = displaySampleData ? sampleHistory : (reportHistory || []);
  const currentSettings = localSettings || (displaySampleData ? defaultSettings : settings);

  const tabs: { id: TabType; label: string; icon: typeof FileText }[] = [
    { id: 'overview', label: 'Latest Report', icon: FileText },
    { id: 'history', label: 'History', icon: Clock },
    { id: 'settings', label: 'Settings', icon: Settings },
  ];

  const dayOptions = [
    { value: '0', label: 'Sunday' },
    { value: '1', label: 'Monday' },
    { value: '2', label: 'Tuesday' },
    { value: '3', label: 'Wednesday' },
    { value: '4', label: 'Thursday' },
    { value: '5', label: 'Friday' },
    { value: '6', label: 'Saturday' },
  ];

  return (
    <Layout
      title="Health Reports"
      description="Weekly health reports for your sites and servers"
      pageGuideId="health-reports"
    >
      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Header Actions */}
      <div className="flex justify-end gap-2 mb-6">
        <Button
          variant="secondary"
          icon={<RefreshCw className={`w-4 h-4 ${generateMutation.isPending ? 'animate-spin' : ''}`} />}
          onClick={() => generateMutation.mutate()}
          disabled={generateMutation.isPending}
        >
          Generate Now
        </Button>
        {report && (
          <Button
            icon={<Send className="w-4 h-4" />}
            onClick={() => sendMutation.mutate(report.id)}
            disabled={sendMutation.isPending}
          >
            {sendMutation.isPending ? 'Sending...' : 'Send Report'}
          </Button>
        )}
      </div>

      {/* Tabs */}
      <div className="border-b border-slate-200 mb-6">
        <div className="flex gap-1 overflow-x-auto">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap ${
                activeTab === tab.id
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
              }`}
            >
              <tab.icon className="w-4 h-4" />
              {tab.label}
            </button>
          ))}
        </div>
      </div>

      {/* Overview Tab */}
      {activeTab === 'overview' && report && (
        <div className="space-y-6">
          {/* Overall Grade Card */}
          <Card className="p-6">
            <div className="flex flex-col md:flex-row md:items-center gap-6">
              <GradeCircle grade={report.overall_grade} score={report.overall_score} />
              <div className="flex-1">
                <h3 className="text-lg font-semibold text-slate-900 mb-2">Overall Health</h3>
                <p className="text-slate-600 mb-3">
                  {report.period_start} to {report.period_end}
                </p>
                <TrendIndicator change={report.trends?.change} />
              </div>
            </div>
          </Card>

          {/* Summary Stats */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <Card className="p-4 text-center">
              <p className="text-3xl font-bold text-slate-900">{report.sites_data?.summary?.total || 0}</p>
              <p className="text-sm text-slate-500">Total Sites</p>
            </Card>
            <Card className="p-4 text-center">
              <p className="text-3xl font-bold text-green-600">{report.sites_data?.summary?.healthy || 0}</p>
              <p className="text-sm text-slate-500">Healthy Sites</p>
            </Card>
            <Card className="p-4 text-center">
              <p className="text-3xl font-bold text-slate-900">{report.servers_data?.summary?.total || 0}</p>
              <p className="text-sm text-slate-500">Total Servers</p>
            </Card>
            <Card className="p-4 text-center">
              <p className="text-3xl font-bold text-green-600">{report.servers_data?.summary?.healthy || 0}</p>
              <p className="text-sm text-slate-500">Healthy Servers</p>
            </Card>
          </div>

          {/* Recommendations */}
          {report.recommendations && report.recommendations.length > 0 && (
            <Card>
              <div className="p-4 border-b border-slate-200">
                <h3 className="font-semibold text-slate-900 flex items-center gap-2">
                  <AlertTriangle className="w-5 h-5 text-amber-500" />
                  Top Issues to Address
                </h3>
              </div>
              <div className="divide-y divide-slate-200">
                {report.recommendations.slice(0, 5).map((rec: HealthReportRecommendation, idx: number) => {
                  const config = PRIORITY_CONFIG[rec.priority as keyof typeof PRIORITY_CONFIG] || PRIORITY_CONFIG.medium;
                  return (
                    <div key={idx} className={`p-4 ${config.bg}`}>
                      <div className="flex items-start justify-between gap-4">
                        <div>
                          <p className={`font-medium ${config.color}`}>{rec.message}</p>
                        </div>
                        <Badge variant={rec.priority === 'critical' ? 'danger' : rec.priority === 'high' ? 'warning' : 'default'}>
                          {rec.priority}
                        </Badge>
                      </div>
                    </div>
                  );
                })}
              </div>
            </Card>
          )}

          {/* Sites List */}
          {report.sites_data?.items && report.sites_data.items.length > 0 && (
            <Card>
              <div className="p-4 border-b border-slate-200">
                <h3 className="font-semibold text-slate-900">Site Health</h3>
              </div>
              <div className="divide-y divide-slate-200">
                {report.sites_data.items.map((site: HealthReportItem, idx: number) => (
                  <div key={site.id || idx} className="p-4 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                      <GradeCircle grade={site.grade} score={site.score} size="sm" />
                      <div>
                        <p className="font-medium text-slate-900">{site.name}</p>
                        <p className="text-sm text-slate-500">
                          {site.issues?.length ? site.issues.slice(0, 2).join(', ') : 'All good'}
                        </p>
                      </div>
                    </div>
                    <Badge variant={site.score >= 80 ? 'success' : site.score >= 60 ? 'warning' : 'danger'}>
                      {site.score >= 80 ? 'healthy' : site.score >= 60 ? 'warning' : 'critical'}
                    </Badge>
                  </div>
                ))}
              </div>
            </Card>
          )}

          {/* Servers List */}
          {report.servers_data?.items && report.servers_data.items.length > 0 && (
            <Card>
              <div className="p-4 border-b border-slate-200">
                <h3 className="font-semibold text-slate-900">Server Health</h3>
              </div>
              <div className="divide-y divide-slate-200">
                {report.servers_data.items.map((server: HealthReportItem, idx: number) => (
                  <div key={server.id || idx} className="p-4 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                      <GradeCircle grade={server.grade} score={server.score} size="sm" />
                      <div>
                        <p className="font-medium text-slate-900">{server.name}</p>
                        <p className="text-sm text-slate-500">
                          {server.issues?.length ? server.issues.slice(0, 2).join(', ') : 'All good'}
                        </p>
                      </div>
                    </div>
                    <Badge variant={server.score >= 80 ? 'success' : server.score >= 60 ? 'warning' : 'danger'}>
                      {server.score >= 80 ? 'healthy' : server.score >= 60 ? 'warning' : 'critical'}
                    </Badge>
                  </div>
                ))}
              </div>
            </Card>
          )}
        </div>
      )}

      {/* No Report State */}
      {activeTab === 'overview' && !report && !reportLoading && (
        <Card className="p-8 text-center">
          <FileText className="w-12 h-12 text-slate-300 mx-auto mb-4" />
          <p className="text-slate-500 mb-4">No health reports generated yet</p>
          <Button onClick={() => generateMutation.mutate()} disabled={generateMutation.isPending}>
            Generate First Report
          </Button>
        </Card>
      )}

      {/* History Tab */}
      {activeTab === 'history' && (
        <Card>
          <div className="p-4 border-b border-slate-200">
            <h3 className="font-semibold text-slate-900">Report History</h3>
          </div>
          {history.length > 0 ? (
            <div className="divide-y divide-slate-200">
              {history.map((item: HealthReport) => {
                const config = GRADE_CONFIG[item.overall_grade as HealthGrade] || { color: 'text-slate-600', bgColor: 'bg-slate-100' };
                return (
                  <div key={item.id} className="p-4 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                      <div className={`w-10 h-10 rounded-full ${config.bgColor} flex items-center justify-center`}>
                        <span className={`font-bold ${config.color}`}>{item.overall_grade}</span>
                      </div>
                      <div>
                        <p className="font-medium text-slate-900">
                          {item.period_start} to {item.period_end}
                        </p>
                        <p className="text-sm text-slate-500">
                          Score: {item.overall_score}/100 &bull; {item.report_type}
                        </p>
                      </div>
                    </div>
                    <div className="flex items-center gap-3">
                      {item.sent_at ? (
                        <div className="flex items-center gap-1 text-green-600">
                          <CheckCircle className="w-4 h-4" />
                          <span className="text-sm">Sent</span>
                        </div>
                      ) : (
                        <span className="text-sm text-slate-400">Not sent</span>
                      )}
                      <Button
                        variant="secondary"
                        size="sm"
                        icon={<Send className="w-3 h-3" />}
                        onClick={() => sendMutation.mutate(item.id)}
                        disabled={sendMutation.isPending}
                      >
                        Send
                      </Button>
                    </div>
                  </div>
                );
              })}
            </div>
          ) : (
            <div className="p-8 text-center text-slate-500">
              No reports in history yet
            </div>
          )}
        </Card>
      )}

      {/* Settings Tab */}
      {activeTab === 'settings' && currentSettings && (
        <Card className="p-6">
          <h3 className="font-semibold text-slate-900 mb-6">Report Settings</h3>
          <div className="space-y-6 max-w-xl">
            {/* Enable/Disable */}
            <div className="flex items-center justify-between">
              <div>
                <p className="font-medium text-slate-900">Enable Automated Reports</p>
                <p className="text-sm text-slate-500">Send reports on the configured schedule</p>
              </div>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={currentSettings.enabled}
                  onChange={(e) => setLocalSettings({ ...currentSettings, enabled: e.target.checked })}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
              </label>
            </div>

            {/* Frequency */}
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Frequency</label>
              <Select
                value={currentSettings.frequency}
                onChange={(e) => setLocalSettings({ ...currentSettings, frequency: e.target.value as 'weekly' | 'monthly' })}
                options={[
                  { value: 'weekly', label: 'Weekly' },
                  { value: 'monthly', label: 'Monthly' },
                ]}
              />
            </div>

            {/* Day of Week (for weekly) */}
            {currentSettings.frequency === 'weekly' && (
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Send On</label>
                <Select
                  value={String(currentSettings.day_of_week)}
                  onChange={(e) => setLocalSettings({ ...currentSettings, day_of_week: parseInt(e.target.value) })}
                  options={dayOptions}
                />
              </div>
            )}

            {/* Send Time */}
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Send Time</label>
              <Input
                type="time"
                value={currentSettings.send_time}
                onChange={(e) => setLocalSettings({ ...currentSettings, send_time: e.target.value })}
              />
            </div>

            {/* Recipients */}
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Recipients</label>
              <Textarea
                value={Array.isArray(currentSettings.recipients) ? currentSettings.recipients.join('\n') : currentSettings.recipients}
                onChange={(e) => setLocalSettings({ ...currentSettings, recipients: e.target.value.split('\n').filter(Boolean) })}
                placeholder="Enter email addresses, one per line"
                rows={3}
              />
              <p className="text-xs text-slate-500 mt-1">One email address per line</p>
            </div>

            {/* Include Options */}
            <div className="space-y-3">
              <p className="font-medium text-slate-900">Include in Reports</p>
              <label className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={currentSettings.include_sites}
                  onChange={(e) => setLocalSettings({ ...currentSettings, include_sites: e.target.checked })}
                  className="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                />
                <span className="text-slate-700">WordPress Sites</span>
              </label>
              <label className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={currentSettings.include_servers}
                  onChange={(e) => setLocalSettings({ ...currentSettings, include_servers: e.target.checked })}
                  className="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                />
                <span className="text-slate-700">Plesk Servers</span>
              </label>
              <label className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={currentSettings.include_recommendations}
                  onChange={(e) => setLocalSettings({ ...currentSettings, include_recommendations: e.target.checked })}
                  className="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                />
                <span className="text-slate-700">Recommendations</span>
              </label>
            </div>

            {/* Site Selection */}
            {currentSettings.include_sites && availableItems && availableItems.sites.length > 0 && (
              <div className="space-y-3 p-4 bg-slate-50 rounded-lg">
                <div className="flex items-center justify-between">
                  <p className="font-medium text-slate-900">Select Sites to Include</p>
                  <button
                    type="button"
                    className="text-sm text-primary-600 hover:text-primary-700"
                    onClick={() => {
                      const allSiteIds = availableItems.sites.map(s => s.id);
                      const allSelected = currentSettings.selected_site_ids?.length === allSiteIds.length;
                      setLocalSettings({
                        ...currentSettings,
                        selected_site_ids: allSelected ? [] : allSiteIds,
                      });
                    }}
                  >
                    {currentSettings.selected_site_ids?.length === availableItems.sites.length ? 'Deselect All' : 'Select All'}
                  </button>
                </div>
                <p className="text-xs text-slate-500">
                  {currentSettings.selected_site_ids?.length === 0 || !currentSettings.selected_site_ids
                    ? 'All sites will be included (none specifically selected)'
                    : `${currentSettings.selected_site_ids.length} site${currentSettings.selected_site_ids.length !== 1 ? 's' : ''} selected`}
                </p>
                <div className="space-y-2 max-h-48 overflow-y-auto">
                  {availableItems.sites.map((site) => (
                    <label key={site.id} className="flex items-center gap-2">
                      <input
                        type="checkbox"
                        checked={currentSettings.selected_site_ids?.includes(site.id) ?? false}
                        onChange={(e) => {
                          const currentIds = currentSettings.selected_site_ids || [];
                          const newIds = e.target.checked
                            ? [...currentIds, site.id]
                            : currentIds.filter(id => id !== site.id);
                          setLocalSettings({ ...currentSettings, selected_site_ids: newIds });
                        }}
                        className="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                      <span className="text-sm text-slate-700">{site.name}</span>
                      <span className="text-xs text-slate-400">{site.url}</span>
                    </label>
                  ))}
                </div>
              </div>
            )}

            {/* Server Selection */}
            {currentSettings.include_servers && availableItems && availableItems.servers.length > 0 && (
              <div className="space-y-3 p-4 bg-slate-50 rounded-lg">
                <div className="flex items-center justify-between">
                  <p className="font-medium text-slate-900">Select Servers to Include</p>
                  <button
                    type="button"
                    className="text-sm text-primary-600 hover:text-primary-700"
                    onClick={() => {
                      const allServerIds = availableItems.servers.map(s => s.id);
                      const allSelected = currentSettings.selected_server_ids?.length === allServerIds.length;
                      setLocalSettings({
                        ...currentSettings,
                        selected_server_ids: allSelected ? [] : allServerIds,
                      });
                    }}
                  >
                    {currentSettings.selected_server_ids?.length === availableItems.servers.length ? 'Deselect All' : 'Select All'}
                  </button>
                </div>
                <p className="text-xs text-slate-500">
                  {currentSettings.selected_server_ids?.length === 0 || !currentSettings.selected_server_ids
                    ? 'All servers will be included (none specifically selected)'
                    : `${currentSettings.selected_server_ids.length} server${currentSettings.selected_server_ids.length !== 1 ? 's' : ''} selected`}
                </p>
                <div className="space-y-2 max-h-48 overflow-y-auto">
                  {availableItems.servers.map((server) => (
                    <label key={server.id} className="flex items-center gap-2">
                      <input
                        type="checkbox"
                        checked={currentSettings.selected_server_ids?.includes(server.id) ?? false}
                        onChange={(e) => {
                          const currentIds = currentSettings.selected_server_ids || [];
                          const newIds = e.target.checked
                            ? [...currentIds, server.id]
                            : currentIds.filter(id => id !== server.id);
                          setLocalSettings({ ...currentSettings, selected_server_ids: newIds });
                        }}
                        className="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                      <span className="text-sm text-slate-700">{server.name}</span>
                      <span className="text-xs text-slate-400">{server.host}</span>
                    </label>
                  ))}
                </div>
              </div>
            )}

            {/* Save Button */}
            <div className="pt-4">
              <Button
                onClick={() => localSettings && saveSettingsMutation.mutate(localSettings)}
                disabled={saveSettingsMutation.isPending || !localSettings}
              >
                {saveSettingsMutation.isPending ? 'Saving...' : 'Save Settings'}
              </Button>
            </div>
          </div>
        </Card>
      )}
    </Layout>
  );
}

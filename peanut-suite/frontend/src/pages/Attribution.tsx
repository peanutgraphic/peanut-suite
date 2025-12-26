import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  GitBranch,
  TrendingUp,
  DollarSign,
  Target,
  MousePointer,
  Calendar,
  Info,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Select, Badge, InfoTooltip, SampleDataBanner } from '../components/common';
import { BarChart, DoughnutChart } from '../components/common';
import { attributionApi } from '../api/endpoints';
import type { AttributionModel, ChannelPerformance } from '../types';
import {
  helpContent,
  pageDescriptions,
  sampleAttributionStats,
  sampleAttributionModels,
  sampleAttributionChannels,
  sampleAttributionComparison,
} from '../constants';

const modelColors: Record<string, string> = {
  first_touch: '#3b82f6',
  last_touch: '#10b981',
  linear: '#f59e0b',
  time_decay: '#8b5cf6',
  position_based: '#ec4899',
};

const channelColors: Record<string, string> = {
  'Direct': '#6366f1',
  'Organic Search': '#22c55e',
  'Paid Search': '#f59e0b',
  'Social': '#06b6d4',
  'Paid Social': '#ec4899',
  'Email': '#8b5cf6',
  'Referral': '#f97316',
  'Display': '#84cc16',
  'Affiliate': '#14b8a6',
};

export default function Attribution() {
  const [selectedModel, setSelectedModel] = useState('last_touch');
  const [dateRange, setDateRange] = useState('30d');
  const [showSampleData, setShowSampleData] = useState(true);

  // Calculate date range
  const getDateParams = () => {
    const to = new Date().toISOString().split('T')[0];
    let from: string;

    switch (dateRange) {
      case '7d':
        from = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        break;
      case '90d':
        from = new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        break;
      default:
        from = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    }

    return { date_from: from, date_to: to };
  };

  // Fetch models
  const { data: models } = useQuery({
    queryKey: ['attribution-models'],
    queryFn: attributionApi.getModels,
  });

  // Fetch stats
  const { data: stats } = useQuery({
    queryKey: ['attribution-stats'],
    queryFn: attributionApi.getStats,
  });

  // Fetch report
  const { data: report, isLoading: reportLoading } = useQuery({
    queryKey: ['attribution-report', selectedModel, dateRange],
    queryFn: () => attributionApi.getReport({
      model: selectedModel,
      ...getDateParams(),
    }),
  });

  // Fetch model comparison
  const { data: comparison, isLoading: comparisonLoading } = useQuery({
    queryKey: ['attribution-comparison', dateRange],
    queryFn: () => attributionApi.compareModels(getDateParams()),
  });

  // Determine if we should show sample data
  const hasNoRealData = !reportLoading && !comparisonLoading &&
    (!stats?.total_conversions || stats.total_conversions === 0) &&
    (!report?.channels || report.channels.length === 0);
  const displaySampleData = hasNoRealData && showSampleData;

  // Use sample data or real data
  const displayStats = displaySampleData ? sampleAttributionStats : stats;
  const displayModels = displaySampleData ? sampleAttributionModels : models;
  const displayReport = displaySampleData ? { channels: sampleAttributionChannels } : report;
  const displayComparison = displaySampleData ? sampleAttributionComparison : comparison;

  const modelOptions = (displayModels || []).map((m: AttributionModel) => ({
    value: m.id,
    label: m.name,
  }));

  const dateOptions = [
    { value: '7d', label: 'Last 7 days' },
    { value: '30d', label: 'Last 30 days' },
    { value: '90d', label: 'Last 90 days' },
  ];

  const currentModel = displayModels?.find((m: AttributionModel) => m.id === selectedModel);

  // Format channel data for charts
  const channelData = displayReport?.channels || [];
  const chartLabels = channelData.map((c: ChannelPerformance) => c.channel);
  const chartCredits = channelData.map((c: ChannelPerformance) => Math.round(c.attributed_credit * 100) / 100);
  const chartColors = chartLabels.map((label: string) => channelColors[label] || '#94a3b8');

  // Calculate comparison data
  const comparisonData = displayComparison?.comparison || {};

  const pageInfo = pageDescriptions.attribution;
  const pageHelpContent = { howTo: pageInfo.howTo, tips: pageInfo.tips, useCases: pageInfo.useCases };

  return (
    <Layout title={pageInfo.title} description={pageInfo.description} helpContent={pageHelpContent}>
      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Stats Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6">
        <Card className="p-4">
          <div className="flex items-center gap-2 mb-1">
            <Target className="w-4 h-4 text-slate-400" />
            <span className="text-sm text-slate-500">Conversions</span>
            <InfoTooltip content={helpContent.analytics.conversions} />
          </div>
          <div className="text-2xl font-bold">{displayStats?.total_conversions ?? 0}</div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-2 mb-1">
            <DollarSign className="w-4 h-4 text-green-500" />
            <span className="text-sm text-slate-500">Total Value</span>
          </div>
          <div className="text-2xl font-bold text-green-600">
            ${(displayStats?.total_value ?? 0).toLocaleString()}
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-2 mb-1">
            <Calendar className="w-4 h-4 text-blue-500" />
            <span className="text-sm text-slate-500">Today</span>
          </div>
          <div className="text-2xl font-bold text-blue-600">{displayStats?.today_conversions ?? 0}</div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-2 mb-1">
            <TrendingUp className="w-4 h-4 text-purple-500" />
            <span className="text-sm text-slate-500">This Month</span>
          </div>
          <div className="text-2xl font-bold text-purple-600">{displayStats?.month_conversions ?? 0}</div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-2 mb-1">
            <MousePointer className="w-4 h-4 text-amber-500" />
            <span className="text-sm text-slate-500">Total Touches</span>
            <InfoTooltip content={helpContent.attribution.touchpoints} />
          </div>
          <div className="text-2xl font-bold text-amber-600">{displayStats?.total_touches ?? 0}</div>
        </Card>
      </div>

      {/* Filters */}
      <Card className="p-4 mb-6">
        <div className="flex flex-wrap items-center gap-4">
          <div className="flex items-center gap-2">
            <GitBranch className="w-4 h-4 text-slate-400" />
            <span className="text-sm text-slate-500">Model:</span>
            <Select
              value={selectedModel}
              onChange={(e) => setSelectedModel(e.target.value)}
              options={modelOptions}
              className="w-48"
            />
          </div>
          <div className="flex items-center gap-2">
            <Calendar className="w-4 h-4 text-slate-400" />
            <span className="text-sm text-slate-500">Period:</span>
            <Select
              value={dateRange}
              onChange={(e) => setDateRange(e.target.value)}
              options={dateOptions}
              className="w-36"
            />
          </div>
          {currentModel && (
            <div className="flex items-center gap-2 text-sm text-slate-500 ml-auto">
              <Info className="w-4 h-4" />
              {currentModel.description}
            </div>
          )}
        </div>
      </Card>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {/* Channel Performance Chart */}
        <Card className="p-6">
          <h3 className="text-lg font-semibold mb-4">Channel Performance</h3>
          {reportLoading ? (
            <div className="h-64 flex items-center justify-center text-slate-400">
              Loading...
            </div>
          ) : channelData.length > 0 ? (
            <div className="h-64">
              <BarChart
                labels={chartLabels}
                datasets={[{
                  label: 'Attribution Credit',
                  data: chartCredits,
                  backgroundColor: chartColors,
                }]}
              />
            </div>
          ) : (
            <div className="h-64 flex items-center justify-center text-slate-400">
              No data available
            </div>
          )}
        </Card>

        {/* Channel Distribution */}
        <Card className="p-6">
          <h3 className="text-lg font-semibold mb-4">Channel Distribution</h3>
          {reportLoading ? (
            <div className="h-64 flex items-center justify-center text-slate-400">
              Loading...
            </div>
          ) : channelData.length > 0 ? (
            <div className="h-64">
              <DoughnutChart
                labels={chartLabels}
                data={chartCredits}
                colors={chartColors}
              />
            </div>
          ) : (
            <div className="h-64 flex items-center justify-center text-slate-400">
              No data available
            </div>
          )}
        </Card>
      </div>

      {/* Channel Details Table */}
      <Card className="mb-6">
        <div className="p-4 border-b border-slate-100">
          <h3 className="text-lg font-semibold">Channel Details</h3>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="bg-slate-50">
                <th className="px-4 py-3 text-left text-sm font-medium text-slate-500">Channel</th>
                <th className="px-4 py-3 text-right text-sm font-medium text-slate-500">Conversions</th>
                <th className="px-4 py-3 text-right text-sm font-medium text-slate-500">Attribution Credit</th>
                <th className="px-4 py-3 text-right text-sm font-medium text-slate-500">Value</th>
                <th className="px-4 py-3 text-right text-sm font-medium text-slate-500">Touches</th>
              </tr>
            </thead>
            <tbody>
              {channelData.length > 0 ? channelData.map((channel: ChannelPerformance, index: number) => (
                <tr key={index} className="border-t border-slate-100 hover:bg-slate-50">
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <div
                        className="w-3 h-3 rounded-full"
                        style={{ backgroundColor: channelColors[channel.channel] || '#94a3b8' }}
                      />
                      <span className="font-medium">{channel.channel}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-right">{channel.conversions}</td>
                  <td className="px-4 py-3 text-right">
                    <Badge variant="default">
                      {(channel.attributed_credit * 100).toFixed(1)}%
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-right text-green-600">
                    ${channel.attributed_value.toLocaleString()}
                  </td>
                  <td className="px-4 py-3 text-right text-slate-500">{channel.touches}</td>
                </tr>
              )) : (
                <tr>
                  <td colSpan={5} className="px-4 py-8 text-center text-slate-500">
                    No channel data available for this period
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </Card>

      {/* Model Comparison */}
      <Card>
        <div className="p-4 border-b border-slate-100">
          <h3 className="text-lg font-semibold">Model Comparison</h3>
          <p className="text-sm text-slate-500 mt-1">
            See how different attribution models distribute credit across channels
          </p>
        </div>
        <div className="p-4">
          {comparisonLoading ? (
            <div className="h-32 flex items-center justify-center text-slate-400">
              Loading comparison...
            </div>
          ) : Object.keys(comparisonData).length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="bg-slate-50">
                    <th className="px-3 py-2 text-left font-medium text-slate-500">Channel</th>
                    {Object.keys(comparisonData).map((model) => (
                      <th
                        key={model}
                        className="px-3 py-2 text-center font-medium"
                        style={{ color: modelColors[model] }}
                      >
                        {displayComparison?.models[model] || model}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {/* Get unique channels across all models */}
                  {(() => {
                    const channels = new Set<string>();
                    Object.values(comparisonData).forEach((modelData: ChannelPerformance[]) => {
                      modelData.forEach((c: ChannelPerformance) => channels.add(c.channel));
                    });
                    return Array.from(channels).map((channel) => (
                      <tr key={channel} className="border-t border-slate-100">
                        <td className="px-3 py-2 font-medium">{channel}</td>
                        {Object.keys(comparisonData).map((model) => {
                          const channelData = (comparisonData[model] as ChannelPerformance[])
                            .find((c: ChannelPerformance) => c.channel === channel);
                          const credit = channelData?.attributed_credit ?? 0;
                          return (
                            <td key={model} className="px-3 py-2 text-center">
                              <span
                                className="inline-block px-2 py-1 rounded text-xs font-medium"
                                style={{
                                  backgroundColor: `${modelColors[model]}15`,
                                  color: modelColors[model],
                                }}
                              >
                                {(credit * 100).toFixed(1)}%
                              </span>
                            </td>
                          );
                        })}
                      </tr>
                    ));
                  })()}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="h-32 flex items-center justify-center text-slate-400">
              No comparison data available
            </div>
          )}
        </div>
      </Card>
    </Layout>
  );
}

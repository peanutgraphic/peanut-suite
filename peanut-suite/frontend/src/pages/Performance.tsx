import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  Gauge,
  Smartphone,
  Monitor,
  RefreshCw,
  Plus,
  Trash2,
  AlertTriangle,
  CheckCircle,
  Settings,
  ChevronDown,
  ChevronUp,
  ExternalLink,
  X,
} from 'lucide-react';
import { clsx } from 'clsx';
import { Layout } from '../components/layout';
import { Card, Button, Input, Badge, Select, SampleDataBanner } from '../components/common';
import { performanceApi } from '../api/endpoints';
import { pageDescriptions, samplePerformanceScores, samplePerformanceAverages, samplePerformanceSettings } from '../constants';

interface ScoreCardProps {
  label: string;
  score: number;
  threshold?: { good: number; poor: number };
  suffix?: string;
  inverted?: boolean;
}

function ScoreCard({ label, score, threshold, suffix = '', inverted = false }: ScoreCardProps) {
  const getColor = () => {
    if (!threshold) {
      // Standard 0-100 score
      if (score >= 90) return 'text-green-600';
      if (score >= 50) return 'text-amber-600';
      return 'text-red-600';
    }
    // Metric with custom thresholds
    if (inverted) {
      // Lower is better (like CLS)
      if (score <= threshold.good) return 'text-green-600';
      if (score <= threshold.poor) return 'text-amber-600';
      return 'text-red-600';
    }
    // Lower is better (like LCP in ms)
    if (score <= threshold.good) return 'text-green-600';
    if (score <= threshold.poor) return 'text-amber-600';
    return 'text-red-600';
  };

  const getBgColor = () => {
    if (!threshold) {
      if (score >= 90) return 'bg-green-50';
      if (score >= 50) return 'bg-amber-50';
      return 'bg-red-50';
    }
    if (inverted) {
      if (score <= threshold.good) return 'bg-green-50';
      if (score <= threshold.poor) return 'bg-amber-50';
      return 'bg-red-50';
    }
    if (score <= threshold.good) return 'bg-green-50';
    if (score <= threshold.poor) return 'bg-amber-50';
    return 'bg-red-50';
  };

  return (
    <div className={clsx('rounded-lg p-4', getBgColor())}>
      <p className="text-sm text-slate-600 mb-1">{label}</p>
      <p className={clsx('text-2xl font-bold', getColor())}>
        {typeof score === 'number' ? (suffix === 's' ? (score / 1000).toFixed(2) : score.toFixed(suffix === '' ? 0 : 2)) : '—'}
        {suffix && <span className="text-base font-normal ml-1">{suffix}</span>}
      </p>
    </div>
  );
}

interface ScoreRingProps {
  score: number;
  size?: number;
  strokeWidth?: number;
  label?: string;
}

function ScoreRing({ score, size = 120, strokeWidth = 8, label }: ScoreRingProps) {
  const radius = (size - strokeWidth) / 2;
  const circumference = radius * 2 * Math.PI;
  const offset = circumference - (score / 100) * circumference;

  const getColor = () => {
    if (score >= 90) return '#22c55e'; // green-500
    if (score >= 50) return '#f59e0b'; // amber-500
    return '#ef4444'; // red-500
  };

  return (
    <div className="relative inline-flex items-center justify-center">
      <svg width={size} height={size} className="transform -rotate-90">
        <circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          stroke="#e2e8f0"
          strokeWidth={strokeWidth}
          fill="none"
        />
        <circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          stroke={getColor()}
          strokeWidth={strokeWidth}
          fill="none"
          strokeLinecap="round"
          strokeDasharray={circumference}
          strokeDashoffset={offset}
          className="transition-all duration-500"
        />
      </svg>
      <div className="absolute flex flex-col items-center">
        <span className="text-2xl font-bold text-slate-900">{score}</span>
        {label && <span className="text-xs text-slate-500">{label}</span>}
      </div>
    </div>
  );
}

export default function Performance() {
  const queryClient = useQueryClient();
  const [strategy, setStrategy] = useState<'mobile' | 'desktop'>('mobile');
  const [showSettings, setShowSettings] = useState(false);
  const [newUrl, setNewUrl] = useState('');
  const [expandedUrl, setExpandedUrl] = useState<string | null>(null);
  const [runningCheck, setRunningCheck] = useState<string | null>(null);
  const [showSampleData, setShowSampleData] = useState(true);

  const { data: settings, isLoading: settingsLoading } = useQuery({
    queryKey: ['performance-settings'],
    queryFn: performanceApi.getSettings,
  });

  const { data: scoresData, isLoading: scoresLoading } = useQuery({
    queryKey: ['performance-scores', strategy],
    queryFn: () => performanceApi.getScores(strategy),
  });

  // Determine if we should show sample data
  const realScores = scoresData?.scores || [];
  const hasNoRealData = !scoresLoading && realScores.length === 0;
  const displaySampleData = hasNoRealData && showSampleData;

  const updateSettingsMutation = useMutation({
    mutationFn: performanceApi.updateSettings,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['performance-settings'] });
    },
  });

  const addUrlMutation = useMutation({
    mutationFn: performanceApi.addUrl,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['performance-settings'] });
      setNewUrl('');
    },
  });

  const deleteUrlMutation = useMutation({
    mutationFn: performanceApi.deleteUrl,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['performance-settings'] });
    },
  });

  const runCheckMutation = useMutation({
    mutationFn: (url: string) => performanceApi.runCheck(url, strategy),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['performance-scores'] });
      setRunningCheck(null);
    },
    onError: () => {
      setRunningCheck(null);
    },
  });

  const handleRunCheck = (url: string) => {
    setRunningCheck(url);
    runCheckMutation.mutate(url);
  };

  const handleAddUrl = (e: React.FormEvent) => {
    e.preventDefault();
    if (newUrl) {
      addUrlMutation.mutate(newUrl);
    }
  };

  const pageInfo = pageDescriptions.performance || {
    title: 'Performance',
    description: 'Track Core Web Vitals and PageSpeed Insights scores',
    howTo: { title: 'Getting Started', steps: [] },
  };

  const scores = displaySampleData ? samplePerformanceScores : realScores;
  const averages = displaySampleData ? samplePerformanceAverages : scoresData?.averages;
  const displaySettings = displaySampleData ? samplePerformanceSettings : settings;

  return (
    <Layout
      title={pageInfo.title}
      description={pageInfo.description}
      helpContent={{
        howTo: pageInfo.howTo,
        tips: pageInfo.tips,
        useCases: pageInfo.useCases,
      }}
      pageGuideId="performance"
    >
      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-3">
          <div className="flex rounded-lg border border-slate-200 overflow-hidden">
            <button
              onClick={() => setStrategy('mobile')}
              className={clsx(
                'flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors',
                strategy === 'mobile'
                  ? 'bg-primary-50 text-primary-700'
                  : 'text-slate-600 hover:bg-slate-50'
              )}
            >
              <Smartphone className="w-4 h-4" />
              Mobile
            </button>
            <button
              onClick={() => setStrategy('desktop')}
              className={clsx(
                'flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors',
                strategy === 'desktop'
                  ? 'bg-primary-50 text-primary-700'
                  : 'text-slate-600 hover:bg-slate-50'
              )}
            >
              <Monitor className="w-4 h-4" />
              Desktop
            </button>
          </div>
        </div>
        <Button
          variant="outline"
          icon={<Settings className="w-4 h-4" />}
          onClick={() => setShowSettings(!showSettings)}
        >
          Settings
        </Button>
      </div>

      {/* Settings Panel */}
      {showSettings && (
        <Card className="mb-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold text-slate-900">Performance Settings</h3>
            <button onClick={() => setShowSettings(false)} className="text-slate-400 hover:text-slate-600">
              <X className="w-5 h-5" />
            </button>
          </div>

          {settingsLoading ? (
            <div className="animate-pulse space-y-4">
              <div className="h-10 bg-slate-100 rounded" />
              <div className="h-10 bg-slate-100 rounded" />
            </div>
          ) : (
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">
                  Google PageSpeed API Key (optional)
                </label>
                <Input
                  type="password"
                  placeholder={displaySettings?.api_key_set ? 'API key is set' : 'Enter API key for higher rate limits'}
                  onChange={(e) => {
                    if (!displaySampleData && e.target.value && !e.target.value.startsWith('••••')) {
                      updateSettingsMutation.mutate({ api_key: e.target.value });
                    }
                  }}
                  disabled={displaySampleData}
                />
                <p className="text-xs text-slate-500 mt-1">
                  Without an API key, requests are limited. Get a free key from{' '}
                  <a
                    href="https://developers.google.com/speed/docs/insights/v5/get-started"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-primary-600 hover:underline"
                  >
                    Google Cloud Console
                  </a>
                </p>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Auto-check Frequency
                  </label>
                  <Select
                    value={displaySettings?.auto_check_enabled ? displaySettings.check_frequency : 'disabled'}
                    onChange={(e) => {
                      if (!displaySampleData) {
                        if (e.target.value === 'disabled') {
                          updateSettingsMutation.mutate({ auto_check_enabled: false });
                        } else {
                          updateSettingsMutation.mutate({
                            auto_check_enabled: true,
                            check_frequency: e.target.value,
                          });
                        }
                      }
                    }}
                    options={[
                      { value: 'disabled', label: 'Disabled' },
                      { value: 'daily', label: 'Daily' },
                      { value: 'weekly', label: 'Weekly' },
                    ]}
                    disabled={displaySampleData}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Alert Threshold
                  </label>
                  <div className="flex gap-2">
                    <Input
                      type="number"
                      min={0}
                      max={100}
                      value={displaySettings?.alert_threshold || 50}
                      onChange={(e) => {
                        if (!displaySampleData) {
                          updateSettingsMutation.mutate({ alert_threshold: parseInt(e.target.value) });
                        }
                      }}
                      className="w-20"
                      disabled={displaySampleData}
                    />
                    <span className="text-sm text-slate-500 self-center">
                      Alert when score drops below this
                    </span>
                  </div>
                </div>
              </div>

              {/* Tracked URLs */}
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">
                  Tracked URLs
                </label>
                <div className="space-y-2 mb-3">
                  {displaySettings?.urls?.map((url, index) => (
                    <div
                      key={url}
                      className="flex items-center justify-between bg-slate-50 px-3 py-2 rounded-lg"
                    >
                      <span className="text-sm text-slate-700 truncate">{url}</span>
                      {!displaySampleData && (
                        <button
                          onClick={() => deleteUrlMutation.mutate(index)}
                          className="text-slate-400 hover:text-red-500"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      )}
                    </div>
                  ))}
                </div>
                {!displaySampleData && (
                  <form onSubmit={handleAddUrl} className="flex gap-2">
                    <Input
                      placeholder="https://example.com/page"
                      value={newUrl}
                      onChange={(e) => setNewUrl(e.target.value)}
                      className="flex-1"
                    />
                    <Button type="submit" icon={<Plus className="w-4 h-4" />}>
                      Add
                    </Button>
                  </form>
                )}
              </div>
            </div>
          )}
        </Card>
      )}

      {/* Average Scores Overview */}
      {averages && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <Card className="!p-4 text-center">
            <ScoreRing score={Math.round(averages.overall)} label="Overall" />
          </Card>
          <Card className="!p-4 text-center">
            <ScoreRing score={Math.round(averages.performance)} label="Performance" />
          </Card>
          <Card className="!p-4 text-center">
            <ScoreRing score={Math.round(averages.accessibility)} label="Accessibility" />
          </Card>
          <Card className="!p-4 text-center">
            <ScoreRing score={Math.round(averages.seo)} label="SEO" />
          </Card>
        </div>
      )}

      {/* Core Web Vitals Summary */}
      {averages && (
        <Card className="mb-6">
          <h3 className="font-semibold text-slate-900 mb-4">Core Web Vitals (Averages)</h3>
          <div className="grid grid-cols-3 gap-4">
            <ScoreCard
              label="LCP (Largest Contentful Paint)"
              score={averages.lcp}
              threshold={{ good: 2500, poor: 4000 }}
              suffix="s"
            />
            <ScoreCard
              label="FID (First Input Delay)"
              score={averages.fid}
              threshold={{ good: 100, poor: 300 }}
              suffix="ms"
            />
            <ScoreCard
              label="CLS (Cumulative Layout Shift)"
              score={averages.cls}
              threshold={{ good: 0.1, poor: 0.25 }}
              inverted
            />
          </div>
        </Card>
      )}

      {/* Individual URL Scores */}
      <Card>
        <h3 className="font-semibold text-slate-900 mb-4">Page Scores</h3>

        {scoresLoading ? (
          <div className="animate-pulse space-y-4">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-20 bg-slate-100 rounded-lg" />
            ))}
          </div>
        ) : scores.length === 0 ? (
          <div className="text-center py-12">
            <Gauge className="w-12 h-12 mx-auto mb-3 text-slate-300" />
            <p className="text-slate-500 mb-4">No performance data yet</p>
            <p className="text-sm text-slate-400 mb-4">
              Add URLs to track and run a check to get started
            </p>
            <Button onClick={() => setShowSettings(true)}>Configure URLs</Button>
          </div>
        ) : (
          <div className="space-y-4">
            {scores.map((score) => (
              <div
                key={score.id}
                className="border border-slate-200 rounded-lg overflow-hidden"
              >
                {/* URL Header */}
                <div
                  className="flex items-center justify-between p-4 bg-slate-50 cursor-pointer"
                  onClick={() => setExpandedUrl(expandedUrl === score.url ? null : score.url)}
                >
                  <div className="flex items-center gap-4">
                    <ScoreRing score={score.overall_score} size={48} strokeWidth={4} />
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="font-medium text-slate-900 truncate max-w-md">
                          {score.url}
                        </span>
                        <a
                          href={score.url}
                          target="_blank"
                          rel="noopener noreferrer"
                          onClick={(e) => e.stopPropagation()}
                          className="text-slate-400 hover:text-slate-600"
                        >
                          <ExternalLink className="w-4 h-4" />
                        </a>
                      </div>
                      <p className="text-sm text-slate-500">
                        Last checked: {new Date(score.checked_at).toLocaleString()}
                      </p>
                    </div>
                  </div>

                  <div className="flex items-center gap-3">
                    <Button
                      variant="outline"
                      size="sm"
                      icon={<RefreshCw className={clsx('w-4 h-4', runningCheck === score.url && 'animate-spin')} />}
                      onClick={(e) => {
                        e.stopPropagation();
                        handleRunCheck(score.url);
                      }}
                      disabled={runningCheck === score.url}
                    >
                      {runningCheck === score.url ? 'Checking...' : 'Re-check'}
                    </Button>
                    {expandedUrl === score.url ? (
                      <ChevronUp className="w-5 h-5 text-slate-400" />
                    ) : (
                      <ChevronDown className="w-5 h-5 text-slate-400" />
                    )}
                  </div>
                </div>

                {/* Expanded Details */}
                {expandedUrl === score.url && (
                  <div className="p-4 border-t border-slate-200">
                    {/* Score Categories */}
                    <div className="grid grid-cols-4 gap-4 mb-6">
                      <div className="text-center">
                        <p className="text-sm text-slate-500 mb-1">Performance</p>
                        <p
                          className={clsx(
                            'text-xl font-bold',
                            score.performance_score >= 90
                              ? 'text-green-600'
                              : score.performance_score >= 50
                              ? 'text-amber-600'
                              : 'text-red-600'
                          )}
                        >
                          {score.performance_score}
                        </p>
                      </div>
                      <div className="text-center">
                        <p className="text-sm text-slate-500 mb-1">Accessibility</p>
                        <p
                          className={clsx(
                            'text-xl font-bold',
                            score.accessibility_score >= 90
                              ? 'text-green-600'
                              : score.accessibility_score >= 50
                              ? 'text-amber-600'
                              : 'text-red-600'
                          )}
                        >
                          {score.accessibility_score}
                        </p>
                      </div>
                      <div className="text-center">
                        <p className="text-sm text-slate-500 mb-1">Best Practices</p>
                        <p
                          className={clsx(
                            'text-xl font-bold',
                            score.best_practices_score >= 90
                              ? 'text-green-600'
                              : score.best_practices_score >= 50
                              ? 'text-amber-600'
                              : 'text-red-600'
                          )}
                        >
                          {score.best_practices_score}
                        </p>
                      </div>
                      <div className="text-center">
                        <p className="text-sm text-slate-500 mb-1">SEO</p>
                        <p
                          className={clsx(
                            'text-xl font-bold',
                            score.seo_score >= 90
                              ? 'text-green-600'
                              : score.seo_score >= 50
                              ? 'text-amber-600'
                              : 'text-red-600'
                          )}
                        >
                          {score.seo_score}
                        </p>
                      </div>
                    </div>

                    {/* Core Web Vitals */}
                    <h4 className="font-medium text-slate-900 mb-3">Core Web Vitals</h4>
                    <div className="grid grid-cols-3 md:grid-cols-6 gap-4 mb-6">
                      <ScoreCard
                        label="LCP"
                        score={score.lcp_ms}
                        threshold={{ good: 2500, poor: 4000 }}
                        suffix="s"
                      />
                      <ScoreCard
                        label="FID"
                        score={score.fid_ms || score.inp_ms}
                        threshold={{ good: 100, poor: 300 }}
                        suffix="ms"
                      />
                      <ScoreCard
                        label="CLS"
                        score={score.cls}
                        threshold={{ good: 0.1, poor: 0.25 }}
                        inverted
                      />
                      <ScoreCard
                        label="FCP"
                        score={score.fcp_ms}
                        threshold={{ good: 1800, poor: 3000 }}
                        suffix="s"
                      />
                      <ScoreCard
                        label="TTFB"
                        score={score.ttfb_ms}
                        threshold={{ good: 800, poor: 1800 }}
                        suffix="ms"
                      />
                      <ScoreCard
                        label="TBT"
                        score={score.tbt_ms}
                        threshold={{ good: 200, poor: 600 }}
                        suffix="ms"
                      />
                    </div>

                    {/* Opportunities */}
                    {score.opportunities?.length > 0 && (
                      <div className="mb-4">
                        <h4 className="font-medium text-slate-900 mb-3">
                          Opportunities for Improvement
                        </h4>
                        <div className="space-y-2">
                          {score.opportunities.map((opp) => (
                            <div
                              key={opp.id}
                              className="flex items-center justify-between bg-amber-50 px-4 py-3 rounded-lg"
                            >
                              <div>
                                <p className="font-medium text-slate-900">{opp.title}</p>
                                <p className="text-sm text-slate-600">{opp.description}</p>
                              </div>
                              {opp.savings_ms > 0 && (
                                <Badge variant="warning">
                                  Save {(opp.savings_ms / 1000).toFixed(1)}s
                                </Badge>
                              )}
                            </div>
                          ))}
                        </div>
                      </div>
                    )}

                    {/* Diagnostics */}
                    {score.diagnostics?.length > 0 && (
                      <div>
                        <h4 className="font-medium text-slate-900 mb-3">Diagnostics</h4>
                        <div className="space-y-2">
                          {score.diagnostics.map((diag) => (
                            <div
                              key={diag.id}
                              className="flex items-center gap-3 bg-slate-50 px-4 py-3 rounded-lg"
                            >
                              {diag.score !== null && diag.score < 0.5 ? (
                                <AlertTriangle className="w-5 h-5 text-amber-500 flex-shrink-0" />
                              ) : (
                                <CheckCircle className="w-5 h-5 text-green-500 flex-shrink-0" />
                              )}
                              <div>
                                <p className="font-medium text-slate-900">{diag.title}</p>
                                <p className="text-sm text-slate-600">{diag.description}</p>
                              </div>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </Card>
    </Layout>
  );
}

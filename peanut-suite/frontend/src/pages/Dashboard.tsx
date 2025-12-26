import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import {
  Tag,
  Link2,
  Users,
  MessageSquare,
  ArrowRight,
  ExternalLink,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, StatCard, Skeleton, LineChart, BarChart, DoughnutChart, SampleDataBanner } from '../components/common';
import { utmApi, linksApi, contactsApi, popupsApi, dashboardApi } from '../api/endpoints';
import { helpContent, pageDescriptions, sampleUTMs, sampleLinks, sampleContacts, sampleDashboardStats } from '../constants';

export default function Dashboard() {
  const [period, setPeriod] = useState<'7d' | '30d' | '90d'>('7d');
  const [showSampleData, setShowSampleData] = useState(true);

  const { data: utmStats, isLoading: loadingUTM } = useQuery({
    queryKey: ['utm-stats'],
    queryFn: () => utmApi.getAll({ per_page: 5 }),
  });

  const { data: linkStats, isLoading: loadingLinks } = useQuery({
    queryKey: ['link-stats'],
    queryFn: () => linksApi.getAll({ per_page: 5 }),
  });

  const { data: contactStats, isLoading: loadingContacts } = useQuery({
    queryKey: ['contact-stats'],
    queryFn: () => contactsApi.getAll({ per_page: 5 }),
  });

  const { data: popupStats, isLoading: loadingPopups } = useQuery({
    queryKey: ['popup-stats'],
    queryFn: () => popupsApi.getAll({ per_page: 1 }),
  });

  const { data: timeline } = useQuery({
    queryKey: ['dashboard-timeline', period],
    queryFn: () => dashboardApi.getTimeline(period),
  });

  const isLoading = loadingUTM || loadingLinks || loadingContacts || loadingPopups;

  // Determine if we should show sample data
  const hasNoRealData = !isLoading &&
    (!utmStats?.total || utmStats.total === 0) &&
    (!linkStats?.total || linkStats.total === 0) &&
    (!contactStats?.total || contactStats.total === 0) &&
    (!popupStats?.total || popupStats.total === 0);
  const displaySampleData = hasNoRealData && showSampleData;

  // Generate chart data from timeline or use sample data
  const chartData = useMemo(() => {
    if (timeline && timeline.length > 0) {
      return {
        labels: timeline.map(d => new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
        utmClicks: timeline.map(d => d.utm_clicks),
        linkClicks: timeline.map(d => d.link_clicks),
        contacts: timeline.map(d => d.contacts),
        conversions: timeline.map(d => d.conversions),
      };
    }
    // Sample data for demo
    const days = period === '7d' ? 7 : period === '30d' ? 30 : 90;
    const labels = Array.from({ length: days }, (_, i) => {
      const date = new Date();
      date.setDate(date.getDate() - (days - 1 - i));
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    return {
      labels: labels.filter((_, i) => i % (days > 30 ? 7 : days > 7 ? 3 : 1) === 0),
      utmClicks: Array.from({ length: Math.ceil(days / (days > 30 ? 7 : days > 7 ? 3 : 1)) }, () => Math.floor(Math.random() * 100) + 20),
      linkClicks: Array.from({ length: Math.ceil(days / (days > 30 ? 7 : days > 7 ? 3 : 1)) }, () => Math.floor(Math.random() * 80) + 10),
      contacts: Array.from({ length: Math.ceil(days / (days > 30 ? 7 : days > 7 ? 3 : 1)) }, () => Math.floor(Math.random() * 15) + 5),
      conversions: Array.from({ length: Math.ceil(days / (days > 30 ? 7 : days > 7 ? 3 : 1)) }, () => Math.floor(Math.random() * 10) + 2),
    };
  }, [timeline, period]);

  // Source breakdown for pie chart (sample data)
  const sourceData = useMemo(() => {
    if (utmStats?.data && utmStats.data.length > 0) {
      const sources: Record<string, number> = {};
      utmStats.data.forEach(utm => {
        sources[utm.utm_source] = (sources[utm.utm_source] || 0) + utm.click_count;
      });
      return {
        labels: Object.keys(sources),
        data: Object.values(sources),
      };
    }
    return {
      labels: ['Google', 'Facebook', 'Email', 'Direct'],
      data: [45, 25, 20, 10],
    };
  }, [utmStats]);

  const pageInfo = pageDescriptions.dashboard;
  const pageHelpContent = { howTo: pageInfo.howTo, tips: pageInfo.tips, useCases: pageInfo.useCases };

  return (
    <Layout title={pageInfo.title} description={pageInfo.description} helpContent={pageHelpContent}>
      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Stats Grid */}
      <div data-tour="dashboard-stats" className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {isLoading ? (
          <>
            <Skeleton className="h-32" />
            <Skeleton className="h-32" />
            <Skeleton className="h-32" />
            <Skeleton className="h-32" />
          </>
        ) : (
          <>
            <StatCard
              title="UTM Codes"
              value={displaySampleData ? sampleDashboardStats.utmTotal : (utmStats?.total || 0)}
              icon={<Tag className="w-5 h-5" />}
              tooltip={helpContent.dashboard.utmCodes}
              change={{ value: 12, type: 'increase' }}
            />
            <StatCard
              title="Short Links"
              value={displaySampleData ? sampleDashboardStats.linksTotal : (linkStats?.total || 0)}
              icon={<Link2 className="w-5 h-5" />}
              tooltip={helpContent.dashboard.shortLinks}
              change={{ value: 8, type: 'increase' }}
            />
            <StatCard
              title="Contacts"
              value={displaySampleData ? sampleDashboardStats.contactsTotal : (contactStats?.total || 0)}
              icon={<Users className="w-5 h-5" />}
              tooltip={helpContent.dashboard.contacts}
              change={{ value: 24, type: 'increase' }}
            />
            <StatCard
              title="Popups"
              value={displaySampleData ? sampleDashboardStats.popupsTotal : (popupStats?.total || 0)}
              icon={<MessageSquare className="w-5 h-5" />}
              tooltip={helpContent.dashboard.popups}
              change={{ value: 5, type: 'increase' }}
            />
          </>
        )}
      </div>

      {/* Recent Activity Section */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Recent Links */}
        <Card>
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <Link2 className="w-4 h-4 text-green-600" />
              </div>
              <h3 className="font-semibold text-slate-900">Recent Links</h3>
            </div>
            <Link
              to="/links"
              className="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1"
            >
              View all <ArrowRight className="w-3 h-3" />
            </Link>
          </div>
          {loadingLinks ? (
            <div className="space-y-2">
              <Skeleton className="h-10" />
              <Skeleton className="h-10" />
              <Skeleton className="h-10" />
            </div>
          ) : (
            <ul className="space-y-2">
              {(displaySampleData ? sampleLinks : linkStats?.data || []).slice(0, 4).map((link) => (
                <li key={link.id} className="group">
                  <div className="flex items-center justify-between p-2 -mx-2 rounded-lg hover:bg-slate-50 transition-colors">
                    <div className="min-w-0 flex-1">
                      <p className="font-medium text-slate-900 text-sm truncate">{link.title || link.slug}</p>
                      <p className="text-xs text-slate-400 truncate">{link.short_url}</p>
                    </div>
                    <div className="flex items-center gap-2 ml-2">
                      <span className="text-xs text-slate-500">{link.click_count} clicks</span>
                      <a href={link.short_url} target="_blank" rel="noopener noreferrer" className="opacity-0 group-hover:opacity-100 transition-opacity">
                        <ExternalLink className="w-3.5 h-3.5 text-slate-400 hover:text-primary-600" />
                      </a>
                    </div>
                  </div>
                </li>
              ))}
              {!(displaySampleData ? sampleLinks : linkStats?.data)?.length && (
                <li className="text-sm text-slate-400 py-4 text-center">No links yet</li>
              )}
            </ul>
          )}
        </Card>

        {/* Recent UTM Codes */}
        <Card>
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                <Tag className="w-4 h-4 text-indigo-600" />
              </div>
              <h3 className="font-semibold text-slate-900">Recent UTMs</h3>
            </div>
            <Link
              to="/utm/library"
              className="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1"
            >
              View all <ArrowRight className="w-3 h-3" />
            </Link>
          </div>
          {loadingUTM ? (
            <div className="space-y-2">
              <Skeleton className="h-10" />
              <Skeleton className="h-10" />
              <Skeleton className="h-10" />
            </div>
          ) : (
            <ul className="space-y-2">
              {(displaySampleData ? sampleUTMs : utmStats?.data || []).slice(0, 4).map((utm) => (
                <li key={utm.id} className="group">
                  <div className="flex items-center justify-between p-2 -mx-2 rounded-lg hover:bg-slate-50 transition-colors">
                    <div className="min-w-0 flex-1">
                      <p className="font-medium text-slate-900 text-sm truncate">{utm.utm_campaign}</p>
                      <p className="text-xs text-slate-400">{utm.utm_source} / {utm.utm_medium}</p>
                    </div>
                    <span className="text-xs text-slate-500 ml-2">{utm.click_count} clicks</span>
                  </div>
                </li>
              ))}
              {!(displaySampleData ? sampleUTMs : utmStats?.data)?.length && (
                <li className="text-sm text-slate-400 py-4 text-center">No UTMs yet</li>
              )}
            </ul>
          )}
        </Card>

        {/* Recent Contacts */}
        <Card>
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                <Users className="w-4 h-4 text-amber-600" />
              </div>
              <h3 className="font-semibold text-slate-900">Recent Contacts</h3>
            </div>
            <Link
              to="/contacts"
              className="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1"
            >
              View all <ArrowRight className="w-3 h-3" />
            </Link>
          </div>
          {loadingContacts ? (
            <div className="space-y-2">
              <Skeleton className="h-10" />
              <Skeleton className="h-10" />
              <Skeleton className="h-10" />
            </div>
          ) : (
            <ul className="space-y-2">
              {(displaySampleData ? sampleContacts : contactStats?.data || []).slice(0, 4).map((contact) => (
                <li key={contact.id} className="group">
                  <div className="flex items-center justify-between p-2 -mx-2 rounded-lg hover:bg-slate-50 transition-colors">
                    <div className="min-w-0 flex-1">
                      <p className="font-medium text-slate-900 text-sm truncate">
                        {contact.first_name && contact.last_name
                          ? `${contact.first_name} ${contact.last_name}`
                          : contact.email}
                      </p>
                      <p className="text-xs text-slate-400 truncate">{contact.company || contact.source || 'No source'}</p>
                    </div>
                    <span className={`text-xs px-2 py-0.5 rounded-full ml-2 ${
                      contact.status === 'customer' ? 'bg-green-100 text-green-700' :
                      contact.status === 'qualified' ? 'bg-blue-100 text-blue-700' :
                      contact.status === 'contacted' ? 'bg-amber-100 text-amber-700' :
                      'bg-slate-100 text-slate-600'
                    }`}>{contact.status}</span>
                  </div>
                </li>
              ))}
              {!(displaySampleData ? sampleContacts : contactStats?.data)?.length && (
                <li className="text-sm text-slate-400 py-4 text-center">No contacts yet</li>
              )}
            </ul>
          )}
        </Card>
      </div>

      {/* Quick Actions */}
      <div className="mt-6">
        <Card data-tour="quick-actions">
          <h3 className="text-lg font-semibold text-slate-900 mb-4">Quick Actions</h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
            <QuickActionButton
              href="/utm"
              icon={<Tag className="w-5 h-5" />}
              title="Create UTM Code"
              description="Generate tracked URLs"
            />
            <QuickActionButton
              href="/links"
              icon={<Link2 className="w-5 h-5" />}
              title="Shorten Link"
              description="Create branded short links"
            />
            <QuickActionButton
              href="/popups"
              icon={<MessageSquare className="w-5 h-5" />}
              title="Create Popup"
              description="Build conversion popups"
            />
          </div>
        </Card>
      </div>

      {/* Performance Overview */}
      <div className="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card className="lg:col-span-2">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-lg font-semibold text-slate-900">Performance Overview</h3>
            <select
              className="text-sm border border-slate-200 rounded-lg px-3 py-1.5"
              value={period}
              onChange={(e) => setPeriod(e.target.value as '7d' | '30d' | '90d')}
            >
              <option value="7d">Last 7 days</option>
              <option value="30d">Last 30 days</option>
              <option value="90d">Last 90 days</option>
            </select>
          </div>
          <LineChart
            labels={chartData.labels}
            datasets={[
              { label: 'UTM Clicks', data: chartData.utmClicks, borderColor: '#6366f1' },
              { label: 'Link Clicks', data: chartData.linkClicks, borderColor: '#22c55e' },
            ]}
            height={280}
          />
        </Card>

        <Card>
          <h3 className="text-lg font-semibold text-slate-900 mb-6">Traffic Sources</h3>
          <DoughnutChart
            labels={sourceData.labels}
            data={sourceData.data}
            height={240}
          />
        </Card>
      </div>

      {/* Conversions Chart */}
      <div className="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <h3 className="text-lg font-semibold text-slate-900 mb-6">New Contacts</h3>
          <BarChart
            labels={chartData.labels}
            datasets={[
              { label: 'Contacts', data: chartData.contacts, backgroundColor: '#6366f1' },
            ]}
            height={200}
            showLegend={false}
          />
        </Card>

        <Card>
          <h3 className="text-lg font-semibold text-slate-900 mb-6">Popup Conversions</h3>
          <BarChart
            labels={chartData.labels}
            datasets={[
              { label: 'Conversions', data: chartData.conversions, backgroundColor: '#22c55e' },
            ]}
            height={200}
            showLegend={false}
          />
        </Card>
      </div>
    </Layout>
  );
}

interface QuickActionButtonProps {
  href: string;
  icon: React.ReactNode;
  title: string;
  description: string;
}

function QuickActionButton({ href, icon, title, description }: QuickActionButtonProps) {
  return (
    <Link
      to={href}
      className="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-primary-300 hover:bg-primary-50/50 transition-colors"
    >
      <div className="w-10 h-10 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center">
        {icon}
      </div>
      <div>
        <p className="font-medium text-slate-900">{title}</p>
        <p className="text-xs text-slate-500">{description}</p>
      </div>
    </Link>
  );
}

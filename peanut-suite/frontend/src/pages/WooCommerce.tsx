import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { createColumnHelper } from '@tanstack/react-table';
import {
  ShoppingCart,
  DollarSign,
  TrendingUp,
  Users,
  Package,
  Download,
  BarChart2,
  PieChart,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Table, Pagination, Badge, Select, SampleDataBanner, useToast } from '../components/common';
import { woocommerceApi } from '../api/endpoints';
import { pageDescriptions, sampleWooCommerceStats, sampleWooCommerceReport, sampleWooCommerceOrders } from '../constants';
import { exportToCSV } from '../utils';

interface AttributedOrder {
  id: number;
  order_id: number;
  customer_email: string;
  order_total: number;
  utm_source: string;
  utm_medium: string;
  utm_campaign: string;
  created_at: string;
}

const columnHelper = createColumnHelper<AttributedOrder>();

// Export columns for WooCommerce orders
const ordersExportColumns = [
  { key: 'order_id' as const, header: 'Order ID' },
  { key: 'customer_email' as const, header: 'Customer Email' },
  { key: 'order_total' as const, header: 'Total' },
  { key: 'utm_source' as const, header: 'Source' },
  { key: 'utm_medium' as const, header: 'Medium' },
  { key: 'utm_campaign' as const, header: 'Campaign' },
  { key: 'created_at' as const, header: 'Date' },
];

export default function WooCommerce() {
  const toast = useToast();
  const [period, setPeriod] = useState(30);
  const [groupBy, setGroupBy] = useState<'source' | 'medium' | 'campaign'>('source');
  const [page, setPage] = useState(1);
  const [showSampleData, setShowSampleData] = useState(true);

  const { data: revenueStats, isLoading: revenueLoading } = useQuery({
    queryKey: ['woocommerce-revenue', period],
    queryFn: () => woocommerceApi.getRevenueStats(period),
  });

  const { data: attributionReport, isLoading: reportLoading } = useQuery({
    queryKey: ['woocommerce-attribution', period, groupBy],
    queryFn: () => woocommerceApi.getAttributionReport({ days: period, group_by: groupBy }),
  });

  const { data: ordersData, isLoading: ordersLoading } = useQuery({
    queryKey: ['woocommerce-orders', page],
    queryFn: () => woocommerceApi.getAttributedOrders({ page, per_page: 10 }),
  });

  // Determine if we should show sample data
  const hasNoRealData = !revenueLoading && !reportLoading && !ordersLoading &&
    (!revenueStats?.total_orders || revenueStats.total_orders === 0) &&
    (!ordersData?.orders || ordersData.orders.length === 0);
  const displaySampleData = hasNoRealData && showSampleData;

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(amount);
  };

  const handleExportReport = () => {
    if (displayOrders.length > 0) {
      exportToCSV(displayOrders, ordersExportColumns, `woocommerce-report-${period}days`);
      toast.success('Report exported successfully');
    } else {
      toast.error('No data to export');
    }
  };

  const columns = [
    columnHelper.accessor('order_id', {
      header: 'Order',
      cell: (info) => (
        <span className="font-medium text-slate-900">#{info.getValue()}</span>
      ),
    }),
    columnHelper.accessor('customer_email', {
      header: 'Customer',
      cell: (info) => (
        <span className="text-sm text-slate-600">{info.getValue()}</span>
      ),
    }),
    columnHelper.accessor('order_total', {
      header: 'Total',
      cell: (info) => (
        <span className="font-medium text-slate-900">{formatCurrency(info.getValue())}</span>
      ),
    }),
    columnHelper.accessor('utm_source', {
      header: 'Source',
      cell: (info) => (
        <Badge variant="info" size="sm">{info.getValue() || 'direct'}</Badge>
      ),
    }),
    columnHelper.accessor('utm_campaign', {
      header: 'Campaign',
      cell: (info) => (
        <span className="text-sm text-slate-600">{info.getValue() || '-'}</span>
      ),
    }),
    columnHelper.accessor('created_at', {
      header: 'Date',
      cell: (info) => (
        <span className="text-sm text-slate-500">
          {new Date(info.getValue()).toLocaleDateString()}
        </span>
      ),
    }),
  ];

  const realStats = revenueStats || {
    total_revenue: 0,
    total_orders: 0,
    attributed_orders: 0,
    attribution_rate: 0,
    by_source: [],
    by_campaign: [],
    daily: [],
  };
  const stats = displaySampleData ? sampleWooCommerceStats : realStats;
  const displayReport = displaySampleData ? sampleWooCommerceReport : (attributionReport?.report || []);
  const displayOrders = displaySampleData ? sampleWooCommerceOrders as AttributedOrder[] : (ordersData?.orders || []);

  const pageInfo = pageDescriptions.woocommerce || {
    title: 'Revenue Attribution',
    description: 'Track WooCommerce revenue from your marketing campaigns',
    howTo: ['View revenue by source/campaign', 'Track attributed orders'],
    tips: ['Use UTM parameters on all marketing links', 'Compare channel performance'],
    useCases: ['Measure marketing ROI', 'Identify top-performing campaigns'],
  };

  return (
    <Layout title={pageInfo.title} description={pageInfo.description} helpContent={{ howTo: pageInfo.howTo, tips: pageInfo.tips, useCases: pageInfo.useCases }} pageGuideId="woocommerce">
      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-4">
          <Select
            options={[
              { value: '7', label: 'Last 7 days' },
              { value: '30', label: 'Last 30 days' },
              { value: '90', label: 'Last 90 days' },
              { value: '365', label: 'Last year' },
            ]}
            value={period.toString()}
            onChange={(e) => setPeriod(parseInt(e.target.value))}
            fullWidth={false}
          />
        </div>
        <Button
          variant="outline"
          icon={<Download className="w-4 h-4" />}
          onClick={handleExportReport}
          disabled={displayOrders.length === 0}
        >
          Export Report
        </Button>
      </div>

      {/* Stats Overview */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <Card className="!p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-slate-900">
                {formatCurrency(stats.total_revenue)}
              </p>
              <p className="text-sm text-slate-500">Total Revenue</p>
            </div>
            <DollarSign className="w-8 h-8 text-green-500" />
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-slate-900">{stats.total_orders}</p>
              <p className="text-sm text-slate-500">Total Orders</p>
            </div>
            <ShoppingCart className="w-8 h-8 text-primary-500" />
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-slate-900">{stats.attributed_orders}</p>
              <p className="text-sm text-slate-500">Attributed Orders</p>
            </div>
            <Package className="w-8 h-8 text-blue-500" />
          </div>
        </Card>
        <Card className="!p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-slate-900">
                {Math.round(stats.attribution_rate * 100)}%
              </p>
              <p className="text-sm text-slate-500">Attribution Rate</p>
            </div>
            <TrendingUp className="w-8 h-8 text-emerald-500" />
          </div>
        </Card>
      </div>

      {/* Revenue by Channel */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {/* By Source */}
        <Card>
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold text-slate-900">Revenue by Source</h3>
            <PieChart className="w-5 h-5 text-slate-400" />
          </div>
          {revenueLoading ? (
            <div className="animate-pulse space-y-2">
              {[1, 2, 3, 4].map((i) => (
                <div key={i} className="h-10 bg-slate-100 rounded-lg" />
              ))}
            </div>
          ) : !stats.by_source?.length ? (
            <div className="text-center py-8 text-slate-500">
              <p>No attributed revenue yet</p>
            </div>
          ) : (
            <div className="space-y-3">
              {stats.by_source.map((source) => {
                const percentage = stats.total_revenue
                  ? (source.revenue / stats.total_revenue) * 100
                  : 0;
                return (
                  <div key={source.source} className="flex items-center gap-4">
                    <div className="flex-1">
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-sm font-medium text-slate-900">
                          {source.source || 'Direct'}
                        </span>
                        <span className="text-sm text-slate-500">
                          {formatCurrency(source.revenue)}
                        </span>
                      </div>
                      <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div
                          className="h-full bg-primary-500 rounded-full"
                          style={{ width: `${percentage}%` }}
                        />
                      </div>
                    </div>
                    <span className="text-sm text-slate-400 w-12 text-right">
                      {source.orders} orders
                    </span>
                  </div>
                );
              })}
            </div>
          )}
        </Card>

        {/* By Campaign */}
        <Card>
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold text-slate-900">Revenue by Campaign</h3>
            <BarChart2 className="w-5 h-5 text-slate-400" />
          </div>
          {revenueLoading ? (
            <div className="animate-pulse space-y-2">
              {[1, 2, 3, 4].map((i) => (
                <div key={i} className="h-10 bg-slate-100 rounded-lg" />
              ))}
            </div>
          ) : !stats.by_campaign?.length ? (
            <div className="text-center py-8 text-slate-500">
              <p>No campaign data yet</p>
            </div>
          ) : (
            <div className="space-y-3">
              {stats.by_campaign.slice(0, 5).map((campaign) => {
                const percentage = stats.total_revenue
                  ? (campaign.revenue / stats.total_revenue) * 100
                  : 0;
                return (
                  <div key={campaign.campaign} className="flex items-center gap-4">
                    <div className="flex-1">
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-sm font-medium text-slate-900">
                          {campaign.campaign || '(no campaign)'}
                        </span>
                        <span className="text-sm text-slate-500">
                          {formatCurrency(campaign.revenue)}
                        </span>
                      </div>
                      <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div
                          className="h-full bg-green-500 rounded-full"
                          style={{ width: `${percentage}%` }}
                        />
                      </div>
                    </div>
                    <span className="text-sm text-slate-400 w-12 text-right">
                      {campaign.orders} orders
                    </span>
                  </div>
                );
              })}
            </div>
          )}
        </Card>
      </div>

      {/* Attribution Report */}
      <Card className="mb-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-semibold text-slate-900">Attribution Report</h3>
          <Select
            options={[
              { value: 'source', label: 'Group by Source' },
              { value: 'medium', label: 'Group by Medium' },
              { value: 'campaign', label: 'Group by Campaign' },
            ]}
            value={groupBy}
            onChange={(e) => setGroupBy(e.target.value as typeof groupBy)}
            fullWidth={false}
          />
        </div>

        {reportLoading ? (
          <div className="animate-pulse h-48 bg-slate-100 rounded-lg" />
        ) : displayReport.length === 0 ? (
          <div className="text-center py-8 text-slate-500">
            <Users className="w-12 h-12 mx-auto mb-3 text-slate-300" />
            <p>No attribution data for this period</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-slate-200">
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500 capitalize">
                    {groupBy}
                  </th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Orders</th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">
                    Customers
                  </th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Revenue</th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">
                    Avg Order
                  </th>
                </tr>
              </thead>
              <tbody>
                {displayReport.map((row) => (
                  <tr key={row.channel} className="border-b border-slate-100">
                    <td className="py-3 px-4">
                      <span className="font-medium text-slate-900">{row.channel || 'Direct'}</span>
                    </td>
                    <td className="py-3 px-4 text-slate-600">{row.orders}</td>
                    <td className="py-3 px-4 text-slate-600">{row.customers}</td>
                    <td className="py-3 px-4 font-medium text-slate-900">
                      {formatCurrency(row.revenue)}
                    </td>
                    <td className="py-3 px-4 text-slate-600">
                      {formatCurrency(row.avg_order_value)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      {/* Recent Attributed Orders */}
      <Card>
        <h3 className="font-semibold text-slate-900 mb-4">Recent Attributed Orders</h3>
        <Table
          data={displayOrders}
          columns={columns}
          loading={ordersLoading}
        />
        {!displaySampleData && ordersData && ordersData.total_pages > 1 && (
          <Pagination
            page={page}
            totalPages={ordersData.total_pages}
            total={ordersData.total}
            perPage={10}
            onPageChange={setPage}
          />
        )}
      </Card>
    </Layout>
  );
}

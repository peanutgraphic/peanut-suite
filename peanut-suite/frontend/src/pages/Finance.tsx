import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import {
  DollarSign,
  FileText,
  Receipt,
  TrendingUp,
  TrendingDown,
  ArrowRight,
  Plus,
  Clock,
  AlertTriangle,
  CheckCircle,
  Send,
  Calendar,
  CreditCard,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, StatCard, Skeleton, BarChart, DoughnutChart } from '../components/common';
import { financeApi, projectsApi } from '../api/endpoints';
import { useAccountStore } from '../store';
import type { Invoice, InvoiceStatus, Payment, ExpenseCategory } from '../types';

// Status colors
const statusColors: Record<InvoiceStatus, { bg: string; text: string }> = {
  draft: { bg: 'bg-slate-100', text: 'text-slate-700' },
  sent: { bg: 'bg-blue-100', text: 'text-blue-700' },
  viewed: { bg: 'bg-cyan-100', text: 'text-cyan-700' },
  partial: { bg: 'bg-amber-100', text: 'text-amber-700' },
  paid: { bg: 'bg-green-100', text: 'text-green-700' },
  overdue: { bg: 'bg-red-100', text: 'text-red-700' },
  cancelled: { bg: 'bg-slate-100', text: 'text-slate-500' },
};

// Category labels
const categoryLabels: Record<ExpenseCategory, string> = {
  software: 'Software',
  hosting: 'Hosting',
  advertising: 'Advertising',
  contractors: 'Contractors',
  office: 'Office',
  travel: 'Travel',
  meals: 'Meals',
  equipment: 'Equipment',
  professional: 'Professional',
  insurance: 'Insurance',
  utilities: 'Utilities',
  other: 'Other',
};

export default function Finance() {
  const { isOwnerOrAdmin } = useAccountStore();
  const [period, setPeriod] = useState<'day' | 'week' | 'month' | 'year'>('month');
  const [projectFilter, setProjectFilter] = useState<number | undefined>();

  // Fetch projects for filter
  const { data: projects } = useQuery({
    queryKey: ['projects'],
    queryFn: () => projectsApi.getAll(),
    enabled: isOwnerOrAdmin(),
  });

  // Fetch finance dashboard data
  const { data: dashboard, isLoading: loadingDashboard } = useQuery({
    queryKey: ['finance-dashboard', projectFilter],
    queryFn: () => financeApi.getDashboard(projectFilter),
    enabled: isOwnerOrAdmin(),
    retry: false,
  });

  // Fetch revenue data
  const { data: revenueData, isLoading: loadingRevenue } = useQuery({
    queryKey: ['finance-revenue', period, projectFilter],
    queryFn: () => financeApi.getRevenue({ period, project_id: projectFilter }),
    enabled: isOwnerOrAdmin(),
    retry: false,
  });

  // Fetch P&L report
  const { data: profitLoss } = useQuery({
    queryKey: ['finance-profit-loss', projectFilter],
    queryFn: () => financeApi.getProfitLoss({ project_id: projectFilter }),
    enabled: isOwnerOrAdmin(),
    retry: false,
  });

  const isLoading = loadingDashboard || loadingRevenue;

  // Generate revenue chart data
  const revenueChartData = useMemo(() => {
    if (revenueData?.data && revenueData.data.length > 0) {
      return {
        labels: revenueData.data.map(d => d.period),
        revenue: revenueData.data.map(d => d.revenue),
      };
    }
    // Sample data
    const periods = period === 'day' ? 7 : period === 'week' ? 4 : period === 'month' ? 6 : 12;
    return {
      labels: Array.from({ length: periods }, (_, i) => `Period ${i + 1}`),
      revenue: Array.from({ length: periods }, () => 0),
    };
  }, [revenueData, period]);

  // Expense breakdown by category
  const expenseChartData = useMemo(() => {
    if (profitLoss?.expenses_by_category && profitLoss.expenses_by_category.length > 0) {
      return {
        labels: profitLoss.expenses_by_category.map(c => categoryLabels[c.category] || c.category),
        data: profitLoss.expenses_by_category.map(c => c.total),
      };
    }
    return {
      labels: ['No expenses'],
      data: [0],
    };
  }, [profitLoss]);

  // Format currency
  const formatCurrency = (amount: number, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency,
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const pageInfo = {
    title: 'Finance',
    description: 'Track invoices, expenses, quotes, and payments for your projects.',
  };

  return (
    <Layout
      title={pageInfo.title}
      description={pageInfo.description}
    >
      {/* Quick Actions */}
      <div className="flex gap-2 mb-6">
        <Link
          to="/finance/invoices/new"
          className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium text-sm transition-colors"
        >
          <Plus className="w-4 h-4" />
          New Invoice
        </Link>
        <Link
          to="/finance/quotes/new"
          className="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 bg-white text-slate-700 rounded-lg hover:bg-slate-50 font-medium text-sm transition-colors"
        >
          <FileText className="w-4 h-4" />
          New Quote
        </Link>
      </div>
      {/* Filters */}
      <div className="flex items-center gap-4 mb-6">
        <select
          value={projectFilter || ''}
          onChange={(e) => setProjectFilter(e.target.value ? parseInt(e.target.value) : undefined)}
          className="text-sm border border-slate-200 rounded-lg px-3 py-1.5"
        >
          <option value="">All Projects</option>
          {projects?.map((project) => (
            <option key={project.id} value={project.id}>
              {project.name}
            </option>
          ))}
        </select>

        <select
          value={period}
          onChange={(e) => setPeriod(e.target.value as 'day' | 'week' | 'month' | 'year')}
          className="text-sm border border-slate-200 rounded-lg px-3 py-1.5"
        >
          <option value="day">Daily</option>
          <option value="week">Weekly</option>
          <option value="month">Monthly</option>
          <option value="year">Yearly</option>
        </select>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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
              title="Total Revenue"
              value={formatCurrency(dashboard?.invoices.total_paid || 0)}
              icon={<DollarSign className="w-5 h-5" />}
              tooltip="Total payments received"
            />
            <StatCard
              title="Outstanding"
              value={formatCurrency(dashboard?.invoices.total_outstanding || 0)}
              icon={<Clock className="w-5 h-5" />}
              tooltip="Unpaid invoice balance"
            />
            <StatCard
              title="Total Expenses"
              value={formatCurrency(dashboard?.expenses.total || 0)}
              icon={<Receipt className="w-5 h-5" />}
              tooltip="Total expenses tracked"
            />
            <StatCard
              title="Profit"
              value={formatCurrency(dashboard?.profit || 0)}
              icon={
                (dashboard?.profit || 0) >= 0 ? (
                  <TrendingUp className="w-5 h-5" />
                ) : (
                  <TrendingDown className="w-5 h-5" />
                )
              }
              tooltip="Revenue minus expenses"
            />
          </>
        )}
      </div>

      {/* Invoice Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-8">
        <InvoiceStatBadge
          label="Draft"
          count={dashboard?.invoices.draft || 0}
          icon={<FileText className="w-4 h-4" />}
          color="slate"
        />
        <InvoiceStatBadge
          label="Sent"
          count={dashboard?.invoices.sent || 0}
          icon={<Send className="w-4 h-4" />}
          color="blue"
        />
        <InvoiceStatBadge
          label="Viewed"
          count={dashboard?.invoices.viewed || 0}
          icon={<FileText className="w-4 h-4" />}
          color="cyan"
        />
        <InvoiceStatBadge
          label="Partial"
          count={dashboard?.invoices.partial || 0}
          icon={<CreditCard className="w-4 h-4" />}
          color="amber"
        />
        <InvoiceStatBadge
          label="Paid"
          count={dashboard?.invoices.paid || 0}
          icon={<CheckCircle className="w-4 h-4" />}
          color="green"
        />
        <InvoiceStatBadge
          label="Overdue"
          count={dashboard?.invoices.overdue || 0}
          icon={<AlertTriangle className="w-4 h-4" />}
          color="red"
        />
        <InvoiceStatBadge
          label="Total"
          count={dashboard?.invoices.total || 0}
          icon={<FileText className="w-4 h-4" />}
          color="primary"
        />
      </div>

      {/* Charts */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <Card className="lg:col-span-2">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-lg font-semibold text-slate-900">Revenue Trend</h3>
          </div>
          <BarChart
            labels={revenueChartData.labels}
            datasets={[
              {
                label: 'Revenue',
                data: revenueChartData.revenue,
                backgroundColor: '#22c55e',
              },
            ]}
            height={280}
            showLegend={false}
          />
        </Card>

        <Card>
          <h3 className="text-lg font-semibold text-slate-900 mb-6">Expenses by Category</h3>
          <DoughnutChart
            labels={expenseChartData.labels}
            data={expenseChartData.data}
            height={240}
          />
        </Card>
      </div>

      {/* Recent Activity */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {/* Recent Invoices */}
        <Card>
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <FileText className="w-4 h-4 text-blue-600" />
              </div>
              <h3 className="font-semibold text-slate-900">Recent Invoices</h3>
            </div>
            <Link
              to="/finance/invoices"
              className="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1"
            >
              View all <ArrowRight className="w-3 h-3" />
            </Link>
          </div>
          {isLoading ? (
            <div className="space-y-2">
              <Skeleton className="h-12" />
              <Skeleton className="h-12" />
              <Skeleton className="h-12" />
            </div>
          ) : (
            <ul className="space-y-2">
              {dashboard?.recent_invoices?.slice(0, 5).map((invoice) => (
                <InvoiceListItem key={invoice.id} invoice={invoice} />
              ))}
              {!dashboard?.recent_invoices?.length && (
                <li className="text-sm text-slate-400 py-4 text-center">No invoices yet</li>
              )}
            </ul>
          )}
        </Card>

        {/* Recent Payments */}
        <Card>
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <CreditCard className="w-4 h-4 text-green-600" />
              </div>
              <h3 className="font-semibold text-slate-900">Recent Payments</h3>
            </div>
            <Link
              to="/finance/payments"
              className="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1"
            >
              View all <ArrowRight className="w-3 h-3" />
            </Link>
          </div>
          {isLoading ? (
            <div className="space-y-2">
              <Skeleton className="h-12" />
              <Skeleton className="h-12" />
              <Skeleton className="h-12" />
            </div>
          ) : (
            <ul className="space-y-2">
              {dashboard?.recent_payments?.slice(0, 5).map((payment) => (
                <PaymentListItem key={payment.id} payment={payment} />
              ))}
              {!dashboard?.recent_payments?.length && (
                <li className="text-sm text-slate-400 py-4 text-center">No payments yet</li>
              )}
            </ul>
          )}
        </Card>
      </div>

      {/* Quick Actions */}
      <Card>
        <h3 className="text-lg font-semibold text-slate-900 mb-4">Quick Actions</h3>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
          <QuickActionButton
            href="/finance/invoices/new"
            icon={<FileText className="w-5 h-5" />}
            title="Create Invoice"
            description="Bill your clients"
          />
          <QuickActionButton
            href="/finance/quotes/new"
            icon={<FileText className="w-5 h-5" />}
            title="Create Quote"
            description="Send an estimate"
          />
          <QuickActionButton
            href="/finance/expenses"
            icon={<Receipt className="w-5 h-5" />}
            title="Add Expense"
            description="Track costs"
          />
          <QuickActionButton
            href="/finance/recurring"
            icon={<Calendar className="w-5 h-5" />}
            title="Recurring Invoice"
            description="Automate billing"
          />
        </div>
      </Card>

      {/* Tier Limit Warning */}
      {dashboard?.limits && !dashboard.limits.can_create && (
        <div className="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
          <div className="flex items-start gap-3">
            <AlertTriangle className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
            <div>
              <p className="font-medium text-amber-800">Invoice Limit Reached</p>
              <p className="text-sm text-amber-700 mt-1">
                You've used {dashboard.limits.used} of {dashboard.limits.limit} invoices this month.
                Upgrade your plan to create more invoices.
              </p>
            </div>
          </div>
        </div>
      )}
    </Layout>
  );
}

// Sub-components

interface InvoiceStatBadgeProps {
  label: string;
  count: number;
  icon: React.ReactNode;
  color: 'slate' | 'blue' | 'cyan' | 'amber' | 'green' | 'red' | 'primary';
}

function InvoiceStatBadge({ label, count, icon, color }: InvoiceStatBadgeProps) {
  const colorClasses: Record<string, string> = {
    slate: 'bg-slate-50 text-slate-600 border-slate-200',
    blue: 'bg-blue-50 text-blue-600 border-blue-200',
    cyan: 'bg-cyan-50 text-cyan-600 border-cyan-200',
    amber: 'bg-amber-50 text-amber-600 border-amber-200',
    green: 'bg-green-50 text-green-600 border-green-200',
    red: 'bg-red-50 text-red-600 border-red-200',
    primary: 'bg-primary-50 text-primary-600 border-primary-200',
  };

  return (
    <div className={`flex flex-col items-center p-3 rounded-lg border ${colorClasses[color]}`}>
      <div className="flex items-center gap-1 mb-1">
        {icon}
        <span className="text-xl font-semibold">{count}</span>
      </div>
      <span className="text-xs font-medium">{label}</span>
    </div>
  );
}

interface InvoiceListItemProps {
  invoice: Invoice;
}

function InvoiceListItem({ invoice }: InvoiceListItemProps) {
  const formatCurrency = (amount: number, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency,
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const statusStyle = statusColors[invoice.status];

  return (
    <li className="group">
      <Link
        to={`/finance/invoices/${invoice.id}`}
        className="flex items-center justify-between p-2 -mx-2 rounded-lg hover:bg-slate-50 transition-colors"
      >
        <div className="min-w-0 flex-1">
          <p className="font-medium text-slate-900 text-sm truncate">
            {invoice.invoice_number} - {invoice.client_name}
          </p>
          <p className="text-xs text-slate-400">
            {invoice.issue_date ? new Date(invoice.issue_date).toLocaleDateString() : 'No date'}
          </p>
        </div>
        <div className="flex items-center gap-2 ml-2">
          <span className="text-sm font-medium text-slate-900">
            {formatCurrency(invoice.total, invoice.currency)}
          </span>
          <span className={`text-xs px-2 py-0.5 rounded-full ${statusStyle.bg} ${statusStyle.text}`}>
            {invoice.status}
          </span>
        </div>
      </Link>
    </li>
  );
}

interface PaymentListItemProps {
  payment: Payment;
}

function PaymentListItem({ payment }: PaymentListItemProps) {
  const formatCurrency = (amount: number, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency,
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  return (
    <li className="group">
      <div className="flex items-center justify-between p-2 -mx-2 rounded-lg hover:bg-slate-50 transition-colors">
        <div className="min-w-0 flex-1">
          <p className="font-medium text-slate-900 text-sm truncate">
            {payment.invoice_number || `Invoice #${payment.invoice_id}`}
          </p>
          <p className="text-xs text-slate-400">
            {new Date(payment.payment_date).toLocaleDateString()} via {payment.payment_method}
          </p>
        </div>
        <span className="text-sm font-medium text-green-600 ml-2">
          +{formatCurrency(payment.amount, payment.currency)}
        </span>
      </div>
    </li>
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

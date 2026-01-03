import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import {
  FileText,
  Plus,
  Search,
  MoreHorizontal,
  Send,
  Eye,
  Trash2,
  Download,
  Copy,
  X,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Input, Skeleton, EmptyState, ConfirmModal, useToast } from '../components/common';
import { invoicesApi, projectsApi } from '../api/endpoints';
import { useAccountStore } from '../store';
import type { Invoice, InvoiceStatus } from '../types';

// Status configurations
const statusConfig: Record<InvoiceStatus, { label: string; bg: string; text: string }> = {
  draft: { label: 'Draft', bg: 'bg-slate-100', text: 'text-slate-700' },
  sent: { label: 'Sent', bg: 'bg-blue-100', text: 'text-blue-700' },
  viewed: { label: 'Viewed', bg: 'bg-cyan-100', text: 'text-cyan-700' },
  partial: { label: 'Partial', bg: 'bg-amber-100', text: 'text-amber-700' },
  paid: { label: 'Paid', bg: 'bg-green-100', text: 'text-green-700' },
  overdue: { label: 'Overdue', bg: 'bg-red-100', text: 'text-red-700' },
  cancelled: { label: 'Cancelled', bg: 'bg-slate-100', text: 'text-slate-500' },
};

const statusTabs: InvoiceStatus[] = ['draft', 'sent', 'viewed', 'partial', 'paid', 'overdue', 'cancelled'];

export default function Invoices() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { isOwnerOrAdmin } = useAccountStore();

  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<InvoiceStatus | 'all'>('all');
  const [projectFilter, setProjectFilter] = useState<number | undefined>();
  const [page, setPage] = useState(1);
  const [deleteInvoice, setDeleteInvoice] = useState<Invoice | null>(null);
  const [openMenuId, setOpenMenuId] = useState<number | null>(null);

  const perPage = 20;

  // Fetch projects for filter
  const { data: projects } = useQuery({
    queryKey: ['projects'],
    queryFn: () => projectsApi.getAll(),
    enabled: isOwnerOrAdmin(),
  });

  // Fetch invoices
  const { data: invoicesData, isLoading } = useQuery({
    queryKey: ['invoices', page, statusFilter, projectFilter, search],
    queryFn: () =>
      invoicesApi.getAll({
        page,
        per_page: perPage,
        status: statusFilter === 'all' ? undefined : statusFilter,
        project_id: projectFilter,
        search: search || undefined,
      }),
    enabled: isOwnerOrAdmin(),
  });

  // Fetch stats
  const { data: stats } = useQuery({
    queryKey: ['invoice-stats', projectFilter],
    queryFn: () => invoicesApi.getStats(projectFilter),
    enabled: isOwnerOrAdmin(),
  });

  // Send invoice mutation
  const sendMutation = useMutation({
    mutationFn: (id: number) => invoicesApi.send(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['invoices'] });
      queryClient.invalidateQueries({ queryKey: ['invoice-stats'] });
      toast.success('Invoice sent successfully');
    },
    onError: () => toast.error('Failed to send invoice'),
  });

  // Delete invoice mutation
  const deleteMutation = useMutation({
    mutationFn: (id: number) => invoicesApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['invoices'] });
      queryClient.invalidateQueries({ queryKey: ['invoice-stats'] });
      toast.success('Invoice deleted');
      setDeleteInvoice(null);
    },
    onError: () => toast.error('Failed to delete invoice'),
  });

  // Duplicate invoice mutation
  const duplicateMutation = useMutation({
    mutationFn: (id: number) => invoicesApi.duplicate(id),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['invoices'] });
      queryClient.invalidateQueries({ queryKey: ['invoice-stats'] });
      toast.success('Invoice duplicated');
      navigate(`/finance/invoices/${data.id}`);
    },
    onError: () => toast.error('Failed to duplicate invoice'),
  });

  // Format currency
  const formatCurrency = (amount: number, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency,
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const invoices = invoicesData?.items || [];
  const totalPages = Math.ceil((invoicesData?.total || 0) / perPage);

  return (
    <Layout
      title="Invoices"
      description="Manage and track your client invoices."
    >
      {/* Header Actions */}
      <div className="flex justify-end mb-6">
        <Link
          to="/finance/invoices/new"
          className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium text-sm transition-colors"
        >
          <Plus className="w-4 h-4" />
          New Invoice
        </Link>
      </div>
      {/* Stats Bar */}
      {stats && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div className="bg-white rounded-lg border border-slate-200 p-4">
            <p className="text-sm text-slate-500">Total Invoiced</p>
            <p className="text-xl font-semibold text-slate-900">
              {formatCurrency(stats.total_invoiced)}
            </p>
          </div>
          <div className="bg-white rounded-lg border border-slate-200 p-4">
            <p className="text-sm text-slate-500">Paid</p>
            <p className="text-xl font-semibold text-green-600">
              {formatCurrency(stats.total_paid)}
            </p>
          </div>
          <div className="bg-white rounded-lg border border-slate-200 p-4">
            <p className="text-sm text-slate-500">Outstanding</p>
            <p className="text-xl font-semibold text-amber-600">
              {formatCurrency(stats.total_outstanding)}
            </p>
          </div>
          <div className="bg-white rounded-lg border border-slate-200 p-4">
            <p className="text-sm text-slate-500">Overdue</p>
            <p className="text-xl font-semibold text-red-600">{stats.overdue} invoices</p>
          </div>
        </div>
      )}

      {/* Filters */}
      <Card className="mb-6">
        <div className="flex flex-col lg:flex-row gap-4">
          {/* Search */}
          <div className="flex-1">
            <Input
              type="text"
              placeholder="Search invoices..."
              leftIcon={<Search className="w-4 h-4" />}
              value={search}
              onChange={(e) => {
                setSearch(e.target.value);
                setPage(1);
              }}
            />
          </div>

          {/* Project Filter */}
          <select
            value={projectFilter || ''}
            onChange={(e) => {
              setProjectFilter(e.target.value ? parseInt(e.target.value) : undefined);
              setPage(1);
            }}
            className="border border-slate-200 rounded-lg px-3 py-2 text-sm"
          >
            <option value="">All Projects</option>
            {projects?.map((project) => (
              <option key={project.id} value={project.id}>
                {project.name}
              </option>
            ))}
          </select>

          {/* Clear Filters */}
          {(search || projectFilter || statusFilter !== 'all') && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => {
                setSearch('');
                setProjectFilter(undefined);
                setStatusFilter('all');
                setPage(1);
              }}
              icon={<X className="w-4 h-4" />}
            >
              Clear
            </Button>
          )}
        </div>

        {/* Status Tabs */}
        <div className="mt-4 flex flex-wrap gap-2 pt-4 border-t border-slate-100">
          <button
            onClick={() => {
              setStatusFilter('all');
              setPage(1);
            }}
            className={`px-3 py-1.5 rounded-full text-sm font-medium transition-colors ${
              statusFilter === 'all'
                ? 'bg-primary-100 text-primary-700'
                : 'text-slate-600 hover:bg-slate-100'
            }`}
          >
            All ({stats?.total || 0})
          </button>
          {statusTabs.map((status) => {
            const config = statusConfig[status];
            const count = stats?.[status as keyof typeof stats] || 0;
            return (
              <button
                key={status}
                onClick={() => {
                  setStatusFilter(status);
                  setPage(1);
                }}
                className={`px-3 py-1.5 rounded-full text-sm font-medium transition-colors ${
                  statusFilter === status
                    ? `${config.bg} ${config.text}`
                    : 'text-slate-600 hover:bg-slate-100'
                }`}
              >
                {config.label} ({count})
              </button>
            );
          })}
        </div>
      </Card>

      {/* Invoices Table */}
      <Card>
        {isLoading ? (
          <div className="space-y-4">
            {Array.from({ length: 5 }).map((_, i) => (
              <Skeleton key={i} className="h-16" />
            ))}
          </div>
        ) : invoices.length === 0 ? (
          <EmptyState
            icon={<FileText className="w-12 h-12" />}
            title="No invoices found"
            description={
              search || statusFilter !== 'all' || projectFilter
                ? 'Try adjusting your filters'
                : "Create your first invoice to start tracking client payments."
            }
            action={
              !search && statusFilter === 'all' && !projectFilter ? (
                <Link
                  to="/finance/invoices/new"
                  className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium text-sm transition-colors"
                >
                  <Plus className="w-4 h-4" />
                  Create Invoice
                </Link>
              ) : undefined
            }
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-slate-200">
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Invoice</th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Client</th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Date</th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Due</th>
                  <th className="text-right py-3 px-4 text-sm font-medium text-slate-500">Amount</th>
                  <th className="text-right py-3 px-4 text-sm font-medium text-slate-500">Balance</th>
                  <th className="text-center py-3 px-4 text-sm font-medium text-slate-500">Status</th>
                  <th className="w-12"></th>
                </tr>
              </thead>
              <tbody>
                {invoices.map((invoice) => {
                  const config = statusConfig[invoice.status];
                  return (
                    <tr
                      key={invoice.id}
                      className="border-b border-slate-100 hover:bg-slate-50 transition-colors cursor-pointer"
                      onClick={() => navigate(`/finance/invoices/${invoice.id}`)}
                    >
                      <td className="py-3 px-4">
                        <p className="font-medium text-slate-900">{invoice.invoice_number}</p>
                      </td>
                      <td className="py-3 px-4">
                        <p className="text-sm text-slate-900">{invoice.client_name}</p>
                        <p className="text-xs text-slate-400">{invoice.client_email}</p>
                      </td>
                      <td className="py-3 px-4 text-sm text-slate-600">
                        {invoice.issue_date
                          ? new Date(invoice.issue_date).toLocaleDateString()
                          : '-'}
                      </td>
                      <td className="py-3 px-4 text-sm text-slate-600">
                        {invoice.due_date ? new Date(invoice.due_date).toLocaleDateString() : '-'}
                      </td>
                      <td className="py-3 px-4 text-sm text-right font-medium text-slate-900">
                        {formatCurrency(invoice.total, invoice.currency)}
                      </td>
                      <td className="py-3 px-4 text-sm text-right font-medium">
                        <span
                          className={invoice.balance_due > 0 ? 'text-amber-600' : 'text-green-600'}
                        >
                          {formatCurrency(invoice.balance_due, invoice.currency)}
                        </span>
                      </td>
                      <td className="py-3 px-4 text-center">
                        <span
                          className={`inline-block px-2 py-1 rounded-full text-xs font-medium ${config.bg} ${config.text}`}
                        >
                          {config.label}
                        </span>
                      </td>
                      <td className="py-3 px-4">
                        <div className="relative">
                          <button
                            onClick={(e) => {
                              e.stopPropagation();
                              setOpenMenuId(openMenuId === invoice.id ? null : invoice.id);
                            }}
                            className="p-1 rounded hover:bg-slate-200 transition-colors"
                          >
                            <MoreHorizontal className="w-4 h-4 text-slate-400" />
                          </button>

                          {openMenuId === invoice.id && (
                            <div className="absolute right-0 mt-1 w-48 bg-white border border-slate-200 rounded-lg shadow-lg z-10">
                              <Link
                                to={`/finance/invoices/${invoice.id}`}
                                className="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                onClick={(e) => e.stopPropagation()}
                              >
                                <Eye className="w-4 h-4" />
                                View
                              </Link>
                              {invoice.status === 'draft' && (
                                <button
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    sendMutation.mutate(invoice.id);
                                    setOpenMenuId(null);
                                  }}
                                  className="w-full flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                >
                                  <Send className="w-4 h-4" />
                                  Send
                                </button>
                              )}
                              <button
                                onClick={(e) => {
                                  e.stopPropagation();
                                  duplicateMutation.mutate(invoice.id);
                                  setOpenMenuId(null);
                                }}
                                disabled={duplicateMutation.isPending}
                                className="w-full flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                              >
                                <Copy className="w-4 h-4" />
                                {duplicateMutation.isPending ? 'Duplicating...' : 'Duplicate'}
                              </button>
                              <button
                                onClick={(e) => {
                                  e.stopPropagation();
                                  navigate(`/finance/invoices/${invoice.id}?print=true`);
                                  setOpenMenuId(null);
                                }}
                                className="w-full flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                              >
                                <Download className="w-4 h-4" />
                                Download PDF
                              </button>
                              <hr className="my-1" />
                              <button
                                onClick={(e) => {
                                  e.stopPropagation();
                                  setDeleteInvoice(invoice);
                                  setOpenMenuId(null);
                                }}
                                className="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                              >
                                <Trash2 className="w-4 h-4" />
                                Delete
                              </button>
                            </div>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex items-center justify-between mt-4 pt-4 border-t border-slate-100">
            <p className="text-sm text-slate-500">
              Page {page} of {totalPages} ({invoicesData?.total || 0} invoices)
            </p>
            <div className="flex gap-2">
              <Button
                variant="outline"
                size="sm"
                disabled={page === 1}
                onClick={() => setPage(page - 1)}
              >
                Previous
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={page === totalPages}
                onClick={() => setPage(page + 1)}
              >
                Next
              </Button>
            </div>
          </div>
        )}
      </Card>

      {/* Delete Confirmation */}
      <ConfirmModal
        isOpen={!!deleteInvoice}
        onClose={() => setDeleteInvoice(null)}
        onConfirm={() => deleteInvoice && deleteMutation.mutate(deleteInvoice.id)}
        title="Delete Invoice"
        message={`Are you sure you want to delete invoice ${deleteInvoice?.invoice_number}? This action cannot be undone.`}
        confirmText="Delete"
        variant="danger"
        loading={deleteMutation.isPending}
      />

      {/* Click outside to close menu */}
      {openMenuId && (
        <div className="fixed inset-0 z-0" onClick={() => setOpenMenuId(null)} />
      )}
    </Layout>
  );
}

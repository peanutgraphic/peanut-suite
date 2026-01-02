import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  Building2,
  Plus,
  Trash2,
  Edit2,
  Eye,
  Loader2,
  Search,
  Mail,
  Globe,
  DollarSign,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Input, Modal, Badge, Textarea, useToast, ConfirmModal } from '../components/common';
import { clientsApi } from '../api/endpoints';
import { useAccountStore } from '../store';
import type { Client, ClientFormData, ClientStatus } from '../types';

const STATUS_VARIANTS: Record<ClientStatus, 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'> = {
  active: 'success',
  inactive: 'warning',
  archived: 'default',
};

const CLIENT_SIZES = [
  { value: 'solo', label: 'Solo / Freelancer' },
  { value: 'small', label: 'Small (1-10)' },
  { value: 'medium', label: 'Medium (11-50)' },
  { value: 'large', label: 'Large (51-200)' },
  { value: 'enterprise', label: 'Enterprise (200+)' },
];

const CURRENCIES = [
  { value: 'USD', label: 'USD - US Dollar' },
  { value: 'EUR', label: 'EUR - Euro' },
  { value: 'GBP', label: 'GBP - British Pound' },
  { value: 'CAD', label: 'CAD - Canadian Dollar' },
  { value: 'AUD', label: 'AUD - Australian Dollar' },
];

export default function Clients() {
  const queryClient = useQueryClient();
  const toast = useToast();
  const { isOwnerOrAdmin } = useAccountStore();

  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [selectedClient, setSelectedClient] = useState<Client | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('');

  // Form state
  const [formData, setFormData] = useState<ClientFormData>({
    name: '',
    legal_name: '',
    website: '',
    industry: '',
    size: undefined,
    billing_email: '',
    billing_address: '',
    billing_city: '',
    billing_state: '',
    billing_postal: '',
    billing_country: '',
    currency: 'USD',
    payment_terms: 30,
    notes: '',
  });

  // Fetch clients
  const { data: clients = [], isLoading } = useQuery({
    queryKey: ['clients', { search: searchQuery, status: statusFilter }],
    queryFn: () => clientsApi.getAll({
      search: searchQuery || undefined,
      status: statusFilter || undefined,
    }),
  });

  // Fetch limits
  const { data: limits } = useQuery({
    queryKey: ['clients', 'limits'],
    queryFn: () => clientsApi.getLimits(),
  });

  // Create mutation
  const createMutation = useMutation({
    mutationFn: (data: ClientFormData) => clientsApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['clients'] });
      setShowCreateModal(false);
      resetForm();
      toast.success('Client created');
    },
    onError: (error: Error) => {
      toast.error(error.message);
    },
  });

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<ClientFormData> }) =>
      clientsApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['clients'] });
      setShowEditModal(false);
      setSelectedClient(null);
      resetForm();
      toast.success('Client updated');
    },
    onError: (error: Error) => {
      toast.error(error.message);
    },
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: (id: number) => clientsApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['clients'] });
      setShowDeleteModal(false);
      setSelectedClient(null);
      toast.success('Client deleted');
    },
    onError: (error: Error) => {
      toast.error(error.message);
    },
  });

  const resetForm = () => {
    setFormData({
      name: '',
      legal_name: '',
      website: '',
      industry: '',
      size: undefined,
      billing_email: '',
      billing_address: '',
      billing_city: '',
      billing_state: '',
      billing_postal: '',
      billing_country: '',
      currency: 'USD',
      payment_terms: 30,
      notes: '',
    });
  };

  const handleEdit = (client: Client) => {
    setSelectedClient(client);
    setFormData({
      name: client.name,
      legal_name: client.legal_name || '',
      website: client.website || '',
      industry: client.industry || '',
      size: client.size,
      billing_email: client.billing_email || '',
      billing_address: client.billing_address || '',
      billing_city: client.billing_city || '',
      billing_state: client.billing_state || '',
      billing_postal: client.billing_postal || '',
      billing_country: client.billing_country || '',
      currency: client.currency || 'USD',
      payment_terms: client.payment_terms || 30,
      notes: client.notes || '',
    });
    setShowEditModal(true);
  };

  const handleDelete = (client: Client) => {
    setSelectedClient(client);
    setShowDeleteModal(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (selectedClient) {
      updateMutation.mutate({ id: selectedClient.id, data: formData });
    } else {
      createMutation.mutate(formData);
    }
  };

  const canCreate = limits?.can_create ?? true;

  return (
    <Layout
      title="Clients"
      description="Manage your clients and their projects"
    >
      {/* Header Actions */}
      <div className="flex flex-col sm:flex-row gap-4 mb-6">
        <div className="flex-1 flex gap-3">
          <div className="relative flex-1 max-w-md">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <Input
              placeholder="Search clients..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10"
            />
          </div>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="px-3 py-2 border border-slate-200 rounded-lg text-sm"
          >
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="archived">Archived</option>
          </select>
        </div>
        {isOwnerOrAdmin() && (
          <Button
            onClick={() => setShowCreateModal(true)}
            icon={<Plus className="w-4 h-4" />}
            disabled={!canCreate}
          >
            Add Client
          </Button>
        )}
      </div>

      {/* Limits Badge */}
      {limits && (
        <div className="mb-4">
          <Badge variant={limits.can_create ? 'info' : 'warning'}>
            {limits.unlimited ? 'Unlimited' : `${limits.current} / ${limits.max}`} clients
          </Badge>
        </div>
      )}

      {/* Loading State */}
      {isLoading && (
        <div className="flex items-center justify-center py-12">
          <Loader2 className="w-8 h-8 animate-spin text-primary-500" />
        </div>
      )}

      {/* Empty State */}
      {!isLoading && clients.length === 0 && (
        <Card className="p-12 text-center">
          <Building2 className="w-12 h-12 text-slate-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-slate-900 mb-2">No clients yet</h3>
          <p className="text-slate-500 mb-6">
            Create your first client to start organizing projects and invoices.
          </p>
          {isOwnerOrAdmin() && (
            <Button
              onClick={() => setShowCreateModal(true)}
              icon={<Plus className="w-4 h-4" />}
              disabled={!canCreate}
            >
              Add Client
            </Button>
          )}
        </Card>
      )}

      {/* Clients Grid */}
      {!isLoading && clients.length > 0 && (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {clients.map((client) => (
            <Card key={client.id} className="p-4">
              <div className="flex items-start justify-between mb-3">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                    <Building2 className="w-5 h-5 text-primary-600" />
                  </div>
                  <div>
                    <h3 className="font-medium text-slate-900">{client.name}</h3>
                    {client.industry && (
                      <p className="text-sm text-slate-500">{client.industry}</p>
                    )}
                  </div>
                </div>
                <Badge variant={STATUS_VARIANTS[client.status]}>
                  {client.status}
                </Badge>
              </div>

              {/* Client Info */}
              <div className="space-y-2 text-sm text-slate-600 mb-4">
                {client.billing_email && (
                  <div className="flex items-center gap-2">
                    <Mail className="w-4 h-4 text-slate-400" />
                    <span>{client.billing_email}</span>
                  </div>
                )}
                {client.website && (
                  <div className="flex items-center gap-2">
                    <Globe className="w-4 h-4 text-slate-400" />
                    <a
                      href={client.website}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-primary-600 hover:underline"
                    >
                      {client.website.replace(/^https?:\/\//, '')}
                    </a>
                  </div>
                )}
                <div className="flex items-center gap-4">
                  <span className="flex items-center gap-1">
                    <DollarSign className="w-4 h-4 text-slate-400" />
                    {client.currency}
                  </span>
                  <span>{client.project_count || 0} projects</span>
                  <span>{client.contact_count || 0} contacts</span>
                </div>
              </div>

              {/* Primary Contact */}
              {client.primary_contact && (
                <div className="text-sm text-slate-600 mb-4 p-2 bg-slate-50 rounded">
                  <span className="text-slate-400">Primary:</span>{' '}
                  {client.primary_contact.contact_name || client.primary_contact.contact_email}
                </div>
              )}

              {/* Actions */}
              <div className="flex gap-2 pt-3 border-t border-slate-100">
                <Link to={`/clients/${client.id}`} className="flex-1">
                  <Button variant="outline" size="sm" className="w-full" icon={<Eye className="w-4 h-4" />}>
                    View
                  </Button>
                </Link>
                {isOwnerOrAdmin() && (
                  <>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => handleEdit(client)}
                      icon={<Edit2 className="w-4 h-4" />}
                    />
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => handleDelete(client)}
                      icon={<Trash2 className="w-4 h-4 text-red-500" />}
                      disabled={(client.settings as Record<string, unknown>)?.is_default === true}
                    />
                  </>
                )}
              </div>
            </Card>
          ))}
        </div>
      )}

      {/* Create/Edit Modal */}
      <Modal
        isOpen={showCreateModal || showEditModal}
        onClose={() => {
          setShowCreateModal(false);
          setShowEditModal(false);
          setSelectedClient(null);
          resetForm();
        }}
        title={selectedClient ? 'Edit Client' : 'Create Client'}
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <Input
              label="Client Name *"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              placeholder="Acme Corp"
              required
            />
            <Input
              label="Legal Name"
              value={formData.legal_name}
              onChange={(e) => setFormData({ ...formData, legal_name: e.target.value })}
              placeholder="Acme Corporation Inc."
            />
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <Input
              label="Website"
              type="url"
              value={formData.website}
              onChange={(e) => setFormData({ ...formData, website: e.target.value })}
              placeholder="https://acme.com"
            />
            <Input
              label="Industry"
              value={formData.industry}
              onChange={(e) => setFormData({ ...formData, industry: e.target.value })}
              placeholder="Technology"
            />
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Size</label>
              <select
                value={formData.size || ''}
                onChange={(e) => setFormData({ ...formData, size: e.target.value as ClientFormData['size'] })}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"
              >
                <option value="">Select size...</option>
                {CLIENT_SIZES.map((size) => (
                  <option key={size.value} value={size.value}>{size.label}</option>
                ))}
              </select>
            </div>
            <Input
              label="Billing Email"
              type="email"
              value={formData.billing_email}
              onChange={(e) => setFormData({ ...formData, billing_email: e.target.value })}
              placeholder="billing@acme.com"
            />
          </div>

          <Textarea
            label="Billing Address"
            value={formData.billing_address}
            onChange={(e) => setFormData({ ...formData, billing_address: e.target.value })}
            placeholder="123 Main St"
            rows={2}
          />

          <div className="grid gap-4 sm:grid-cols-4">
            <Input
              label="City"
              value={formData.billing_city}
              onChange={(e) => setFormData({ ...formData, billing_city: e.target.value })}
            />
            <Input
              label="State"
              value={formData.billing_state}
              onChange={(e) => setFormData({ ...formData, billing_state: e.target.value })}
            />
            <Input
              label="Postal Code"
              value={formData.billing_postal}
              onChange={(e) => setFormData({ ...formData, billing_postal: e.target.value })}
            />
            <Input
              label="Country"
              value={formData.billing_country}
              onChange={(e) => setFormData({ ...formData, billing_country: e.target.value })}
              placeholder="US"
            />
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Currency</label>
              <select
                value={formData.currency}
                onChange={(e) => setFormData({ ...formData, currency: e.target.value })}
                className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"
              >
                {CURRENCIES.map((curr) => (
                  <option key={curr.value} value={curr.value}>{curr.label}</option>
                ))}
              </select>
            </div>
            <Input
              label="Payment Terms (days)"
              type="number"
              value={formData.payment_terms}
              onChange={(e) => setFormData({ ...formData, payment_terms: parseInt(e.target.value) || 30 })}
              min={0}
            />
          </div>

          <Textarea
            label="Notes"
            value={formData.notes}
            onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
            rows={3}
          />

          <div className="flex gap-3 pt-4">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                setShowCreateModal(false);
                setShowEditModal(false);
                setSelectedClient(null);
                resetForm();
              }}
              className="flex-1"
            >
              Cancel
            </Button>
            <Button
              type="submit"
              loading={createMutation.isPending || updateMutation.isPending}
              className="flex-1"
            >
              {selectedClient ? 'Save Changes' : 'Create Client'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* Delete Confirmation */}
      <ConfirmModal
        isOpen={showDeleteModal}
        onClose={() => {
          setShowDeleteModal(false);
          setSelectedClient(null);
        }}
        onConfirm={() => {
          if (selectedClient) {
            deleteMutation.mutate(selectedClient.id);
          }
        }}
        title="Delete Client"
        message={`Are you sure you want to delete "${selectedClient?.name}"? This action cannot be undone.`}
        confirmText="Delete"
        variant="danger"
        loading={deleteMutation.isPending}
      />
    </Layout>
  );
}

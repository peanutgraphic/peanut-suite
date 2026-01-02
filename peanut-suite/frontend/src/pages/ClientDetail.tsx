import { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  ArrowLeft,
  Building2,
  Users,
  FolderOpen,
  FileText,
  Mail,
  MapPin,
  DollarSign,
  Clock,
  Plus,
  Trash2,
  Star,
  ExternalLink,
  Edit2,
  UserPlus,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Badge, Modal, Input, useToast, ConfirmModal } from '../components/common';
import { clientsApi, contactsApi, invoicesApi } from '../api/endpoints';
import { useAccountStore } from '../store';
import type { ClientContact, ClientContactRole, Contact, Project, Invoice, ClientFormData } from '../types';

const CONTACT_ROLES: { value: ClientContactRole; label: string }[] = [
  { value: 'primary', label: 'Primary Contact' },
  { value: 'billing', label: 'Billing' },
  { value: 'technical', label: 'Technical' },
  { value: 'project_manager', label: 'Project Manager' },
  { value: 'other', label: 'Other' },
];

const ROLE_COLORS: Record<ClientContactRole, 'primary' | 'success' | 'warning' | 'info' | 'default'> = {
  primary: 'primary',
  billing: 'success',
  technical: 'info',
  project_manager: 'warning',
  other: 'default',
};

const STATUS_VARIANTS: Record<string, 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'> = {
  active: 'success',
  inactive: 'warning',
  archived: 'default',
  // Invoice statuses
  draft: 'default',
  sent: 'primary',
  viewed: 'info',
  partial: 'warning',
  paid: 'success',
  overdue: 'danger',
  cancelled: 'default',
  // Project statuses
  planning: 'info',
  in_progress: 'primary',
  on_hold: 'warning',
  completed: 'success',
};

const CLIENT_SIZES: Record<string, string> = {
  solo: 'Solo / Freelancer',
  small: 'Small (1-10)',
  medium: 'Medium (11-50)',
  large: 'Large (51-200)',
  enterprise: 'Enterprise (200+)',
};

const CURRENCIES = [
  { value: 'USD', label: 'USD - US Dollar' },
  { value: 'EUR', label: 'EUR - Euro' },
  { value: 'GBP', label: 'GBP - British Pound' },
  { value: 'CAD', label: 'CAD - Canadian Dollar' },
  { value: 'AUD', label: 'AUD - Australian Dollar' },
];

export default function ClientDetail() {
  const { id } = useParams();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { isOwnerOrAdmin } = useAccountStore();

  const [activeTab, setActiveTab] = useState<'overview' | 'contacts' | 'projects' | 'invoices'>('overview');
  const [showEditModal, setShowEditModal] = useState(false);
  const [showAddContactModal, setShowAddContactModal] = useState(false);
  const [showRemoveContactModal, setShowRemoveContactModal] = useState(false);
  const [selectedContact, setSelectedContact] = useState<ClientContact | null>(null);
  const [contactSearch, setContactSearch] = useState('');
  const [selectedNewContactId, setSelectedNewContactId] = useState<number | null>(null);
  const [selectedRole, setSelectedRole] = useState<ClientContactRole>('primary');

  // Form state for edit
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

  // Fetch client
  const { data: client, isLoading } = useQuery({
    queryKey: ['client', id],
    queryFn: () => clientsApi.getById(Number(id)),
  });

  // Fetch client stats
  const { data: stats } = useQuery({
    queryKey: ['client-stats', id],
    queryFn: () => clientsApi.getStats(Number(id)),
    enabled: !!id,
  });

  // Fetch client contacts
  const { data: clientContacts = [] } = useQuery({
    queryKey: ['client-contacts', id],
    queryFn: () => clientsApi.getContacts(Number(id)),
    enabled: !!id && activeTab === 'contacts',
  });

  // Fetch client projects
  const { data: projects = [] } = useQuery({
    queryKey: ['client-projects', id],
    queryFn: () => clientsApi.getProjects(Number(id)),
    enabled: !!id && activeTab === 'projects',
  });

  // Fetch invoices for client - using getAll with filter
  const { data: invoicesData } = useQuery({
    queryKey: ['client-invoices', id],
    queryFn: () => invoicesApi.getAll({ project_id: undefined }), // Will need backend support for client_id filter
    enabled: !!id && activeTab === 'invoices',
  });

  // Fetch all contacts for adding
  const { data: allContactsData } = useQuery({
    queryKey: ['contacts', { search: contactSearch }],
    queryFn: () => contactsApi.getAll({ search: contactSearch || undefined }),
    enabled: showAddContactModal,
  });
  const allContacts: Contact[] = allContactsData?.data ?? [];

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: (data: Partial<ClientFormData>) => clientsApi.update(Number(id), data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['client', id] });
      queryClient.invalidateQueries({ queryKey: ['clients'] });
      setShowEditModal(false);
      toast.success('Client updated');
    },
    onError: (error: Error) => {
      toast.error(error.message);
    },
  });

  // Add contact mutation
  const addContactMutation = useMutation({
    mutationFn: ({ contactId, role }: { contactId: number; role: ClientContactRole }) =>
      clientsApi.addContact(Number(id), contactId, role),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['client-contacts', id] });
      queryClient.invalidateQueries({ queryKey: ['client', id] });
      setShowAddContactModal(false);
      setSelectedNewContactId(null);
      setSelectedRole('primary');
      setContactSearch('');
      toast.success('Contact added to client');
    },
    onError: (error: Error) => {
      toast.error(error.message);
    },
  });

  // Remove contact mutation
  const removeContactMutation = useMutation({
    mutationFn: (contactId: number) => clientsApi.removeContact(Number(id), contactId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['client-contacts', id] });
      queryClient.invalidateQueries({ queryKey: ['client', id] });
      setShowRemoveContactModal(false);
      setSelectedContact(null);
      toast.success('Contact removed from client');
    },
    onError: (error: Error) => {
      toast.error(error.message);
    },
  });

  // Update contact role mutation
  const updateRoleMutation = useMutation({
    mutationFn: ({ contactId, role }: { contactId: number; role: ClientContactRole }) =>
      clientsApi.updateContactRole(Number(id), contactId, role),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['client-contacts', id] });
      toast.success('Contact role updated');
    },
    onError: (error: Error) => {
      toast.error(error.message);
    },
  });

  // Set primary contact mutation
  const setPrimaryMutation = useMutation({
    mutationFn: (contactId: number) => clientsApi.setPrimaryContact(Number(id), contactId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['client-contacts', id] });
      queryClient.invalidateQueries({ queryKey: ['client', id] });
      toast.success('Primary contact updated');
    },
    onError: (error: Error) => {
      toast.error(error.message);
    },
  });

  const handleEditOpen = () => {
    if (client) {
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
    }
  };

  // Get contacts not already linked
  const availableContacts = allContacts.filter(
    (c) => !clientContacts.some((cc) => cc.contact_id === c.id)
  );

  // Filter invoices by projects belonging to this client
  const clientInvoices = invoicesData?.items?.filter((inv: Invoice) =>
    projects.some((p: Project) => p.id === inv.project_id)
  ) || [];

  if (isLoading) {
    return (
      <Layout title="Loading..." description="">
        <div className="animate-pulse space-y-4">
          <div className="h-10 bg-slate-200 rounded w-1/3" />
          <div className="h-64 bg-slate-200 rounded" />
        </div>
      </Layout>
    );
  }

  if (!client) {
    return (
      <Layout title="Client Not Found" description="">
        <Card>
          <p className="text-slate-600 mb-4">The requested client could not be found.</p>
          <Link to="/clients" className="text-primary-600 hover:underline">
            Back to Clients
          </Link>
        </Card>
      </Layout>
    );
  }

  const isDefaultClient = (client.settings as Record<string, unknown>)?.is_default === true;

  return (
    <Layout title={client.name} description={client.industry || 'Client Details'}>
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <Link to="/clients">
          <Button variant="outline" icon={<ArrowLeft className="w-4 h-4" />}>
            Back to Clients
          </Button>
        </Link>
        <div className="flex items-center gap-3">
          {client.website && (
            <a href={client.website} target="_blank" rel="noopener noreferrer">
              <Button variant="outline" icon={<ExternalLink className="w-4 h-4" />}>
                Visit Website
              </Button>
            </a>
          )}
          {isOwnerOrAdmin() && !isDefaultClient && (
            <Button onClick={handleEditOpen} icon={<Edit2 className="w-4 h-4" />}>
              Edit Client
            </Button>
          )}
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
              <FolderOpen className="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">{stats?.project_count || 0}</p>
              <p className="text-sm text-slate-500">Projects</p>
            </div>
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
              <Users className="w-5 h-5 text-green-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">{stats?.contact_count || 0}</p>
              <p className="text-sm text-slate-500">Contacts</p>
            </div>
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
              <DollarSign className="w-5 h-5 text-purple-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">
                {new Intl.NumberFormat('en-US', {
                  style: 'currency',
                  currency: client.currency || 'USD',
                  minimumFractionDigits: 0,
                  maximumFractionDigits: 0,
                }).format(stats?.total_revenue || 0)}
              </p>
              <p className="text-sm text-slate-500">Total Revenue</p>
            </div>
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
              <FileText className="w-5 h-5 text-amber-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-slate-900">
                {new Intl.NumberFormat('en-US', {
                  style: 'currency',
                  currency: client.currency || 'USD',
                  minimumFractionDigits: 0,
                  maximumFractionDigits: 0,
                }).format(stats?.outstanding_balance || 0)}
              </p>
              <p className="text-sm text-slate-500">Outstanding</p>
            </div>
          </div>
        </Card>
      </div>

      {/* Tabs */}
      <div className="flex gap-1 bg-slate-100 p-1 rounded-lg mb-6">
        {[
          { id: 'overview', label: 'Overview', icon: Building2 },
          { id: 'contacts', label: 'Contacts', icon: Users },
          { id: 'projects', label: 'Projects', icon: FolderOpen },
          { id: 'invoices', label: 'Invoices', icon: FileText },
        ].map((tab) => (
          <button
            key={tab.id}
            onClick={() => setActiveTab(tab.id as typeof activeTab)}
            className={`flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors ${
              activeTab === tab.id
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            <tab.icon className="w-4 h-4" />
            {tab.label}
          </button>
        ))}
      </div>

      {/* Tab Content */}
      {activeTab === 'overview' && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* Company Info */}
          <Card>
            <h3 className="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
              <Building2 className="w-5 h-5" />
              Company Information
            </h3>
            <dl className="space-y-3">
              <div className="flex justify-between">
                <dt className="text-slate-500">Name</dt>
                <dd className="font-medium text-slate-900">{client.name}</dd>
              </div>
              {client.legal_name && (
                <div className="flex justify-between">
                  <dt className="text-slate-500">Legal Name</dt>
                  <dd className="font-medium text-slate-900">{client.legal_name}</dd>
                </div>
              )}
              {client.industry && (
                <div className="flex justify-between">
                  <dt className="text-slate-500">Industry</dt>
                  <dd className="font-medium text-slate-900">{client.industry}</dd>
                </div>
              )}
              {client.size && (
                <div className="flex justify-between">
                  <dt className="text-slate-500">Size</dt>
                  <dd className="font-medium text-slate-900">{CLIENT_SIZES[client.size] || client.size}</dd>
                </div>
              )}
              {client.website && (
                <div className="flex justify-between">
                  <dt className="text-slate-500">Website</dt>
                  <dd>
                    <a
                      href={client.website}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-primary-600 hover:underline flex items-center gap-1"
                    >
                      {client.website.replace(/^https?:\/\//, '')}
                      <ExternalLink className="w-3 h-3" />
                    </a>
                  </dd>
                </div>
              )}
              <div className="flex justify-between items-center">
                <dt className="text-slate-500">Status</dt>
                <dd>
                  <Badge variant={STATUS_VARIANTS[client.status] || 'default'}>
                    {client.status}
                  </Badge>
                </dd>
              </div>
            </dl>
          </Card>

          {/* Billing Info */}
          <Card>
            <h3 className="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
              <DollarSign className="w-5 h-5" />
              Billing Information
            </h3>
            <dl className="space-y-3">
              {client.billing_email && (
                <div className="flex justify-between">
                  <dt className="text-slate-500 flex items-center gap-1">
                    <Mail className="w-4 h-4" />
                    Email
                  </dt>
                  <dd className="font-medium text-slate-900">
                    <a href={`mailto:${client.billing_email}`} className="text-primary-600 hover:underline">
                      {client.billing_email}
                    </a>
                  </dd>
                </div>
              )}
              {(client.billing_address || client.billing_city) && (
                <div className="flex justify-between items-start">
                  <dt className="text-slate-500 flex items-center gap-1">
                    <MapPin className="w-4 h-4" />
                    Address
                  </dt>
                  <dd className="font-medium text-slate-900 text-right">
                    {client.billing_address && <div>{client.billing_address}</div>}
                    {(client.billing_city || client.billing_state || client.billing_postal) && (
                      <div>
                        {[client.billing_city, client.billing_state, client.billing_postal]
                          .filter(Boolean)
                          .join(', ')}
                      </div>
                    )}
                    {client.billing_country && <div>{client.billing_country}</div>}
                  </dd>
                </div>
              )}
              <div className="flex justify-between">
                <dt className="text-slate-500">Currency</dt>
                <dd className="font-medium text-slate-900">{client.currency || 'USD'}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-slate-500 flex items-center gap-1">
                  <Clock className="w-4 h-4" />
                  Payment Terms
                </dt>
                <dd className="font-medium text-slate-900">{client.payment_terms || 30} days</dd>
              </div>
              {client.tax_id && (
                <div className="flex justify-between">
                  <dt className="text-slate-500">Tax ID</dt>
                  <dd className="font-medium text-slate-900">{client.tax_id}</dd>
                </div>
              )}
            </dl>
          </Card>

          {/* Notes */}
          {client.notes && (
            <Card className="md:col-span-2">
              <h3 className="text-lg font-semibold text-slate-900 mb-4">Notes</h3>
              <p className="text-slate-600 whitespace-pre-wrap">{client.notes}</p>
            </Card>
          )}

          {/* Primary Contact */}
          {client.primary_contact && (
            <Card className="md:col-span-2">
              <h3 className="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <Star className="w-5 h-5 text-amber-500" />
                Primary Contact
              </h3>
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
                  <Users className="w-6 h-6 text-primary-600" />
                </div>
                <div>
                  <p className="font-medium text-slate-900">
                    {client.primary_contact.contact_name || 'Unknown'}
                  </p>
                  {client.primary_contact.contact_email && (
                    <a
                      href={`mailto:${client.primary_contact.contact_email}`}
                      className="text-sm text-primary-600 hover:underline"
                    >
                      {client.primary_contact.contact_email}
                    </a>
                  )}
                  {client.primary_contact.title && (
                    <p className="text-sm text-slate-500">{client.primary_contact.title}</p>
                  )}
                </div>
              </div>
            </Card>
          )}
        </div>
      )}

      {activeTab === 'contacts' && (
        <Card>
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-lg font-semibold text-slate-900">Client Contacts</h3>
            {isOwnerOrAdmin() && (
              <Button
                onClick={() => setShowAddContactModal(true)}
                icon={<UserPlus className="w-4 h-4" />}
                size="sm"
              >
                Add Contact
              </Button>
            )}
          </div>

          {clientContacts.length === 0 ? (
            <div className="text-center py-8">
              <Users className="w-12 h-12 text-slate-300 mx-auto mb-2" />
              <p className="text-slate-500">No contacts linked to this client yet.</p>
            </div>
          ) : (
            <div className="divide-y divide-slate-100">
              {clientContacts.map((cc) => (
                <div key={cc.id} className="py-4 flex items-center justify-between">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                      {cc.is_primary ? (
                        <Star className="w-5 h-5 text-amber-500" />
                      ) : (
                        <Users className="w-5 h-5 text-slate-400" />
                      )}
                    </div>
                    <div>
                      <p className="font-medium text-slate-900">
                        {cc.contact_name || cc.contact_email}
                      </p>
                      {cc.contact_email && cc.contact_name && (
                        <p className="text-sm text-slate-500">{cc.contact_email}</p>
                      )}
                      {cc.title && (
                        <p className="text-sm text-slate-400">{cc.title}</p>
                      )}
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    {isOwnerOrAdmin() ? (
                      <select
                        value={cc.role}
                        onChange={(e) =>
                          updateRoleMutation.mutate({
                            contactId: cc.contact_id,
                            role: e.target.value as ClientContactRole,
                          })
                        }
                        className="text-sm border border-slate-200 rounded-lg px-2 py-1"
                      >
                        {CONTACT_ROLES.map((role) => (
                          <option key={role.value} value={role.value}>
                            {role.label}
                          </option>
                        ))}
                      </select>
                    ) : (
                      <Badge variant={ROLE_COLORS[cc.role] || 'default'}>
                        {CONTACT_ROLES.find((r) => r.value === cc.role)?.label || cc.role}
                      </Badge>
                    )}
                    {isOwnerOrAdmin() && (
                      <div className="flex gap-1">
                        {!cc.is_primary && (
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setPrimaryMutation.mutate(cc.contact_id)}
                            loading={setPrimaryMutation.isPending}
                            title="Set as primary contact"
                          >
                            <Star className="w-4 h-4" />
                          </Button>
                        )}
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => {
                            setSelectedContact(cc);
                            setShowRemoveContactModal(true);
                          }}
                        >
                          <Trash2 className="w-4 h-4 text-red-500" />
                        </Button>
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>
      )}

      {activeTab === 'projects' && (
        <Card>
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-lg font-semibold text-slate-900">Projects</h3>
            <Link to="/projects">
              <Button variant="outline" size="sm" icon={<Plus className="w-4 h-4" />}>
                View All Projects
              </Button>
            </Link>
          </div>

          {projects.length === 0 ? (
            <div className="text-center py-8">
              <FolderOpen className="w-12 h-12 text-slate-300 mx-auto mb-2" />
              <p className="text-slate-500">No projects for this client yet.</p>
            </div>
          ) : (
            <div className="divide-y divide-slate-100">
              {(projects as Project[]).map((project) => (
                <div key={project.id} className="py-4 flex items-center justify-between">
                  <div>
                    <p className="font-medium text-slate-900">{project.name}</p>
                    {project.description && (
                      <p className="text-sm text-slate-500 line-clamp-1">{project.description}</p>
                    )}
                  </div>
                  <div className="flex items-center gap-3">
                    <Badge variant={STATUS_VARIANTS[project.status] || 'default'}>
                      {project.status.replace('_', ' ')}
                    </Badge>
                    <Link to={`/projects`}>
                      <Button variant="outline" size="sm">View</Button>
                    </Link>
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>
      )}

      {activeTab === 'invoices' && (
        <Card>
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-lg font-semibold text-slate-900">Invoices</h3>
            <Link to="/finance/invoices">
              <Button variant="outline" size="sm" icon={<Plus className="w-4 h-4" />}>
                View All Invoices
              </Button>
            </Link>
          </div>

          {clientInvoices.length === 0 ? (
            <div className="text-center py-8">
              <FileText className="w-12 h-12 text-slate-300 mx-auto mb-2" />
              <p className="text-slate-500">No invoices for this client yet.</p>
            </div>
          ) : (
            <div className="divide-y divide-slate-100">
              {clientInvoices.map((invoice: Invoice) => (
                <div key={invoice.id} className="py-4 flex items-center justify-between">
                  <div>
                    <p className="font-medium text-slate-900">{invoice.invoice_number}</p>
                    <p className="text-sm text-slate-500">
                      {invoice.issue_date
                        ? new Date(invoice.issue_date).toLocaleDateString()
                        : 'Draft'}
                    </p>
                  </div>
                  <div className="flex items-center gap-4">
                    <div className="text-right">
                      <p className="font-medium text-slate-900">
                        {new Intl.NumberFormat('en-US', {
                          style: 'currency',
                          currency: invoice.currency || 'USD',
                        }).format(invoice.total)}
                      </p>
                      {invoice.balance_due > 0 && invoice.balance_due !== invoice.total && (
                        <p className="text-sm text-amber-600">
                          Due: {new Intl.NumberFormat('en-US', {
                            style: 'currency',
                            currency: invoice.currency || 'USD',
                          }).format(invoice.balance_due)}
                        </p>
                      )}
                    </div>
                    <Badge variant={STATUS_VARIANTS[invoice.status] || 'default'}>
                      {invoice.status}
                    </Badge>
                    <Link to={`/finance/invoices/${invoice.id}/edit`}>
                      <Button variant="outline" size="sm">View</Button>
                    </Link>
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>
      )}

      {/* Edit Client Modal */}
      <Modal
        isOpen={showEditModal}
        onClose={() => setShowEditModal(false)}
        title="Edit Client"
      >
        <form
          onSubmit={(e) => {
            e.preventDefault();
            updateMutation.mutate(formData);
          }}
          className="space-y-4"
        >
          <div className="grid gap-4 sm:grid-cols-2">
            <Input
              label="Client Name *"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              required
            />
            <Input
              label="Legal Name"
              value={formData.legal_name}
              onChange={(e) => setFormData({ ...formData, legal_name: e.target.value })}
            />
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <Input
              label="Website"
              type="url"
              value={formData.website}
              onChange={(e) => setFormData({ ...formData, website: e.target.value })}
            />
            <Input
              label="Industry"
              value={formData.industry}
              onChange={(e) => setFormData({ ...formData, industry: e.target.value })}
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
                {Object.entries(CLIENT_SIZES).map(([value, label]) => (
                  <option key={value} value={value}>{label}</option>
                ))}
              </select>
            </div>
            <Input
              label="Billing Email"
              type="email"
              value={formData.billing_email}
              onChange={(e) => setFormData({ ...formData, billing_email: e.target.value })}
            />
          </div>

          <Input
            label="Billing Address"
            value={formData.billing_address}
            onChange={(e) => setFormData({ ...formData, billing_address: e.target.value })}
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

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Notes</label>
            <textarea
              value={formData.notes}
              onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
              rows={3}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm resize-none"
            />
          </div>

          <div className="flex gap-3 pt-4">
            <Button
              type="button"
              variant="outline"
              onClick={() => setShowEditModal(false)}
              className="flex-1"
            >
              Cancel
            </Button>
            <Button type="submit" loading={updateMutation.isPending} className="flex-1">
              Save Changes
            </Button>
          </div>
        </form>
      </Modal>

      {/* Add Contact Modal */}
      <Modal
        isOpen={showAddContactModal}
        onClose={() => {
          setShowAddContactModal(false);
          setSelectedNewContactId(null);
          setSelectedRole('primary');
          setContactSearch('');
        }}
        title="Add Contact to Client"
      >
        <div className="space-y-4">
          <Input
            label="Search Contacts"
            placeholder="Search by name or email..."
            value={contactSearch}
            onChange={(e) => setContactSearch(e.target.value)}
          />

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Role</label>
            <select
              value={selectedRole}
              onChange={(e) => setSelectedRole(e.target.value as ClientContactRole)}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"
            >
              {CONTACT_ROLES.map((role) => (
                <option key={role.value} value={role.value}>{role.label}</option>
              ))}
            </select>
          </div>

          <div className="max-h-60 overflow-y-auto border border-slate-200 rounded-lg">
            {availableContacts.length === 0 ? (
              <div className="p-4 text-center text-slate-500">
                {contactSearch ? 'No contacts found' : 'All contacts are already linked'}
              </div>
            ) : (
              <div className="divide-y divide-slate-100">
                {availableContacts.map((contact) => (
                  <button
                    key={contact.id}
                    type="button"
                    onClick={() => setSelectedNewContactId(contact.id)}
                    className={`w-full p-3 text-left hover:bg-slate-50 flex items-center gap-3 ${
                      selectedNewContactId === contact.id ? 'bg-primary-50' : ''
                    }`}
                  >
                    <div className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                      <Users className="w-4 h-4 text-slate-400" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-slate-900 truncate">
                        {contact.first_name && contact.last_name
                          ? `${contact.first_name} ${contact.last_name}`
                          : contact.email}
                      </p>
                      <p className="text-sm text-slate-500 truncate">{contact.email}</p>
                    </div>
                    {selectedNewContactId === contact.id && (
                      <div className="w-5 h-5 rounded-full bg-primary-600 flex items-center justify-center">
                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      </div>
                    )}
                  </button>
                ))}
              </div>
            )}
          </div>

          <div className="flex gap-3 pt-4">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                setShowAddContactModal(false);
                setSelectedNewContactId(null);
                setSelectedRole('primary');
                setContactSearch('');
              }}
              className="flex-1"
            >
              Cancel
            </Button>
            <Button
              onClick={() => {
                if (selectedNewContactId) {
                  addContactMutation.mutate({
                    contactId: selectedNewContactId,
                    role: selectedRole,
                  });
                }
              }}
              loading={addContactMutation.isPending}
              disabled={!selectedNewContactId}
              className="flex-1"
            >
              Add Contact
            </Button>
          </div>
        </div>
      </Modal>

      {/* Remove Contact Confirmation */}
      <ConfirmModal
        isOpen={showRemoveContactModal}
        onClose={() => {
          setShowRemoveContactModal(false);
          setSelectedContact(null);
        }}
        onConfirm={() => {
          if (selectedContact) {
            removeContactMutation.mutate(selectedContact.contact_id);
          }
        }}
        title="Remove Contact"
        message={`Are you sure you want to remove "${selectedContact?.contact_name || selectedContact?.contact_email}" from this client?`}
        confirmText="Remove"
        variant="danger"
        loading={removeContactMutation.isPending}
      />
    </Layout>
  );
}

import { useState, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { createColumnHelper } from '@tanstack/react-table';
import {
  Plus,
  Trash2,
  Mail,
  Search,
  Filter,
  Download,
  Upload,
  User,
  FileUp,
  AlertCircle,
  Building2,
} from 'lucide-react';
import { Layout } from '../components/layout';
import {
  Card,
  Button,
  Input,
  Table,
  Pagination,
  Badge,
  StatusBadge,
  Modal,
  ConfirmModal,
  Select,
  createCheckboxColumn,
  InfoTooltip,
  SampleDataBanner,
  useToast,
  ProjectSelector,
} from '../components/common';
import { contactsApi, clientsApi } from '../api/endpoints';
import type { Contact, ContactStatus } from '../types';
import { useFilterStore, useProjectStore } from '../store';
import { exportToCSV, contactsExportColumns } from '../utils';
import { helpContent, pageDescriptions, sampleContacts } from '../constants';

const columnHelper = createColumnHelper<Contact>();

const statusOptions = [
  { value: 'lead', label: 'Lead' },
  { value: 'contacted', label: 'Contacted' },
  { value: 'qualified', label: 'Qualified' },
  { value: 'customer', label: 'Customer' },
  { value: 'churned', label: 'Churned' },
];

export default function Contacts() {
  const queryClient = useQueryClient();
  const toast = useToast();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [page, setPage] = useState(1);
  const [selectedRows, setSelectedRows] = useState<Record<string, boolean>>({});
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const [bulkDeleteModalOpen, setBulkDeleteModalOpen] = useState(false);
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [importModalOpen, setImportModalOpen] = useState(false);
  const [importFile, setImportFile] = useState<File | null>(null);
  const [isImporting, setIsImporting] = useState(false);

  // Get current project
  const { currentProject } = useProjectStore();

  const [newContact, setNewContact] = useState<{
    email: string;
    first_name: string;
    last_name: string;
    phone: string;
    company: string;
    status: ContactStatus;
    project_id: number | null;
  }>({
    email: '',
    first_name: '',
    last_name: '',
    phone: '',
    company: '',
    status: 'lead',
    project_id: null,
  });

  const { contactFilters, setContactFilter, resetContactFilters } = useFilterStore();
  const [showSampleData, setShowSampleData] = useState(true);
  const [clientFilter, setClientFilter] = useState<number | null>(null);

  // Fetch clients for filter
  const { data: clients = [] } = useQuery({
    queryKey: ['clients', { status: 'active' }],
    queryFn: () => clientsApi.getAll({ status: 'active' }),
  });

  const { data, isLoading } = useQuery({
    queryKey: ['contacts', page, contactFilters],
    queryFn: () =>
      contactsApi.getAll({
        page,
        per_page: 20,
        search: contactFilters.search || undefined,
        status: contactFilters.status || undefined,
        source: contactFilters.source || undefined,
      }),
  });

  const createMutation = useMutation({
    mutationFn: contactsApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['contacts'] });
      setCreateModalOpen(false);
      setNewContact({
        email: '',
        first_name: '',
        last_name: '',
        phone: '',
        company: '',
        status: 'lead',
        project_id: null,
      });
      toast.success('Contact created successfully');
    },
    onError: () => {
      toast.error('Failed to create contact');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: contactsApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['contacts'] });
      setDeleteId(null);
    },
  });

  const bulkDeleteMutation = useMutation({
    mutationFn: contactsApi.bulkDelete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['contacts'] });
      setBulkDeleteModalOpen(false);
      setSelectedRows({});
      toast.success('Contacts deleted successfully');
    },
    onError: () => {
      toast.error('Failed to delete contacts');
    },
  });

  // Determine if we should show sample data
  const hasNoRealData = !isLoading && (!data?.data || data.data.length === 0);
  const displaySampleData = hasNoRealData && showSampleData;
  const displayData = displaySampleData ? sampleContacts : (data?.data || []);

  const handleCreate = () => {
    const projectId = newContact.project_id ?? currentProject?.id;
    if (!projectId) {
      toast.error('Please select a project');
      return;
    }
    createMutation.mutate({
      email: newContact.email,
      first_name: newContact.first_name || undefined,
      last_name: newContact.last_name || undefined,
      phone: newContact.phone || undefined,
      company: newContact.company || undefined,
      status: newContact.status,
      project_id: projectId,
    });
  };

  const handleBulkDelete = () => {
    const contacts = data?.data || [];
    const selectedIds = Object.entries(selectedRows)
      .filter(([_, isSelected]) => isSelected)
      .map(([index]) => contacts[parseInt(index, 10)]?.id)
      .filter((id): id is number => id !== undefined);
    if (selectedIds.length > 0) {
      bulkDeleteMutation.mutate(selectedIds);
    }
  };

  const handleImport = async () => {
    if (!importFile) return;

    const projectId = currentProject?.id;
    if (!projectId) {
      toast.error('Please select a project before importing');
      return;
    }

    setIsImporting(true);
    try {
      const text = await importFile.text();
      const lines = text.split('\n').filter(line => line.trim());

      if (lines.length < 2) {
        toast.error('CSV file is empty or missing header row');
        setIsImporting(false);
        return;
      }

      const headers = lines[0].split(',').map(h => h.trim().toLowerCase());
      const emailIndex = headers.findIndex(h => h === 'email');

      if (emailIndex === -1) {
        toast.error('CSV must have an "email" column');
        setIsImporting(false);
        return;
      }

      const firstNameIndex = headers.findIndex(h => h === 'first_name' || h === 'firstname');
      const lastNameIndex = headers.findIndex(h => h === 'last_name' || h === 'lastname');
      const companyIndex = headers.findIndex(h => h === 'company');
      const phoneIndex = headers.findIndex(h => h === 'phone');

      let imported = 0;
      let errors = 0;

      for (let i = 1; i < lines.length; i++) {
        const values = lines[i].split(',').map(v => v.trim().replace(/^["']|["']$/g, ''));
        const email = values[emailIndex];

        if (!email || !email.includes('@')) {
          errors++;
          continue;
        }

        try {
          await contactsApi.create({
            email,
            first_name: firstNameIndex >= 0 ? values[firstNameIndex] : undefined,
            last_name: lastNameIndex >= 0 ? values[lastNameIndex] : undefined,
            company: companyIndex >= 0 ? values[companyIndex] : undefined,
            phone: phoneIndex >= 0 ? values[phoneIndex] : undefined,
            status: 'lead',
            project_id: projectId,
          });
          imported++;
        } catch {
          errors++;
        }
      }

      queryClient.invalidateQueries({ queryKey: ['contacts'] });
      setImportModalOpen(false);
      setImportFile(null);

      if (errors > 0) {
        toast.success(`Imported ${imported} contacts (${errors} failed)`);
      } else {
        toast.success(`Imported ${imported} contacts successfully`);
      }
    } catch {
      toast.error('Failed to parse CSV file');
    } finally {
      setIsImporting(false);
    }
  };

  const columns = [
    createCheckboxColumn<Contact>(),
    columnHelper.accessor('email', {
      header: 'Contact',
      cell: (info) => (
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
            <User className="w-4 h-4 text-primary-600" />
          </div>
          <div>
            <p className="font-medium text-slate-900">
              {info.row.original.first_name || info.row.original.last_name
                ? `${info.row.original.first_name || ''} ${info.row.original.last_name || ''}`.trim()
                : 'No name'}
            </p>
            <p className="text-sm text-slate-500">{info.getValue()}</p>
          </div>
        </div>
      ),
    }),
    columnHelper.accessor('company', {
      header: 'Company',
      cell: (info) => (
        <span className="text-slate-700">{info.getValue() || '-'}</span>
      ),
    }),
    columnHelper.accessor('client_names', {
      header: 'Clients',
      cell: (info) => {
        const clientNames = info.getValue() || [];
        return clientNames.length > 0 ? (
          <div className="flex items-center gap-1">
            <Building2 className="w-3.5 h-3.5 text-slate-400" />
            <span className="text-sm text-slate-600 truncate max-w-[120px]">
              {clientNames.slice(0, 2).join(', ')}
              {clientNames.length > 2 && ` +${clientNames.length - 2}`}
            </span>
          </div>
        ) : (
          <span className="text-slate-400">-</span>
        );
      },
    }),
    columnHelper.accessor('status', {
      header: 'Status',
      cell: (info) => <StatusBadge status={info.getValue()} />,
    }),
    columnHelper.accessor('source', {
      header: 'Source',
      cell: (info) => (
        <Badge variant="default">{info.getValue() || 'direct'}</Badge>
      ),
    }),
    columnHelper.accessor('tags', {
      header: 'Tags',
      cell: (info) => {
        const tags = info.getValue() || [];
        return tags.length > 0 ? (
          <div className="flex gap-1">
            {tags.slice(0, 2).map((tag) => (
              <Badge key={tag} variant="primary" size="sm">
                {tag}
              </Badge>
            ))}
            {tags.length > 2 && (
              <Badge variant="default" size="sm">
                +{tags.length - 2}
              </Badge>
            )}
          </div>
        ) : (
          <span className="text-slate-400">-</span>
        );
      },
    }),
    columnHelper.accessor('created_at', {
      header: 'Added',
      cell: (info) => (
        <span className="text-slate-500">
          {new Date(info.getValue()).toLocaleDateString()}
        </span>
      ),
    }),
    columnHelper.display({
      id: 'actions',
      header: '',
      cell: (info) => (
        <div className="flex items-center gap-1">
          <a
            href={`mailto:${info.row.original.email}`}
            className="p-1.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
            title="Send email"
          >
            <Mail className="w-4 h-4" />
          </a>
          <button
            onClick={() => setDeleteId(info.row.original.id)}
            className="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
            title="Delete"
          >
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      ),
    }),
  ];

  const selectedCount = Object.values(selectedRows).filter(Boolean).length;

  const pageInfo = pageDescriptions.contacts;
  const helpContent2 = { howTo: pageInfo.howTo, tips: pageInfo.tips, useCases: pageInfo.useCases };

  return (
    <Layout title={pageInfo.title} description={pageInfo.description} helpContent={helpContent2} pageGuideId="contacts">
      {/* Header Actions */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-4">
          <Input
            type="text"
            placeholder="Search contacts..."
            leftIcon={<Search className="w-4 h-4" />}
            value={contactFilters.search}
            onChange={(e) => setContactFilter('search', e.target.value)}
            fullWidth={false}
            className="w-64"
          />
          <Button
            variant="outline"
            size="sm"
            icon={<Filter className="w-4 h-4" />}
            onClick={resetContactFilters}
          >
            Clear Filters
          </Button>
        </div>

        <div className="flex items-center gap-3">
          {selectedCount > 0 && (
            <Button
              variant="danger"
              size="sm"
              icon={<Trash2 className="w-4 h-4" />}
              onClick={() => setBulkDeleteModalOpen(true)}
            >
              Delete ({selectedCount})
            </Button>
          )}
          <Button
            variant="outline"
            size="sm"
            icon={<Download className="w-4 h-4" />}
            onClick={() => displayData.length > 0 && exportToCSV(displayData, contactsExportColumns, 'contacts')}
            disabled={displayData.length === 0}
          >
            Export CSV
          </Button>
          <Button
            variant="outline"
            size="sm"
            icon={<Upload className="w-4 h-4" />}
            onClick={() => setImportModalOpen(true)}
          >
            Import
          </Button>
          <Button
            icon={<Plus className="w-4 h-4" />}
            onClick={() => setCreateModalOpen(true)}
          >
            Add Contact
          </Button>
        </div>
      </div>

      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Filters */}
      <Card className="mb-6">
        <div className="flex flex-wrap gap-4">
          <div className="flex-1 min-w-[150px]">
            <label className="flex items-center gap-1 text-xs font-medium text-slate-500 mb-1">
              Client
            </label>
            <select
              className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
              value={clientFilter ?? ''}
              onChange={(e) => setClientFilter(e.target.value ? Number(e.target.value) : null)}
            >
              <option value="">All Clients</option>
              {clients.map((client) => (
                <option key={client.id} value={client.id}>
                  {client.name}
                </option>
              ))}
            </select>
          </div>
          <div className="flex-1 min-w-[150px]">
            <label className="flex items-center gap-1 text-xs font-medium text-slate-500 mb-1">
              Status
              <InfoTooltip content={helpContent.contacts.status} />
            </label>
            <select
              className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
              value={contactFilters.status}
              onChange={(e) => setContactFilter('status', e.target.value)}
            >
              <option value="">All Statuses</option>
              {statusOptions.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>
          </div>
          <div className="flex-1 min-w-[150px]">
            <label className="flex items-center gap-1 text-xs font-medium text-slate-500 mb-1">
              Source
              <InfoTooltip content={helpContent.contacts.source} />
            </label>
            <select
              className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
              value={contactFilters.source}
              onChange={(e) => setContactFilter('source', e.target.value)}
            >
              <option value="">All Sources</option>
              <option value="popup">Popup</option>
              <option value="form">Form</option>
              <option value="import">Import</option>
              <option value="manual">Manual</option>
            </select>
          </div>
          <div className="flex-1 min-w-[150px]">
            <label className="flex items-center gap-1 text-xs font-medium text-slate-500 mb-1">
              Tag
              <InfoTooltip content={helpContent.contacts.tags} />
            </label>
            <select
              className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
              value={contactFilters.tag}
              onChange={(e) => setContactFilter('tag', e.target.value)}
            >
              <option value="">All Tags</option>
            </select>
          </div>
        </div>
      </Card>

      {/* Table */}
      <Card>
        <Table
          data={displayData}
          columns={columns}
          loading={isLoading}
          rowSelection={selectedRows}
          onRowSelectionChange={setSelectedRows}
        />
        {data && data.total_pages > 1 && (
          <Pagination
            page={page}
            totalPages={data.total_pages}
            total={data.total}
            perPage={20}
            onPageChange={setPage}
          />
        )}
      </Card>

      {/* Create Modal */}
      <Modal
        isOpen={createModalOpen}
        onClose={() => setCreateModalOpen(false)}
        title="Add Contact"
        size="md"
      >
        <div className="space-y-4">
          <ProjectSelector
            value={newContact.project_id}
            onChange={(projectId) => setNewContact({ ...newContact, project_id: projectId })}
            required
          />
          <Input
            label="Email"
            type="email"
            placeholder="contact@example.com"
            value={newContact.email}
            onChange={(e) => setNewContact({ ...newContact, email: e.target.value })}
            required
          />
          <div className="grid grid-cols-2 gap-4">
            <Input
              label="First Name"
              placeholder="John"
              value={newContact.first_name}
              onChange={(e) => setNewContact({ ...newContact, first_name: e.target.value })}
            />
            <Input
              label="Last Name"
              placeholder="Doe"
              value={newContact.last_name}
              onChange={(e) => setNewContact({ ...newContact, last_name: e.target.value })}
            />
          </div>
          <Input
            label="Phone"
            type="tel"
            placeholder="+1 (555) 000-0000"
            value={newContact.phone}
            onChange={(e) => setNewContact({ ...newContact, phone: e.target.value })}
          />
          <Input
            label="Company"
            placeholder="Acme Inc."
            value={newContact.company}
            onChange={(e) => setNewContact({ ...newContact, company: e.target.value })}
          />
          <Select
            label="Status"
            options={statusOptions}
            value={newContact.status}
            onChange={(e) => setNewContact({ ...newContact, status: e.target.value as ContactStatus })}
            tooltip={helpContent.contacts.status}
          />
        </div>
        <div className="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-200">
          <Button variant="outline" onClick={() => setCreateModalOpen(false)}>
            Cancel
          </Button>
          <Button
            onClick={handleCreate}
            loading={createMutation.isPending}
            disabled={!newContact.email}
          >
            Add Contact
          </Button>
        </div>
      </Modal>

      {/* Delete Confirmation */}
      <ConfirmModal
        isOpen={deleteId !== null}
        onClose={() => setDeleteId(null)}
        onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
        title="Delete Contact"
        message="Are you sure you want to delete this contact? This action cannot be undone."
        confirmText="Delete"
        variant="danger"
        loading={deleteMutation.isPending}
      />

      {/* Bulk Delete Confirmation */}
      <ConfirmModal
        isOpen={bulkDeleteModalOpen}
        onClose={() => setBulkDeleteModalOpen(false)}
        onConfirm={handleBulkDelete}
        title="Delete Contacts"
        message={`Are you sure you want to delete ${selectedCount} contact${selectedCount !== 1 ? 's' : ''}? This action cannot be undone.`}
        confirmText="Delete All"
        variant="danger"
        loading={bulkDeleteMutation.isPending}
      />

      {/* Import Modal */}
      <Modal
        isOpen={importModalOpen}
        onClose={() => {
          setImportModalOpen(false);
          setImportFile(null);
        }}
        title="Import Contacts"
        size="md"
      >
        <div className="space-y-4">
          <p className="text-sm text-slate-600">
            Upload a CSV file with contacts. The file must have an "email" column.
            Optional columns: first_name, last_name, company, phone.
          </p>

          <div
            className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
              importFile ? 'border-primary-500 bg-primary-50' : 'border-slate-200 hover:border-slate-300'
            }`}
          >
            <input
              ref={fileInputRef}
              type="file"
              accept=".csv"
              className="hidden"
              onChange={(e) => {
                const file = e.target.files?.[0];
                if (file) setImportFile(file);
              }}
            />

            {importFile ? (
              <div className="flex items-center justify-center gap-2">
                <FileUp className="w-5 h-5 text-primary-600" />
                <span className="text-sm font-medium text-primary-700">{importFile.name}</span>
                <button
                  onClick={() => setImportFile(null)}
                  className="ml-2 text-slate-400 hover:text-slate-600"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            ) : (
              <div>
                <FileUp className="w-8 h-8 text-slate-400 mx-auto mb-2" />
                <p className="text-sm text-slate-600 mb-2">
                  Drag and drop a CSV file, or
                </p>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => fileInputRef.current?.click()}
                >
                  Browse Files
                </Button>
              </div>
            )}
          </div>

          <div className="flex items-start gap-2 p-3 bg-amber-50 rounded-lg">
            <AlertCircle className="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" />
            <p className="text-xs text-amber-700">
              All imported contacts will be added as leads. Duplicate emails will create new entries.
            </p>
          </div>
        </div>

        <div className="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-200">
          <Button
            variant="outline"
            onClick={() => {
              setImportModalOpen(false);
              setImportFile(null);
            }}
          >
            Cancel
          </Button>
          <Button
            onClick={handleImport}
            disabled={!importFile}
            loading={isImporting}
          >
            {isImporting ? 'Importing...' : 'Import Contacts'}
          </Button>
        </div>
      </Modal>
    </Layout>
  );
}

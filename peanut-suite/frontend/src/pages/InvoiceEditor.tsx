import { useState, useEffect, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
  ArrowLeft,
  Plus,
  Trash2,
  Save,
  Send,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Skeleton, useToast, ClientSelector } from '../components/common';
import { invoicesApi, projectsApi, contactsApi, clientsApi } from '../api/endpoints';
import { useAccountStore } from '../store';
import type { InvoiceItem, InvoiceItemType, DiscountType, InvoiceFormData } from '../types';

const itemTypes: { value: InvoiceItemType; label: string }[] = [
  { value: 'service', label: 'Service' },
  { value: 'product', label: 'Product' },
  { value: 'time', label: 'Time' },
  { value: 'expense', label: 'Expense' },
];

const defaultItem: Omit<InvoiceItem, 'id' | 'invoice_id'> = {
  item_type: 'service',
  description: '',
  quantity: 1,
  hours: null,
  rate: null,
  unit_price: 0,
  amount: 0,
  taxable: true,
  sort_order: 0,
};

export default function InvoiceEditor() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { isOwnerOrAdmin } = useAccountStore();

  const isEditing = !!id;

  // Form state
  const [selectedClientId, setSelectedClientId] = useState<number | null>(null);
  const [projectId, setProjectId] = useState<number | null>(null);
  const [contactId, setContactId] = useState<number | null>(null);
  const [clientName, setClientName] = useState('');
  const [clientEmail, setClientEmail] = useState('');
  const [clientCompany, setClientCompany] = useState('');
  const [clientAddress, setClientAddress] = useState('');
  const [issueDate, setIssueDate] = useState(new Date().toISOString().split('T')[0]);
  const [dueDate, setDueDate] = useState('');
  const [paymentTerms, setPaymentTerms] = useState('Net 30');
  const [taxPercent, setTaxPercent] = useState(0);
  const [discountAmount, setDiscountAmount] = useState(0);
  const [discountType, setDiscountType] = useState<DiscountType>('fixed');
  const [currency, setCurrency] = useState('USD');
  const [notes, setNotes] = useState('');
  const [clientNotes, setClientNotes] = useState('');
  const [footer, setFooter] = useState('');
  const [items, setItems] = useState<Omit<InvoiceItem, 'id' | 'invoice_id'>[]>([{ ...defaultItem }]);

  // Fetch projects
  const { data: projects } = useQuery({
    queryKey: ['projects'],
    queryFn: () => projectsApi.getAll(),
    enabled: isOwnerOrAdmin(),
  });

  // Fetch contacts
  const { data: contacts } = useQuery({
    queryKey: ['contacts-list'],
    queryFn: () => contactsApi.getAll({ per_page: 100 }),
    enabled: isOwnerOrAdmin(),
  });

  // Fetch selected client billing info
  const { data: clientBilling } = useQuery({
    queryKey: ['client-billing', selectedClientId],
    queryFn: () => clientsApi.getBilling(selectedClientId!),
    enabled: !!selectedClientId,
  });

  // Fetch existing invoice
  const { data: invoice, isLoading: loadingInvoice } = useQuery({
    queryKey: ['invoice', id],
    queryFn: () => invoicesApi.getById(parseInt(id!)),
    enabled: isEditing,
  });

  // Fetch next invoice number
  const { data: nextNumber } = useQuery({
    queryKey: ['invoice-next-number'],
    queryFn: () => invoicesApi.getNextNumber(),
    enabled: !isEditing,
  });

  // Populate form when editing
  useEffect(() => {
    if (invoice) {
      setProjectId(invoice.project_id);
      setContactId(invoice.contact_id);
      setClientName(invoice.client_name);
      setClientEmail(invoice.client_email);
      setClientCompany(invoice.client_company || '');
      setClientAddress(invoice.client_address || '');
      setIssueDate(invoice.issue_date || '');
      setDueDate(invoice.due_date || '');
      setPaymentTerms(invoice.payment_terms || '');
      setTaxPercent(invoice.tax_percent);
      setDiscountAmount(invoice.discount_amount);
      setDiscountType(invoice.discount_type);
      setCurrency(invoice.currency);
      setNotes(invoice.notes || '');
      setClientNotes(invoice.client_notes || '');
      setFooter(invoice.footer || '');
      setItems(invoice.items.map(item => ({
        item_type: item.item_type,
        description: item.description,
        quantity: item.quantity,
        hours: item.hours,
        rate: item.rate,
        unit_price: item.unit_price,
        amount: item.amount,
        taxable: item.taxable,
        sort_order: item.sort_order,
      })));
    }
  }, [invoice]);

  // Auto-populate due date from payment terms
  useEffect(() => {
    if (issueDate && paymentTerms) {
      const issue = new Date(issueDate);
      let days = 30;
      if (paymentTerms === 'Net 15') days = 15;
      if (paymentTerms === 'Net 30') days = 30;
      if (paymentTerms === 'Net 45') days = 45;
      if (paymentTerms === 'Net 60') days = 60;
      if (paymentTerms === 'Due on Receipt') days = 0;
      issue.setDate(issue.getDate() + days);
      setDueDate(issue.toISOString().split('T')[0]);
    }
  }, [issueDate, paymentTerms]);

  // Auto-fill client info from selected client
  useEffect(() => {
    if (clientBilling && selectedClientId) {
      setClientName(clientBilling.client_name || '');
      setClientEmail(clientBilling.client_email || '');
      setClientCompany(clientBilling.client_name || '');
      setClientAddress(clientBilling.client_address || '');
      if (clientBilling.currency) {
        setCurrency(clientBilling.currency);
      }
      if (clientBilling.payment_terms) {
        setPaymentTerms(`Net ${clientBilling.payment_terms}`);
      }
    }
  }, [clientBilling, selectedClientId]);

  // Auto-fill client info from contact (overrides client if both selected)
  useEffect(() => {
    if (contactId && contacts?.data) {
      const contact = contacts.data.find(c => c.id === contactId);
      if (contact) {
        setClientEmail(contact.email);
        setClientName(
          contact.first_name && contact.last_name
            ? `${contact.first_name} ${contact.last_name}`
            : contact.email
        );
        setClientCompany(contact.company || '');
      }
    }
  }, [contactId, contacts]);

  // Calculate totals
  const totals = useMemo(() => {
    const subtotal = items.reduce((sum, item) => sum + (item.amount || 0), 0);
    const taxableAmount = items.filter(i => i.taxable).reduce((sum, item) => sum + (item.amount || 0), 0);
    const taxAmount = (taxableAmount * taxPercent) / 100;
    const discount = discountType === 'percent' ? (subtotal * discountAmount) / 100 : discountAmount;
    const total = subtotal + taxAmount - discount;
    return { subtotal, taxAmount, discount, total };
  }, [items, taxPercent, discountAmount, discountType]);

  // Update item amount when price or quantity changes
  const updateItem = (index: number, updates: Partial<Omit<InvoiceItem, 'id' | 'invoice_id'>>) => {
    setItems(prev => {
      const newItems = [...prev];
      newItems[index] = { ...newItems[index], ...updates };

      // Recalculate amount
      const item = newItems[index];
      if (item.item_type === 'time' && item.hours && item.rate) {
        newItems[index].amount = item.hours * item.rate;
        newItems[index].unit_price = item.rate;
        newItems[index].quantity = item.hours;
      } else {
        newItems[index].amount = item.quantity * item.unit_price;
      }

      return newItems;
    });
  };

  const addItem = () => {
    setItems(prev => [...prev, { ...defaultItem, sort_order: prev.length }]);
  };

  const removeItem = (index: number) => {
    setItems(prev => prev.filter((_, i) => i !== index));
  };

  // Create mutation
  const createMutation = useMutation({
    mutationFn: (data: InvoiceFormData) => invoicesApi.create(data),
    onSuccess: (result) => {
      queryClient.invalidateQueries({ queryKey: ['invoices'] });
      toast.success('Invoice created successfully');
      navigate(`/finance/invoices/${result.id}`);
    },
    onError: () => toast.error('Failed to create invoice'),
  });

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: (data: InvoiceFormData) => invoicesApi.update(parseInt(id!), data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['invoices'] });
      queryClient.invalidateQueries({ queryKey: ['invoice', id] });
      toast.success('Invoice updated successfully');
    },
    onError: () => toast.error('Failed to update invoice'),
  });

  // Send mutation
  const sendMutation = useMutation({
    mutationFn: () => invoicesApi.send(parseInt(id!)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['invoices'] });
      queryClient.invalidateQueries({ queryKey: ['invoice', id] });
      toast.success('Invoice sent successfully');
    },
    onError: () => toast.error('Failed to send invoice'),
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!projectId) {
      toast.error('Please select a project');
      return;
    }

    if (!clientName || !clientEmail) {
      toast.error('Please enter client name and email');
      return;
    }

    if (items.length === 0 || items.every(i => !i.description)) {
      toast.error('Please add at least one line item');
      return;
    }

    const data: InvoiceFormData = {
      project_id: projectId,
      contact_id: contactId,
      client_name: clientName,
      client_email: clientEmail,
      client_company: clientCompany || undefined,
      client_address: clientAddress || undefined,
      issue_date: issueDate,
      due_date: dueDate,
      payment_terms: paymentTerms,
      tax_percent: taxPercent,
      discount_amount: discountAmount,
      discount_type: discountType,
      currency,
      notes: notes || undefined,
      client_notes: clientNotes || undefined,
      footer: footer || undefined,
      items: items.filter(i => i.description),
    };

    if (isEditing) {
      updateMutation.mutate(data);
    } else {
      createMutation.mutate(data);
    }
  };

  const isSubmitting = createMutation.isPending || updateMutation.isPending;

  if (isEditing && loadingInvoice) {
    return (
      <Layout title="Loading...">
        <Card>
          <div className="space-y-4">
            <Skeleton className="h-8 w-48" />
            <Skeleton className="h-64" />
          </div>
        </Card>
      </Layout>
    );
  }

  return (
    <Layout
      title={isEditing ? `Edit Invoice ${invoice?.invoice_number}` : 'New Invoice'}
    >
      {/* Header Actions */}
      <div className="flex justify-between items-center mb-6">
        <Link
          to="/finance/invoices"
          className="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 font-medium text-sm transition-colors"
        >
          <ArrowLeft className="w-4 h-4" />
          Back to Invoices
        </Link>
        <div className="flex gap-2">
          {isEditing && invoice?.status === 'draft' && (
            <Button
              variant="outline"
              icon={<Send className="w-4 h-4" />}
              onClick={() => sendMutation.mutate()}
              disabled={sendMutation.isPending}
            >
              Send
            </Button>
          )}
          <Button
            variant="primary"
            icon={<Save className="w-4 h-4" />}
            onClick={handleSubmit}
            disabled={isSubmitting}
          >
            {isSubmitting ? 'Saving...' : 'Save'}
          </Button>
        </div>
      </div>
      <form onSubmit={handleSubmit}>
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-6">
            {/* Client Details */}
            <Card>
              <h3 className="text-lg font-semibold text-slate-900 mb-4">Client Details</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="md:col-span-2">
                  <ClientSelector
                    value={selectedClientId}
                    onChange={(id) => setSelectedClientId(id)}
                    label="Client"
                    required={false}
                    placeholder="Select a client to auto-fill billing info..."
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Project <span className="text-red-500">*</span>
                  </label>
                  <select
                    value={projectId || ''}
                    onChange={(e) => setProjectId(e.target.value ? parseInt(e.target.value) : null)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                    required
                  >
                    <option value="">Select project...</option>
                    {projects?.map((p) => (
                      <option key={p.id} value={p.id}>
                        {p.name}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Contact (Optional)
                  </label>
                  <select
                    value={contactId || ''}
                    onChange={(e) => setContactId(e.target.value ? parseInt(e.target.value) : null)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                  >
                    <option value="">Select contact...</option>
                    {contacts?.data?.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.first_name} {c.last_name} ({c.email})
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Client Name <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={clientName}
                    onChange={(e) => setClientName(e.target.value)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Client Email <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="email"
                    value={clientEmail}
                    onChange={(e) => setClientEmail(e.target.value)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Company</label>
                  <input
                    type="text"
                    value={clientCompany}
                    onChange={(e) => setClientCompany(e.target.value)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Address</label>
                  <textarea
                    value={clientAddress}
                    onChange={(e) => setClientAddress(e.target.value)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                    rows={2}
                  />
                </div>
              </div>
            </Card>

            {/* Line Items */}
            <Card>
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold text-slate-900">Line Items</h3>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  icon={<Plus className="w-4 h-4" />}
                  onClick={addItem}
                >
                  Add Item
                </Button>
              </div>

              <div className="space-y-4">
                {items.map((item, index) => (
                  <div key={index} className="p-4 border border-slate-200 rounded-lg">
                    <div className="grid grid-cols-12 gap-3">
                      <div className="col-span-12 md:col-span-2">
                        <label className="block text-xs font-medium text-slate-500 mb-1">Type</label>
                        <select
                          value={item.item_type}
                          onChange={(e) => updateItem(index, { item_type: e.target.value as InvoiceItemType })}
                          className="w-full border border-slate-200 rounded px-2 py-1.5 text-sm"
                        >
                          {itemTypes.map((t) => (
                            <option key={t.value} value={t.value}>
                              {t.label}
                            </option>
                          ))}
                        </select>
                      </div>
                      <div className="col-span-12 md:col-span-4">
                        <label className="block text-xs font-medium text-slate-500 mb-1">Description</label>
                        <input
                          type="text"
                          value={item.description}
                          onChange={(e) => updateItem(index, { description: e.target.value })}
                          className="w-full border border-slate-200 rounded px-2 py-1.5 text-sm"
                          placeholder="Description"
                        />
                      </div>
                      {item.item_type === 'time' ? (
                        <>
                          <div className="col-span-6 md:col-span-2">
                            <label className="block text-xs font-medium text-slate-500 mb-1">Hours</label>
                            <input
                              type="number"
                              step="0.25"
                              min="0"
                              value={item.hours || ''}
                              onChange={(e) => updateItem(index, { hours: parseFloat(e.target.value) || 0 })}
                              className="w-full border border-slate-200 rounded px-2 py-1.5 text-sm"
                            />
                          </div>
                          <div className="col-span-6 md:col-span-2">
                            <label className="block text-xs font-medium text-slate-500 mb-1">Rate</label>
                            <input
                              type="number"
                              step="0.01"
                              min="0"
                              value={item.rate || ''}
                              onChange={(e) => updateItem(index, { rate: parseFloat(e.target.value) || 0 })}
                              className="w-full border border-slate-200 rounded px-2 py-1.5 text-sm"
                            />
                          </div>
                        </>
                      ) : (
                        <>
                          <div className="col-span-6 md:col-span-2">
                            <label className="block text-xs font-medium text-slate-500 mb-1">Qty</label>
                            <input
                              type="number"
                              min="1"
                              value={item.quantity}
                              onChange={(e) => updateItem(index, { quantity: parseInt(e.target.value) || 1 })}
                              className="w-full border border-slate-200 rounded px-2 py-1.5 text-sm"
                            />
                          </div>
                          <div className="col-span-6 md:col-span-2">
                            <label className="block text-xs font-medium text-slate-500 mb-1">Price</label>
                            <input
                              type="number"
                              step="0.01"
                              min="0"
                              value={item.unit_price}
                              onChange={(e) => updateItem(index, { unit_price: parseFloat(e.target.value) || 0 })}
                              className="w-full border border-slate-200 rounded px-2 py-1.5 text-sm"
                            />
                          </div>
                        </>
                      )}
                      <div className="col-span-6 md:col-span-1">
                        <label className="block text-xs font-medium text-slate-500 mb-1">Amount</label>
                        <p className="py-1.5 text-sm font-medium text-slate-900">
                          ${item.amount.toFixed(2)}
                        </p>
                      </div>
                      <div className="col-span-6 md:col-span-1 flex items-end justify-end">
                        <button
                          type="button"
                          onClick={() => removeItem(index)}
                          className="p-1.5 text-slate-400 hover:text-red-500 transition-colors"
                          disabled={items.length === 1}
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                    <div className="mt-2 flex items-center gap-4">
                      <label className="flex items-center gap-2 text-sm text-slate-600">
                        <input
                          type="checkbox"
                          checked={item.taxable}
                          onChange={(e) => updateItem(index, { taxable: e.target.checked })}
                          className="rounded border-slate-300"
                        />
                        Taxable
                      </label>
                    </div>
                  </div>
                ))}
              </div>
            </Card>

            {/* Notes */}
            <Card>
              <h3 className="text-lg font-semibold text-slate-900 mb-4">Notes & Terms</h3>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Internal Notes (not shown to client)
                  </label>
                  <textarea
                    value={notes}
                    onChange={(e) => setNotes(e.target.value)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                    rows={2}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Client Notes (shown on invoice)
                  </label>
                  <textarea
                    value={clientNotes}
                    onChange={(e) => setClientNotes(e.target.value)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                    rows={2}
                    placeholder="Thank you for your business!"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Footer</label>
                  <textarea
                    value={footer}
                    onChange={(e) => setFooter(e.target.value)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                    rows={2}
                    placeholder="Payment instructions, terms, etc."
                  />
                </div>
              </div>
            </Card>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Invoice Details */}
            <Card>
              <h3 className="text-lg font-semibold text-slate-900 mb-4">Invoice Details</h3>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Invoice Number
                  </label>
                  <input
                    type="text"
                    value={isEditing ? invoice?.invoice_number : nextNumber?.number || ''}
                    disabled
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-slate-50"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Issue Date</label>
                  <input
                    type="date"
                    value={issueDate}
                    onChange={(e) => setIssueDate(e.target.value)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Payment Terms</label>
                  <select
                    value={paymentTerms}
                    onChange={(e) => setPaymentTerms(e.target.value)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                  >
                    <option value="Due on Receipt">Due on Receipt</option>
                    <option value="Net 15">Net 15</option>
                    <option value="Net 30">Net 30</option>
                    <option value="Net 45">Net 45</option>
                    <option value="Net 60">Net 60</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Due Date</label>
                  <input
                    type="date"
                    value={dueDate}
                    onChange={(e) => setDueDate(e.target.value)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Currency</label>
                  <select
                    value={currency}
                    onChange={(e) => setCurrency(e.target.value)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                  >
                    <option value="USD">USD - US Dollar</option>
                    <option value="EUR">EUR - Euro</option>
                    <option value="GBP">GBP - British Pound</option>
                    <option value="CAD">CAD - Canadian Dollar</option>
                    <option value="AUD">AUD - Australian Dollar</option>
                  </select>
                </div>
              </div>
            </Card>

            {/* Tax & Discount */}
            <Card>
              <h3 className="text-lg font-semibold text-slate-900 mb-4">Tax & Discount</h3>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Tax Rate (%)</label>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    value={taxPercent}
                    onChange={(e) => setTaxPercent(parseFloat(e.target.value) || 0)}
                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Discount</label>
                  <div className="flex gap-2">
                    <input
                      type="number"
                      step="0.01"
                      min="0"
                      value={discountAmount}
                      onChange={(e) => setDiscountAmount(parseFloat(e.target.value) || 0)}
                      className="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm"
                    />
                    <select
                      value={discountType}
                      onChange={(e) => setDiscountType(e.target.value as DiscountType)}
                      className="border border-slate-200 rounded-lg px-3 py-2 text-sm"
                    >
                      <option value="fixed">$</option>
                      <option value="percent">%</option>
                    </select>
                  </div>
                </div>
              </div>
            </Card>

            {/* Totals */}
            <Card className="bg-slate-50">
              <h3 className="text-lg font-semibold text-slate-900 mb-4">Summary</h3>
              <div className="space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-slate-600">Subtotal</span>
                  <span className="font-medium">${totals.subtotal.toFixed(2)}</span>
                </div>
                {taxPercent > 0 && (
                  <div className="flex justify-between text-sm">
                    <span className="text-slate-600">Tax ({taxPercent}%)</span>
                    <span className="font-medium">${totals.taxAmount.toFixed(2)}</span>
                  </div>
                )}
                {discountAmount > 0 && (
                  <div className="flex justify-between text-sm text-green-600">
                    <span>Discount</span>
                    <span>-${totals.discount.toFixed(2)}</span>
                  </div>
                )}
                <hr className="my-2" />
                <div className="flex justify-between text-lg font-semibold">
                  <span>Total</span>
                  <span>${totals.total.toFixed(2)}</span>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </form>
    </Layout>
  );
}

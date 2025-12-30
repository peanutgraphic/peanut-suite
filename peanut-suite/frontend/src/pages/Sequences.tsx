import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  Mail,
  Plus,
  Play,
  Pause,
  Trash2,
  Users,
  Clock,
  ChevronRight,
  Edit2,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Input, Badge, Modal, ConfirmModal, useToast, SampleDataBanner } from '../components/common';
import { sequencesApi } from '../api/endpoints';
import { pageDescriptions, sampleSequences, sampleSequenceDetail, sampleSequenceSubscribers } from '../constants';

interface Sequence {
  id: number;
  name: string;
  description: string;
  trigger_type: string;
  trigger_value: string;
  status: 'draft' | 'active' | 'paused';
  active_subscribers: number;
  completed_subscribers: number;
  created_at: string;
}

interface SequenceEmail {
  id: number;
  sequence_id: number;
  subject: string;
  body: string;
  delay_days: number;
  delay_hours: number;
  status: string;
  created_at: string;
}

export default function Sequences() {
  const queryClient = useQueryClient();
  const toast = useToast();
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [selectedSequence, setSelectedSequence] = useState<number | null>(null);
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const [showSampleData, setShowSampleData] = useState(true);

  const { data, isLoading } = useQuery({
    queryKey: ['sequences'],
    queryFn: sequencesApi.getAll,
  });

  // Determine if we should show sample data
  const realSequences = data?.sequences || [];
  const hasNoRealData = !isLoading && realSequences.length === 0;
  const displaySampleData = hasNoRealData && showSampleData;
  const displaySequences = displaySampleData ? sampleSequences as Sequence[] : realSequences;

  const [newSequence, setNewSequence] = useState({
    name: '',
    description: '',
    trigger_type: 'manual',
    trigger_value: '',
  });

  const createMutation = useMutation({
    mutationFn: sequencesApi.create,
    onSuccess: (result) => {
      queryClient.invalidateQueries({ queryKey: ['sequences'] });
      setCreateModalOpen(false);
      setNewSequence({ name: '', description: '', trigger_type: 'manual', trigger_value: '' });
      toast.success('Sequence created');
      setSelectedSequence(result.id);
    },
    onError: () => {
      toast.error('Failed to create sequence');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: sequencesApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['sequences'] });
      setDeleteId(null);
      if (selectedSequence === deleteId) {
        setSelectedSequence(null);
      }
      toast.success('Sequence deleted');
    },
    onError: () => {
      toast.error('Failed to delete sequence');
    },
  });

  const updateStatusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      sequencesApi.update(id, { status }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['sequences'] });
      toast.success('Sequence status updated');
    },
    onError: () => {
      toast.error('Failed to update status');
    },
  });

  const getStatusBadge = (status: Sequence['status']) => {
    switch (status) {
      case 'active':
        return <Badge variant="success"><Play className="w-3 h-3 mr-1" />Active</Badge>;
      case 'paused':
        return <Badge variant="warning"><Pause className="w-3 h-3 mr-1" />Paused</Badge>;
      case 'draft':
        return <Badge variant="default"><Edit2 className="w-3 h-3 mr-1" />Draft</Badge>;
      default:
        return <Badge variant="default">{status}</Badge>;
    }
  };

  const getTriggerLabel = (type: string) => {
    switch (type) {
      case 'manual': return 'Manual enrollment';
      case 'contact_created': return 'Contact created';
      case 'tag_added': return 'Tag added';
      case 'form_submitted': return 'Form submitted';
      default: return type;
    }
  };

  const pageInfo = pageDescriptions.sequences || {
    title: 'Email Sequences',
    description: 'Create automated email drip campaigns',
    howTo: ['Create a sequence', 'Add emails with delays', 'Enroll contacts'],
    tips: ['Start with a welcome sequence', 'Use 2-3 day delays between emails'],
    useCases: ['Welcome series', 'Onboarding sequences', 'Re-engagement campaigns'],
  };

  return (
    <Layout title={pageInfo.title} description={pageInfo.description} helpContent={{ howTo: pageInfo.howTo, tips: pageInfo.tips, useCases: pageInfo.useCases }} pageGuideId="sequences">
      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      <div className="flex gap-6">
        {/* Sequences List */}
        <div className="w-80 flex-shrink-0">
          <Card padding="none">
            <div className="p-4 border-b border-slate-200 flex items-center justify-between">
              <h3 className="font-semibold text-slate-900">Sequences</h3>
              <Button size="sm" icon={<Plus className="w-4 h-4" />} onClick={() => setCreateModalOpen(true)}>
                New
              </Button>
            </div>

            <div className="divide-y divide-slate-100 max-h-[calc(100vh-280px)] overflow-y-auto">
              {isLoading ? (
                <div className="p-4">
                  <div className="animate-pulse space-y-3">
                    {[1, 2, 3].map((i) => (
                      <div key={i} className="h-16 bg-slate-100 rounded-lg" />
                    ))}
                  </div>
                </div>
              ) : displaySequences.length === 0 ? (
                <div className="p-8 text-center text-slate-500">
                  <Mail className="w-12 h-12 mx-auto mb-3 text-slate-300" />
                  <p>No sequences yet</p>
                  <Button
                    size="sm"
                    variant="outline"
                    className="mt-3"
                    onClick={() => setCreateModalOpen(true)}
                  >
                    Create your first sequence
                  </Button>
                </div>
              ) : (
                displaySequences.map((sequence) => (
                  <button
                    key={sequence.id}
                    onClick={() => setSelectedSequence(sequence.id)}
                    className={`w-full p-4 text-left hover:bg-slate-50 transition-colors ${
                      selectedSequence === sequence.id ? 'bg-primary-50' : ''
                    }`}
                  >
                    <div className="flex items-start justify-between">
                      <div className="flex-1 min-w-0">
                        <p className="font-medium text-slate-900 truncate">{sequence.name}</p>
                        <p className="text-xs text-slate-500 mt-0.5">
                          {getTriggerLabel(sequence.trigger_type)}
                        </p>
                      </div>
                      <ChevronRight className="w-4 h-4 text-slate-400 flex-shrink-0 mt-1" />
                    </div>
                    <div className="flex items-center gap-3 mt-2">
                      {getStatusBadge(sequence.status)}
                      <span className="text-xs text-slate-500">
                        {sequence.active_subscribers} active
                      </span>
                    </div>
                  </button>
                ))
              )}
            </div>
          </Card>
        </div>

        {/* Sequence Detail */}
        <div className="flex-1">
          {selectedSequence ? (
            <SequenceDetail
              sequenceId={selectedSequence}
              displaySampleData={displaySampleData}
              onDelete={() => !displaySampleData && setDeleteId(selectedSequence)}
              onStatusChange={(status) =>
                !displaySampleData && updateStatusMutation.mutate({ id: selectedSequence, status })
              }
            />
          ) : (
            <Card className="flex items-center justify-center h-96">
              <div className="text-center text-slate-500">
                <Mail className="w-16 h-16 mx-auto mb-4 text-slate-300" />
                <p className="text-lg font-medium">Select a sequence</p>
                <p className="text-sm">Choose a sequence from the list to view details</p>
              </div>
            </Card>
          )}
        </div>
      </div>

      {/* Create Modal */}
      <Modal
        isOpen={createModalOpen}
        onClose={() => setCreateModalOpen(false)}
        title="Create Email Sequence"
        size="md"
      >
        <div className="space-y-4">
          <Input
            label="Sequence Name"
            placeholder="Welcome Series"
            value={newSequence.name}
            onChange={(e) => setNewSequence({ ...newSequence, name: e.target.value })}
            required
          />
          <Input
            label="Description"
            placeholder="Optional description"
            value={newSequence.description}
            onChange={(e) => setNewSequence({ ...newSequence, description: e.target.value })}
          />
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1.5">Trigger</label>
            <select
              className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"
              value={newSequence.trigger_type}
              onChange={(e) => setNewSequence({ ...newSequence, trigger_type: e.target.value })}
            >
              <option value="manual">Manual enrollment</option>
              <option value="contact_created">When contact is created</option>
              <option value="tag_added">When tag is added</option>
              <option value="form_submitted">When form is submitted</option>
            </select>
          </div>
          {newSequence.trigger_type === 'tag_added' && (
            <Input
              label="Tag Name"
              placeholder="new-subscriber"
              value={newSequence.trigger_value}
              onChange={(e) => setNewSequence({ ...newSequence, trigger_value: e.target.value })}
            />
          )}
        </div>
        <div className="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-200">
          <Button variant="outline" onClick={() => setCreateModalOpen(false)}>
            Cancel
          </Button>
          <Button
            onClick={() => createMutation.mutate(newSequence)}
            loading={createMutation.isPending}
            disabled={!newSequence.name}
          >
            Create Sequence
          </Button>
        </div>
      </Modal>

      {/* Delete Confirmation */}
      <ConfirmModal
        isOpen={deleteId !== null}
        onClose={() => setDeleteId(null)}
        onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
        title="Delete Sequence"
        message="Are you sure you want to delete this sequence? All enrolled subscribers will be removed and emails will stop sending."
        confirmText="Delete"
        variant="danger"
        loading={deleteMutation.isPending}
      />
    </Layout>
  );
}

function SequenceDetail({
  sequenceId,
  displaySampleData = false,
  onDelete,
  onStatusChange,
}: {
  sequenceId: number;
  displaySampleData?: boolean;
  onDelete: () => void;
  onStatusChange: (status: string) => void;
}) {
  const queryClient = useQueryClient();
  const toast = useToast();
  const [emailModalOpen, setEmailModalOpen] = useState(false);
  const [enrollModalOpen, setEnrollModalOpen] = useState(false);
  const [newEmail, setNewEmail] = useState({
    subject: '',
    body: '',
    delay_days: 0,
    delay_hours: 0,
  });
  const [enrollEmail, setEnrollEmail] = useState('');

  const { data: realSequence, isLoading } = useQuery({
    queryKey: ['sequence', sequenceId],
    queryFn: () => sequencesApi.getById(sequenceId),
    enabled: !displaySampleData,
  });

  const { data: realSubscribers } = useQuery({
    queryKey: ['sequence-subscribers', sequenceId],
    queryFn: () => sequencesApi.getSubscribers(sequenceId),
    enabled: !displaySampleData,
  });

  // Use sample data if needed
  const sequence = displaySampleData ? sampleSequenceDetail : realSequence;
  const subscribers = displaySampleData ? { subscribers: sampleSequenceSubscribers } : realSubscribers;

  const addEmailMutation = useMutation({
    mutationFn: (email: typeof newEmail) => sequencesApi.addEmail(sequenceId, email),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['sequence', sequenceId] });
      setEmailModalOpen(false);
      setNewEmail({ subject: '', body: '', delay_days: 0, delay_hours: 0 });
      toast.success('Email added to sequence');
    },
    onError: () => {
      toast.error('Failed to add email');
    },
  });

  const enrollMutation = useMutation({
    mutationFn: (email: string) => sequencesApi.enrollContact(sequenceId, { email }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['sequence-subscribers', sequenceId] });
      setEnrollModalOpen(false);
      setEnrollEmail('');
      toast.success('Contact enrolled');
    },
    onError: () => {
      toast.error('Failed to enroll contact');
    },
  });

  const deleteEmailMutation = useMutation({
    mutationFn: sequencesApi.deleteEmail,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['sequence', sequenceId] });
      toast.success('Email removed');
    },
    onError: () => {
      toast.error('Failed to remove email');
    },
  });

  if (isLoading || !sequence) {
    return <Card><div className="animate-pulse h-96 bg-slate-100 rounded-lg" /></Card>;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <Card>
        <div className="flex items-start justify-between">
          <div>
            <h2 className="text-xl font-bold text-slate-900">{sequence.name}</h2>
            {sequence.description && (
              <p className="text-slate-500 mt-1">{sequence.description}</p>
            )}
          </div>
          <div className="flex items-center gap-2">
            {sequence.status === 'active' ? (
              <Button
                variant="outline"
                icon={<Pause className="w-4 h-4" />}
                onClick={() => onStatusChange('paused')}
              >
                Pause
              </Button>
            ) : (
              <Button
                icon={<Play className="w-4 h-4" />}
                onClick={() => onStatusChange('active')}
              >
                Activate
              </Button>
            )}
            <Button
              variant="outline"
              icon={<Trash2 className="w-4 h-4" />}
              onClick={onDelete}
            />
          </div>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-slate-200">
          <div className="text-center">
            <p className="text-2xl font-bold text-slate-900">{sequence.stats?.active || 0}</p>
            <p className="text-sm text-slate-500">Active</p>
          </div>
          <div className="text-center">
            <p className="text-2xl font-bold text-slate-900">{sequence.stats?.completed || 0}</p>
            <p className="text-sm text-slate-500">Completed</p>
          </div>
          <div className="text-center">
            <p className="text-2xl font-bold text-slate-900">{sequence.emails?.length || 0}</p>
            <p className="text-sm text-slate-500">Emails</p>
          </div>
        </div>
      </Card>

      {/* Emails */}
      <Card>
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-semibold text-slate-900">Emails in Sequence</h3>
          <Button size="sm" icon={<Plus className="w-4 h-4" />} onClick={() => setEmailModalOpen(true)}>
            Add Email
          </Button>
        </div>

        {!sequence.emails?.length ? (
          <div className="text-center py-8 text-slate-500 border-2 border-dashed border-slate-200 rounded-lg">
            <Mail className="w-10 h-10 mx-auto mb-2 text-slate-300" />
            <p>No emails in this sequence</p>
            <Button size="sm" variant="outline" className="mt-2" onClick={() => setEmailModalOpen(true)}>
              Add first email
            </Button>
          </div>
        ) : (
          <div className="space-y-3">
            {sequence.emails.map((email: SequenceEmail, index: number) => (
              <div
                key={email.id}
                className="flex items-start gap-4 p-4 border border-slate-200 rounded-lg"
              >
                <div className="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-700 font-medium">
                  {index + 1}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-slate-900">{email.subject}</p>
                  <p className="text-sm text-slate-500 line-clamp-2 mt-0.5">
                    {email.body.replace(/<[^>]*>/g, '').substring(0, 100)}...
                  </p>
                  <div className="flex items-center gap-2 mt-2">
                    <Clock className="w-3.5 h-3.5 text-slate-400" />
                    <span className="text-xs text-slate-500">
                      {index === 0
                        ? 'Immediately'
                        : `${email.delay_days}d ${email.delay_hours}h after previous`}
                    </span>
                  </div>
                </div>
                {!displaySampleData && (
                  <button
                    onClick={() => deleteEmailMutation.mutate(email.id)}
                    className="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                )}
              </div>
            ))}
          </div>
        )}
      </Card>

      {/* Subscribers */}
      <Card>
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-semibold text-slate-900">Subscribers</h3>
          <Button size="sm" variant="outline" icon={<Plus className="w-4 h-4" />} onClick={() => setEnrollModalOpen(true)}>
            Enroll Contact
          </Button>
        </div>

        {!subscribers?.subscribers?.length ? (
          <div className="text-center py-8 text-slate-500">
            <Users className="w-10 h-10 mx-auto mb-2 text-slate-300" />
            <p>No subscribers yet</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-slate-200">
                  <th className="text-left py-2 px-3 text-sm font-medium text-slate-500">Email</th>
                  <th className="text-left py-2 px-3 text-sm font-medium text-slate-500">Status</th>
                  <th className="text-left py-2 px-3 text-sm font-medium text-slate-500">Progress</th>
                  <th className="text-left py-2 px-3 text-sm font-medium text-slate-500">Enrolled</th>
                </tr>
              </thead>
              <tbody>
                {subscribers.subscribers.slice(0, 10).map((sub) => (
                  <tr key={sub.id} className="border-b border-slate-100">
                    <td className="py-2 px-3 text-sm">{sub.email}</td>
                    <td className="py-2 px-3">
                      <Badge
                        variant={
                          sub.status === 'completed'
                            ? 'success'
                            : sub.status === 'active'
                            ? 'info'
                            : 'default'
                        }
                        size="sm"
                      >
                        {sub.status}
                      </Badge>
                    </td>
                    <td className="py-2 px-3 text-sm text-slate-600">
                      {sub.emails_sent} / {sequence.emails?.length || 0} emails
                    </td>
                    <td className="py-2 px-3 text-sm text-slate-500">
                      {new Date(sub.enrolled_at).toLocaleDateString()}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      {/* Add Email Modal */}
      <Modal
        isOpen={emailModalOpen}
        onClose={() => setEmailModalOpen(false)}
        title="Add Email to Sequence"
        size="lg"
      >
        <div className="space-y-4">
          <Input
            label="Subject Line"
            placeholder="Welcome to our newsletter!"
            value={newEmail.subject}
            onChange={(e) => setNewEmail({ ...newEmail, subject: e.target.value })}
            required
          />
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1.5">Email Body</label>
            <textarea
              className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm h-32"
              placeholder="Write your email content here... (HTML supported)"
              value={newEmail.body}
              onChange={(e) => setNewEmail({ ...newEmail, body: e.target.value })}
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <Input
              type="number"
              label="Delay (Days)"
              value={newEmail.delay_days}
              onChange={(e) => setNewEmail({ ...newEmail, delay_days: parseInt(e.target.value) || 0 })}
              min={0}
            />
            <Input
              type="number"
              label="Delay (Hours)"
              value={newEmail.delay_hours}
              onChange={(e) => setNewEmail({ ...newEmail, delay_hours: parseInt(e.target.value) || 0 })}
              min={0}
              max={23}
            />
          </div>
          <p className="text-sm text-slate-500">
            This email will be sent{' '}
            {newEmail.delay_days === 0 && newEmail.delay_hours === 0
              ? 'immediately'
              : `${newEmail.delay_days} days and ${newEmail.delay_hours} hours after the previous email`}
            .
          </p>
        </div>
        <div className="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-200">
          <Button variant="outline" onClick={() => setEmailModalOpen(false)}>
            Cancel
          </Button>
          <Button
            onClick={() => addEmailMutation.mutate(newEmail)}
            loading={addEmailMutation.isPending}
            disabled={!newEmail.subject || !newEmail.body}
          >
            Add Email
          </Button>
        </div>
      </Modal>

      {/* Enroll Modal */}
      <Modal
        isOpen={enrollModalOpen}
        onClose={() => setEnrollModalOpen(false)}
        title="Enroll Contact"
        size="sm"
      >
        <Input
          label="Email Address"
          type="email"
          placeholder="contact@example.com"
          value={enrollEmail}
          onChange={(e) => setEnrollEmail(e.target.value)}
        />
        <div className="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-200">
          <Button variant="outline" onClick={() => setEnrollModalOpen(false)}>
            Cancel
          </Button>
          <Button
            onClick={() => enrollMutation.mutate(enrollEmail)}
            loading={enrollMutation.isPending}
            disabled={!enrollEmail}
          >
            Enroll
          </Button>
        </div>
      </Modal>
    </div>
  );
}

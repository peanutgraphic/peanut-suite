import { useState } from 'react';
import Layout from '@/components/layout/Layout';
import Button from '@/components/common/Button';
import Input from '@/components/common/Input';
import Select from '@/components/common/Select';
import Card from '@/components/common/Card';
import Badge from '@/components/common/Badge';
import Modal from '@/components/common/Modal';
import {
  NoDataEmptyState,
  NoResultsEmptyState,
  ErrorEmptyState,
  NoAccessEmptyState,
} from '@/components/common/EmptyState';
import StepIndicator, { CompactStepIndicator, type Step } from '@/components/common/StepIndicator';
import {
  TableSkeleton,
  CardSkeleton,
  StatCardSkeleton,
  ChartSkeleton,
  FormSkeleton,
} from '@/components/common/SkeletonLoader';
import ThemeToggle from '@/components/common/ThemeToggle';
import { toast } from '@/store/useToastStore';
import { Search, Mail, Save, Trash2, Download } from 'lucide-react';

export default function UIShowcase() {
  const [modalOpen, setModalOpen] = useState(false);
  const [currentStep, setCurrentStep] = useState(0);
  const [formData, setFormData] = useState({
    email: '',
    name: '',
    message: '',
  });
  const [formErrors, setFormErrors] = useState<Record<string, string>>({});

  const steps: Step[] = [
    { id: '1', label: 'Basic Info', description: 'Name and email' },
    { id: '2', label: 'Details', description: 'Additional information' },
    { id: '3', label: 'Review', description: 'Confirm and submit' },
  ];

  const handleValidateEmail = (email: string) => {
    if (!email) {
      setFormErrors((prev) => ({ ...prev, email: 'Email is required' }));
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setFormErrors((prev) => ({ ...prev, email: 'Please enter a valid email address' }));
    } else {
      setFormErrors((prev) => {
        const { email, ...rest } = prev;
        return rest;
      });
    }
  };

  return (
    <Layout title="UI Component Showcase" description="Preview all available UI components">
      <div className="space-y-8">
        {/* Toast Notifications Section */}
        <Card>
          <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-4">
            Toast Notifications
          </h2>
          <p className="text-sm text-slate-600 dark:text-slate-400 mb-6">
            Click buttons to trigger different toast notifications.
          </p>
          <div className="flex flex-wrap gap-3">
            <Button onClick={() => toast.success('Changes saved successfully!')} variant="primary">
              Success Toast
            </Button>
            <Button onClick={() => toast.error('Failed to save changes')} variant="danger">
              Error Toast
            </Button>
            <Button onClick={() => toast.warning('This action cannot be undone')} variant="outline">
              Warning Toast
            </Button>
            <Button onClick={() => toast.info('New feature available')} variant="secondary">
              Info Toast
            </Button>
          </div>
        </Card>

        {/* Buttons Section */}
        <Card>
          <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-4">Buttons</h2>
          <div className="space-y-4">
            <div>
              <h3 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                Variants
              </h3>
              <div className="flex flex-wrap gap-3">
                <Button variant="primary">Primary</Button>
                <Button variant="secondary">Secondary</Button>
                <Button variant="danger">Danger</Button>
                <Button variant="ghost">Ghost</Button>
                <Button variant="outline">Outline</Button>
              </div>
            </div>

            <div>
              <h3 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Sizes</h3>
              <div className="flex flex-wrap items-center gap-3">
                <Button size="sm">Small</Button>
                <Button size="md">Medium</Button>
                <Button size="lg">Large</Button>
              </div>
            </div>

            <div>
              <h3 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                With Icons
              </h3>
              <div className="flex flex-wrap gap-3">
                <Button icon={<Search className="w-4 h-4" />}>Search</Button>
                <Button icon={<Download className="w-4 h-4" />} iconPosition="right" variant="secondary">
                  Download
                </Button>
                <Button icon={<Save className="w-4 h-4" />} variant="primary">
                  Save Changes
                </Button>
                <Button icon={<Trash2 className="w-4 h-4" />} variant="danger">
                  Delete
                </Button>
              </div>
            </div>

            <div>
              <h3 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">States</h3>
              <div className="flex flex-wrap gap-3">
                <Button loading>Loading</Button>
                <Button disabled>Disabled</Button>
              </div>
            </div>
          </div>
        </Card>

        {/* Form Inputs Section */}
        <Card>
          <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-4">
            Form Inputs
          </h2>
          <div className="space-y-6 max-w-2xl">
            <Input
              label="Email"
              type="email"
              placeholder="Enter your email"
              value={formData.email}
              onChange={(e) => setFormData({ ...formData, email: e.target.value })}
              onBlur={(e) => handleValidateEmail(e.target.value)}
              error={formErrors.email}
              showValidation
              success={!!formData.email && !formErrors.email}
              leftIcon={<Mail className="w-5 h-5" />}
              required
            />

            <Input
              label="Full Name"
              placeholder="John Doe"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              hint="Enter your first and last name"
            />

            <Input
              label="Search"
              placeholder="Search..."
              leftIcon={<Search className="w-5 h-5" />}
            />

            <Input label="Disabled Input" placeholder="Can't edit this" disabled value="Disabled" />

            <Select
              label="Category"
              options={[
                { value: 'option1', label: 'Option 1' },
                { value: 'option2', label: 'Option 2' },
                { value: 'option3', label: 'Option 3' },
              ]}
            />
          </div>
        </Card>

        {/* Badges Section */}
        <Card>
          <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-4">Badges</h2>
          <div className="flex flex-wrap gap-3">
            <Badge variant="default">Default</Badge>
            <Badge variant="primary">Primary</Badge>
            <Badge variant="success">Success</Badge>
            <Badge variant="warning">Warning</Badge>
            <Badge variant="danger">Danger</Badge>
            <Badge variant="info">Info</Badge>
          </div>
        </Card>

        {/* Step Indicator Section */}
        <Card>
          <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-4">
            Step Indicators
          </h2>
          <div className="space-y-8">
            <div>
              <h3 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-4">
                Horizontal
              </h3>
              <StepIndicator
                steps={steps}
                currentStep={currentStep}
                onStepClick={setCurrentStep}
                allowClickPrevious
              />
              <div className="mt-4 flex gap-3">
                <Button
                  size="sm"
                  onClick={() => setCurrentStep(Math.max(0, currentStep - 1))}
                  disabled={currentStep === 0}
                >
                  Previous
                </Button>
                <Button
                  size="sm"
                  onClick={() => setCurrentStep(Math.min(steps.length - 1, currentStep + 1))}
                  disabled={currentStep === steps.length - 1}
                >
                  Next
                </Button>
              </div>
            </div>

            <div>
              <h3 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-4">
                Compact
              </h3>
              <CompactStepIndicator steps={steps} currentStep={currentStep} />
            </div>

            <div>
              <h3 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-4">
                Vertical
              </h3>
              <StepIndicator
                steps={steps}
                currentStep={currentStep}
                onStepClick={setCurrentStep}
                variant="vertical"
                allowClickPrevious
              />
            </div>
          </div>
        </Card>

        {/* Empty States Section */}
        <Card>
          <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-4">
            Empty States
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="border border-slate-200 dark:border-slate-700 rounded-lg">
              <NoDataEmptyState />
            </div>
            <div className="border border-slate-200 dark:border-slate-700 rounded-lg">
              <NoResultsEmptyState />
            </div>
            <div className="border border-slate-200 dark:border-slate-700 rounded-lg">
              <ErrorEmptyState
                action={
                  <Button size="sm" variant="primary">
                    Try Again
                  </Button>
                }
              />
            </div>
            <div className="border border-slate-200 dark:border-slate-700 rounded-lg">
              <NoAccessEmptyState
                action={
                  <Button size="sm" variant="primary">
                    Upgrade Plan
                  </Button>
                }
              />
            </div>
          </div>
        </Card>

        {/* Skeleton Loaders Section */}
        <Card>
          <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-4">
            Skeleton Loaders
          </h2>
          <div className="space-y-6">
            <div>
              <h3 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                Table Skeleton
              </h3>
              <TableSkeleton rows={3} columns={4} />
            </div>

            <div>
              <h3 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                Card Skeleton
              </h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <CardSkeleton />
                <StatCardSkeleton />
              </div>
            </div>

            <div>
              <h3 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                Chart Skeleton
              </h3>
              <ChartSkeleton />
            </div>

            <div>
              <h3 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                Form Skeleton
              </h3>
              <FormSkeleton fields={3} />
            </div>
          </div>
        </Card>

        {/* Theme Toggle Section */}
        <Card>
          <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-4">
            Theme Toggle
          </h2>
          <div className="space-y-6">
            <div>
              <h3 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                Button Style
              </h3>
              <ThemeToggle variant="button" showLabel />
            </div>

            <div>
              <h3 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                Dropdown Style
              </h3>
              <ThemeToggle variant="dropdown" showLabel />
            </div>
          </div>
        </Card>

        {/* Modal Section */}
        <Card>
          <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-4">Modal</h2>
          <Button onClick={() => setModalOpen(true)}>Open Modal</Button>

          <Modal
            isOpen={modalOpen}
            onClose={() => setModalOpen(false)}
            title="Example Modal"
            size="md"
          >
            <div className="space-y-4">
              <p className="text-sm text-slate-600 dark:text-slate-400">
                This is an example modal dialog. It includes a header, body content, and action
                buttons.
              </p>
              <Input label="Example Input" placeholder="Type something..." />
              <div className="flex justify-end gap-3 pt-4">
                <Button variant="ghost" onClick={() => setModalOpen(false)}>
                  Cancel
                </Button>
                <Button variant="primary" onClick={() => setModalOpen(false)}>
                  Confirm
                </Button>
              </div>
            </div>
          </Modal>
        </Card>

        {/* Keyboard Shortcuts Info */}
        <Card>
          <h2 className="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-4">
            Keyboard Shortcuts
          </h2>
          <div className="space-y-3">
            <div className="flex items-center justify-between py-2">
              <span className="text-sm text-slate-600 dark:text-slate-400">
                Save (when in forms)
              </span>
              <kbd className="px-2 py-1 text-xs font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded border border-slate-200 dark:border-slate-700">
                Ctrl+S
              </kbd>
            </div>
            <div className="flex items-center justify-between py-2">
              <span className="text-sm text-slate-600 dark:text-slate-400">Close modals</span>
              <kbd className="px-2 py-1 text-xs font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded border border-slate-200 dark:border-slate-700">
                Escape
              </kbd>
            </div>
            <div className="flex items-center justify-between py-2">
              <span className="text-sm text-slate-600 dark:text-slate-400">
                Command palette (future)
              </span>
              <kbd className="px-2 py-1 text-xs font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded border border-slate-200 dark:border-slate-700">
                Ctrl+K
              </kbd>
            </div>
          </div>
        </Card>
      </div>
    </Layout>
  );
}

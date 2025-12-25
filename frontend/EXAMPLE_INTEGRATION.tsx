/**
 * Example: Integrating UI/UX Improvements in PopupBuilder
 *
 * This file demonstrates how to integrate the new UI/UX improvements
 * into the PopupBuilder page. Copy relevant sections to the actual
 * PopupBuilder.tsx file.
 */

import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Save, Eye, ArrowLeft } from 'lucide-react';

// Import new components and hooks
import {
  StepIndicator,
  Button,
  Input,
  Card,
  PageSkeleton,
  toast
} from '@/components/common';
import useKeyboardShortcuts, { commonShortcuts } from '@/hooks/useKeyboardShortcuts';
import { Layout } from '../components/layout';
import { popupsApi } from '../api/endpoints';

type PopupStep = 'content' | 'trigger' | 'targeting' | 'style';

export default function PopupBuilderExample() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [currentStep, setCurrentStep] = useState<PopupStep>('content');
  const [formData, setFormData] = useState({});

  // Define steps for the step indicator
  const steps = [
    {
      id: 'content',
      label: 'Content',
      description: 'Add your popup content'
    },
    {
      id: 'trigger',
      label: 'Trigger',
      description: 'Set display conditions'
    },
    {
      id: 'targeting',
      label: 'Targeting',
      description: 'Choose your audience'
    },
    {
      id: 'style',
      label: 'Style',
      description: 'Customize appearance'
    },
  ];

  // Get current step index
  const currentStepIndex = steps.findIndex(s => s.id === currentStep);

  // Fetch popup data with loading state
  const { data: popup, isLoading } = useQuery({
    queryKey: ['popup', id],
    queryFn: () => id ? popupsApi.getPopup(parseInt(id)) : null,
    enabled: !!id,
  });

  // Save mutation with toast notifications
  const saveMutation = useMutation({
    mutationFn: (data: any) =>
      id
        ? popupsApi.updatePopup(parseInt(id), data)
        : popupsApi.createPopup(data),
    onSuccess: () => {
      toast.success('Popup saved successfully!');
      navigate('/popups');
    },
    onError: (error: any) => {
      toast.error(error.message || 'Failed to save popup');
    },
  });

  // Handle save action
  const handleSave = async () => {
    try {
      await saveMutation.mutateAsync(formData);
    } catch (error) {
      // Error already handled in onError
    }
  };

  // Handle preview
  const handlePreview = () => {
    toast.info('Opening preview...');
    // Preview logic
  };

  // Keyboard shortcuts
  useKeyboardShortcuts([
    commonShortcuts.save(handleSave),
    commonShortcuts.escape(() => navigate('/popups')),
    {
      key: 'p',
      ctrlKey: true,
      callback: handlePreview,
      description: 'Preview popup',
    },
    {
      key: 'ArrowRight',
      callback: () => {
        if (currentStepIndex < steps.length - 1) {
          setCurrentStep(steps[currentStepIndex + 1].id as PopupStep);
        }
      },
      description: 'Next step',
    },
    {
      key: 'ArrowLeft',
      callback: () => {
        if (currentStepIndex > 0) {
          setCurrentStep(steps[currentStepIndex - 1].id as PopupStep);
        }
      },
      description: 'Previous step',
    },
  ]);

  // Show skeleton loader while loading
  if (isLoading) {
    return (
      <Layout title="Loading...">
        <PageSkeleton />
      </Layout>
    );
  }

  return (
    <Layout
      title={id ? 'Edit Popup' : 'Create Popup'}
      description="Build and customize your popup"
      action={
        <div className="flex items-center gap-3">
          <Button
            variant="outline"
            onClick={() => navigate('/popups')}
            icon={<ArrowLeft className="w-4 h-4" />}
          >
            Back
          </Button>
          <Button
            variant="secondary"
            onClick={handlePreview}
            icon={<Eye className="w-4 h-4" />}
          >
            Preview
          </Button>
          <Button
            onClick={handleSave}
            loading={saveMutation.isPending}
            icon={<Save className="w-4 h-4" />}
          >
            Save Popup
          </Button>
        </div>
      }
    >
      <div className="max-w-6xl mx-auto">
        {/* Step Indicator */}
        <div className="mb-8">
          <StepIndicator
            steps={steps}
            currentStep={currentStepIndex}
            onStepClick={(index) => setCurrentStep(steps[index].id as PopupStep)}
            allowClickPrevious={true}
          />
        </div>

        {/* Step Content */}
        <Card>
          {currentStep === 'content' && (
            <ContentStep
              data={formData}
              onChange={setFormData}
            />
          )}
          {currentStep === 'trigger' && (
            <TriggerStep
              data={formData}
              onChange={setFormData}
            />
          )}
          {currentStep === 'targeting' && (
            <TargetingStep
              data={formData}
              onChange={setFormData}
            />
          )}
          {currentStep === 'style' && (
            <StyleStep
              data={formData}
              onChange={setFormData}
            />
          )}
        </Card>

        {/* Navigation Footer */}
        <div className="flex items-center justify-between mt-6">
          <div className="text-sm text-slate-500 dark:text-slate-400">
            Press <kbd className="px-2 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded">Ctrl+S</kbd> to save
          </div>
          <div className="flex gap-3">
            <Button
              variant="outline"
              onClick={() => {
                if (currentStepIndex > 0) {
                  setCurrentStep(steps[currentStepIndex - 1].id as PopupStep);
                }
              }}
              disabled={currentStepIndex === 0}
            >
              Previous
            </Button>
            {currentStepIndex < steps.length - 1 ? (
              <Button
                onClick={() => {
                  setCurrentStep(steps[currentStepIndex + 1].id as PopupStep);
                }}
              >
                Next
              </Button>
            ) : (
              <Button
                onClick={handleSave}
                loading={saveMutation.isPending}
              >
                Save & Finish
              </Button>
            )}
          </div>
        </div>
      </div>
    </Layout>
  );
}

// Example step components
function ContentStep({ data, onChange }: any) {
  return (
    <div className="space-y-6">
      <h3 className="text-lg font-semibold text-slate-900 dark:text-white">
        Popup Content
      </h3>
      <Input
        label="Popup Name"
        placeholder="Welcome Popup"
        value={data.name || ''}
        onChange={(e) => onChange({ ...data, name: e.target.value })}
        helper="Internal name for your reference"
      />
      <Input
        label="Title"
        placeholder="Welcome to our site!"
        value={data.title || ''}
        onChange={(e) => onChange({ ...data, title: e.target.value })}
      />
      {/* More content fields... */}
    </div>
  );
}

function TriggerStep({ data, onChange }: any) {
  return (
    <div className="space-y-6">
      <h3 className="text-lg font-semibold text-slate-900 dark:text-white">
        Display Trigger
      </h3>
      {/* Trigger configuration... */}
    </div>
  );
}

function TargetingStep({ data, onChange }: any) {
  return (
    <div className="space-y-6">
      <h3 className="text-lg font-semibold text-slate-900 dark:text-white">
        Audience Targeting
      </h3>
      {/* Targeting configuration... */}
    </div>
  );
}

function StyleStep({ data, onChange }: any) {
  return (
    <div className="space-y-6">
      <h3 className="text-lg font-semibold text-slate-900 dark:text-white">
        Style & Appearance
      </h3>
      {/* Style configuration... */}
    </div>
  );
}

/**
 * Usage Examples for Other Components
 */

// Example 1: Using toast in a mutation
function ExampleMutation() {
  const mutation = useMutation({
    mutationFn: async (data: any) => {
      // API call
    },
    onSuccess: () => {
      toast.success('Operation completed successfully!');
    },
    onError: (error: any) => {
      toast.error(`Error: ${error.message}`);
    },
  });

  return null;
}

// Example 2: Using skeleton loaders
function ExampleListWithSkeleton() {
  const { data, isLoading } = useQuery({
    queryKey: ['items'],
    queryFn: async () => {
      // Fetch items
    },
  });

  if (isLoading) {
    return (
      <div className="space-y-2">
        {Array.from({ length: 5 }).map((_, i) => (
          <ListItemSkeleton key={i} />
        ))}
      </div>
    );
  }

  return (
    <div>
      {data?.map((item: any) => (
        <div key={item.id}>{item.name}</div>
      ))}
    </div>
  );
}

// Example 3: Using keyboard shortcuts in a modal
function ExampleModalWithShortcuts() {
  const [isOpen, setIsOpen] = useState(false);

  useKeyboardShortcuts([
    {
      key: 'n',
      ctrlKey: true,
      callback: () => setIsOpen(true),
      description: 'Open modal',
    },
  ]);

  return (
    <Modal isOpen={isOpen} onClose={() => setIsOpen(false)} title="Example">
      {/* Modal automatically supports Escape to close */}
    </Modal>
  );
}

// Example 4: Table with loading skeleton
function ExampleTableWithSkeleton() {
  const { data, isLoading } = useQuery({
    queryKey: ['table-data'],
    queryFn: async () => {
      // Fetch data
    },
  });

  if (isLoading) {
    return <TableSkeleton rows={10} columns={6} />;
  }

  return (
    <Table
      data={data}
      columns={[...]}
    />
  );
}

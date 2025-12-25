import { Check } from 'lucide-react';
import { clsx } from 'clsx';

export interface Step {
  id: string;
  label: string;
  description?: string;
}

interface StepIndicatorProps {
  steps: Step[];
  currentStep: number;
  onStepClick?: (stepIndex: number) => void;
  variant?: 'horizontal' | 'vertical';
  allowClickPrevious?: boolean;
  className?: string;
}

export default function StepIndicator({
  steps,
  currentStep,
  onStepClick,
  variant = 'horizontal',
  allowClickPrevious = true,
  className,
}: StepIndicatorProps) {
  const handleStepClick = (index: number) => {
    if (!onStepClick) return;

    // Allow clicking on previous steps or current step
    if (allowClickPrevious && index <= currentStep) {
      onStepClick(index);
    }
  };

  if (variant === 'vertical') {
    return (
      <nav aria-label="Progress" className={className}>
        <ol className="space-y-2">
          {steps.map((step, index) => {
            const isCompleted = index < currentStep;
            const isCurrent = index === currentStep;
            const isClickable = allowClickPrevious && index <= currentStep && onStepClick;

            return (
              <li key={step.id}>
                <button
                  onClick={() => handleStepClick(index)}
                  disabled={!isClickable}
                  className={clsx(
                    'group flex items-start gap-4 w-full text-left p-3 rounded-lg transition-colors',
                    {
                      'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800': isClickable,
                      'cursor-default': !isClickable,
                    }
                  )}
                  aria-current={isCurrent ? 'step' : undefined}
                >
                  <div className="flex-shrink-0 relative">
                    {isCompleted ? (
                      <div className="w-8 h-8 bg-primary-600 dark:bg-primary-500 rounded-full flex items-center justify-center">
                        <Check className="w-5 h-5 text-white" aria-hidden="true" />
                      </div>
                    ) : (
                      <div
                        className={clsx(
                          'w-8 h-8 rounded-full flex items-center justify-center border-2',
                          {
                            'border-primary-600 dark:border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 font-semibold':
                              isCurrent,
                            'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400':
                              !isCurrent,
                          }
                        )}
                      >
                        <span className="text-sm">{index + 1}</span>
                      </div>
                    )}
                    {index < steps.length - 1 && (
                      <div
                        className={clsx(
                          'absolute left-4 top-10 w-0.5 h-full',
                          {
                            'bg-primary-600 dark:bg-primary-500': isCompleted,
                            'bg-slate-200 dark:bg-slate-700': !isCompleted,
                          }
                        )}
                        aria-hidden="true"
                      />
                    )}
                  </div>
                  <div className="flex-1 min-w-0 pt-0.5">
                    <p
                      className={clsx('text-sm font-medium', {
                        'text-slate-900 dark:text-white': isCurrent || isCompleted,
                        'text-slate-500 dark:text-slate-400': !isCurrent && !isCompleted,
                      })}
                    >
                      {step.label}
                    </p>
                    {step.description && (
                      <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        {step.description}
                      </p>
                    )}
                  </div>
                </button>
              </li>
            );
          })}
        </ol>
      </nav>
    );
  }

  // Horizontal variant
  return (
    <nav aria-label="Progress" className={className}>
      <ol className="flex items-center justify-between">
        {steps.map((step, index) => {
          const isCompleted = index < currentStep;
          const isCurrent = index === currentStep;
          const isClickable = allowClickPrevious && index <= currentStep && onStepClick;

          return (
            <li key={step.id} className="flex-1 last:flex-none">
              <div className="flex items-center">
                <button
                  onClick={() => handleStepClick(index)}
                  disabled={!isClickable}
                  className={clsx(
                    'group flex flex-col items-center gap-2 transition-colors',
                    {
                      'cursor-pointer': isClickable,
                      'cursor-default': !isClickable,
                    }
                  )}
                  aria-current={isCurrent ? 'step' : undefined}
                >
                  <div className="flex items-center gap-2">
                    {isCompleted ? (
                      <div className="w-10 h-10 bg-primary-600 dark:bg-primary-500 rounded-full flex items-center justify-center shadow-sm">
                        <Check className="w-5 h-5 text-white" aria-hidden="true" />
                      </div>
                    ) : (
                      <div
                        className={clsx(
                          'w-10 h-10 rounded-full flex items-center justify-center border-2 shadow-sm',
                          {
                            'border-primary-600 dark:border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 font-semibold':
                              isCurrent,
                            'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400':
                              !isCurrent,
                          }
                        )}
                      >
                        <span className="text-sm">{index + 1}</span>
                      </div>
                    )}
                  </div>
                  <div className="text-center">
                    <p
                      className={clsx('text-sm font-medium', {
                        'text-slate-900 dark:text-white': isCurrent || isCompleted,
                        'text-slate-500 dark:text-slate-400': !isCurrent && !isCompleted,
                      })}
                    >
                      {step.label}
                    </p>
                    {step.description && (
                      <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-[120px]">
                        {step.description}
                      </p>
                    )}
                  </div>
                </button>
                {index < steps.length - 1 && (
                  <div
                    className={clsx('flex-1 h-0.5 mx-4', {
                      'bg-primary-600 dark:bg-primary-500': isCompleted,
                      'bg-slate-200 dark:bg-slate-700': !isCompleted,
                    })}
                    aria-hidden="true"
                  />
                )}
              </div>
            </li>
          );
        })}
      </ol>
    </nav>
  );
}

// Compact variant for space-constrained layouts
export function CompactStepIndicator({
  steps,
  currentStep,
  className,
}: {
  steps: Step[];
  currentStep: number;
  className?: string;
}) {
  return (
    <div className={clsx('flex items-center gap-2', className)} aria-label="Progress">
      <span className="text-sm font-medium text-slate-700 dark:text-slate-300">
        Step {currentStep + 1} of {steps.length}
      </span>
      <div className="flex gap-1.5">
        {steps.map((step, index) => (
          <div
            key={step.id}
            className={clsx('w-2 h-2 rounded-full transition-colors', {
              'bg-primary-600 dark:bg-primary-500': index <= currentStep,
              'bg-slate-200 dark:bg-slate-700': index > currentStep,
            })}
            aria-label={`${step.label}${index === currentStep ? ' (current)' : index < currentStep ? ' (completed)' : ''}`}
          />
        ))}
      </div>
    </div>
  );
}

import { useEffect, useState, useRef } from 'react';
import { createPortal } from 'react-dom';
import { getPortalRoot } from '../../utils/portalRoot';
import {
  X,
  ChevronLeft,
  ChevronRight,
  CheckCircle2,
  Lightbulb,
  BookOpen,
  // Icons for guide steps - mapped by name
  BarChart2,
  Zap,
  TrendingUp,
  Link2,
  Target,
  Plus,
  Copy,
  Edit2,
  QrCode,
  Users,
  Tag,
  Filter,
  Download,
  Bell,
  Play,
  Activity,
  Eye,
  UserCheck,
  Route,
  Clock,
  GitBranch,
  Layers,
  PieChart,
  Calendar,
  Settings,
  Shield,
  RefreshCw,
  Lock,
  FileText,
  UserX,
  UserPlus,
  Key,
  LogOut,
  Plug,
  Code,
  Globe,
  Search,
  Award,
  Mail,
  ShoppingCart,
  DollarSign,
  Gauge,
  Smartphone,
} from 'lucide-react';
import { clsx } from 'clsx';
import { usePageGuideStore } from '../../store/usePageGuideStore';
import { getPageGuide } from '../../constants/pageGuides';
import Button from './Button';

// Icon mapping for dynamic rendering
const iconMap: Record<string, React.ElementType> = {
  BarChart2,
  Zap,
  TrendingUp,
  BookOpen,
  Link2,
  Target,
  Plus,
  Copy,
  Edit2,
  QrCode,
  Users,
  Tag,
  Filter,
  Download,
  Bell,
  Play,
  Activity,
  Eye,
  UserCheck,
  Route,
  Clock,
  GitBranch,
  Layers,
  PieChart,
  Calendar,
  Settings,
  Shield,
  RefreshCw,
  Lock,
  FileText,
  UserX,
  UserPlus,
  Key,
  LogOut,
  Plug,
  Code,
  Globe,
  Search,
  Award,
  Mail,
  ShoppingCart,
  DollarSign,
  Gauge,
  Smartphone,
};

function getIcon(iconName: string): React.ElementType {
  return iconMap[iconName] || BookOpen;
}

interface PageGuideProps {
  pageId: string;
}

export default function PageGuide({ pageId }: PageGuideProps) {
  const {
    isGuideOpen,
    currentPageId,
    currentStep,
    closeGuide,
    dismissGuide,
    nextStep,
    prevStep,
    goToStep,
  } = usePageGuideStore();

  const [dontShowAgain, setDontShowAgain] = useState(false);
  const dialogRef = useRef<HTMLDivElement>(null);
  const previousFocusRef = useRef<HTMLElement | null>(null);
  const closeButtonRef = useRef<HTMLButtonElement>(null);

  const guide = getPageGuide(pageId);
  const isOpen = isGuideOpen && currentPageId === pageId && guide;

  // Reset checkbox when guide changes
  useEffect(() => {
    setDontShowAgain(false);
  }, [pageId]);

  // Focus management - store previous focus and focus dialog on open
  useEffect(() => {
    if (isOpen) {
      previousFocusRef.current = document.activeElement as HTMLElement;
      // Focus the close button after a short delay to allow render
      const timer = setTimeout(() => {
        closeButtonRef.current?.focus();
      }, 100);
      return () => clearTimeout(timer);
    } else if (previousFocusRef.current) {
      // Return focus to previous element when closed
      previousFocusRef.current.focus();
      previousFocusRef.current = null;
    }
  }, [isOpen]);

  // Keyboard handling - Escape to close, focus trap
  useEffect(() => {
    if (!isOpen) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        e.preventDefault();
        if (dontShowAgain) {
          dismissGuide(pageId);
        } else {
          closeGuide();
        }
      }

      // Focus trap - Tab and Shift+Tab
      if (e.key === 'Tab' && dialogRef.current) {
        const focusableElements = dialogRef.current.querySelectorAll<HTMLElement>(
          'button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (e.shiftKey && document.activeElement === firstElement) {
          e.preventDefault();
          lastElement?.focus();
        } else if (!e.shiftKey && document.activeElement === lastElement) {
          e.preventDefault();
          firstElement?.focus();
        }
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, dontShowAgain, pageId, dismissGuide, closeGuide]);

  // Don't render if not open, wrong page, or missing guide data
  if (!isOpen || !guide || !guide.steps || guide.steps.length === 0) {
    return null;
  }

  const totalSteps = guide.steps.length;
  const safeCurrentStep = Math.min(currentStep, totalSteps - 1);
  const step = guide.steps[safeCurrentStep];

  // Safety check for step
  if (!step) {
    return null;
  }

  const isFirstStep = safeCurrentStep === 0;
  const isLastStep = safeCurrentStep === totalSteps - 1;
  const StepIcon = getIcon(step.icon);

  const handleClose = () => {
    if (dontShowAgain) {
      dismissGuide(pageId);
    } else {
      closeGuide();
    }
  };

  const handleNext = () => {
    if (isLastStep) {
      dismissGuide(pageId);
    } else {
      nextStep(totalSteps);
    }
  };

  const handlePrev = () => {
    prevStep();
  };

  return createPortal(
    <div
      ref={dialogRef}
      className="fixed bottom-6 right-6 z-[100] w-[380px] max-w-[calc(100vw-48px)] animate-in slide-in-from-bottom-4 fade-in duration-300"
      role="dialog"
      aria-modal="true"
      aria-labelledby="page-guide-title"
      aria-describedby="page-guide-step-content"
    >
      <div className="bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden">
        {/* Header */}
        <div className="bg-gradient-to-r from-primary-50 to-primary-100/50 px-5 py-4 border-b border-primary-100">
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center shadow-sm">
                <BookOpen className="w-5 h-5 text-white" aria-hidden="true" />
              </div>
              <div>
                <h2 id="page-guide-title" className="font-semibold text-slate-900">
                  {guide.title}
                </h2>
                <p className="text-sm text-slate-600">{guide.subtitle}</p>
              </div>
            </div>
            <button
              ref={closeButtonRef}
              onClick={handleClose}
              className="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-white/80 rounded-lg transition-colors"
              aria-label="Close guide"
            >
              <X className="w-5 h-5" aria-hidden="true" />
            </button>
          </div>
        </div>

        {/* Step Content */}
        <div className="px-5 py-4">
          {/* Step indicator */}
          <div className="flex items-center justify-between mb-4">
            <span className="text-xs font-medium text-slate-500">
              Step {safeCurrentStep + 1} of {totalSteps}
            </span>
            <div className="flex gap-1.5" role="tablist" aria-label="Guide steps">
              {guide.steps.map((s, index) => (
                <button
                  key={index}
                  onClick={() => goToStep(index)}
                  className={clsx(
                    'w-2 h-2 rounded-full transition-all',
                    index === safeCurrentStep
                      ? 'bg-primary-600 w-4'
                      : index < safeCurrentStep
                      ? 'bg-primary-300'
                      : 'bg-slate-200 hover:bg-slate-300'
                  )}
                  role="tab"
                  aria-selected={index === safeCurrentStep}
                  aria-label={`Step ${index + 1}: ${s.title}${index === safeCurrentStep ? ' (current)' : ''}`}
                />
              ))}
            </div>
          </div>

          {/* Step card */}
          <div
            id="page-guide-step-content"
            className="bg-slate-50 rounded-lg p-4 border border-slate-100"
            role="tabpanel"
            aria-label={`Step ${safeCurrentStep + 1}: ${step.title}`}
          >
            <div className="flex items-start gap-3 mb-3">
              <div className="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm border border-slate-200 flex-shrink-0">
                <StepIcon className="w-5 h-5 text-primary-600" aria-hidden="true" />
              </div>
              <div>
                <h3 className="font-medium text-slate-900">{step.title}</h3>
              </div>
            </div>

            <p className="text-sm text-slate-600 leading-relaxed mb-4">
              {step.description}
            </p>

            {/* Expected outcome */}
            {step.expected && (
              <div className="flex items-start gap-2 mb-3 p-2.5 bg-green-50 rounded-lg border border-green-100">
                <CheckCircle2 className="w-4 h-4 text-green-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
                <div>
                  <span className="text-xs font-medium text-green-800 block mb-0.5">
                    What you'll see:
                  </span>
                  <span className="text-xs text-green-700">{step.expected}</span>
                </div>
              </div>
            )}

            {/* Troubleshooting tip */}
            {step.troubleshoot && (
              <div className="flex items-start gap-2 p-2.5 bg-amber-50 rounded-lg border border-amber-100">
                <Lightbulb className="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
                <div>
                  <span className="text-xs font-medium text-amber-800 block mb-0.5">
                    Tip:
                  </span>
                  <span className="text-xs text-amber-700">{step.troubleshoot}</span>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Footer */}
        <div className="px-5 py-3 bg-slate-50 border-t border-slate-100">
          {/* Don't show again */}
          <label className="flex items-center gap-2 mb-3 cursor-pointer group">
            <input
              type="checkbox"
              checked={dontShowAgain}
              onChange={(e) => setDontShowAgain(e.target.checked)}
              className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
            />
            <span className="text-xs text-slate-500 group-hover:text-slate-700">
              Don't show this guide again
            </span>
          </label>

          {/* Navigation */}
          <div className="flex items-center justify-between">
            <Button
              variant="ghost"
              size="sm"
              onClick={handlePrev}
              disabled={isFirstStep}
              className={clsx(isFirstStep && 'invisible')}
              aria-label={`Previous step: ${safeCurrentStep > 0 ? guide.steps[safeCurrentStep - 1]?.title : ''}`}
            >
              <ChevronLeft className="w-4 h-4 mr-1" aria-hidden="true" />
              Previous
            </Button>

            <Button
              size="sm"
              onClick={handleNext}
              aria-label={isLastStep ? 'Complete guide' : `Next step: ${guide.steps[safeCurrentStep + 1]?.title || ''}`}
            >
              {isLastStep ? 'Got it!' : 'Next'}
              {!isLastStep && <ChevronRight className="w-4 h-4 ml-1" aria-hidden="true" />}
            </Button>
          </div>

          {/* Live region for screen reader announcements */}
          <div
            role="status"
            aria-live="polite"
            aria-atomic="true"
            className="sr-only"
          >
            Step {safeCurrentStep + 1} of {totalSteps}: {step.title}
          </div>
        </div>
      </div>
    </div>,
    getPortalRoot()
  );
}

// Button to trigger opening the guide
export function PageGuideButton({ pageId }: { pageId: string }) {
  const { dismissedGuides, openGuide } = usePageGuideStore();
  const guide = getPageGuide(pageId);
  const safeDismissed = Array.isArray(dismissedGuides) ? dismissedGuides : [];

  // Don't show button if guide doesn't exist or was dismissed
  if (!guide || safeDismissed.includes(pageId)) {
    return null;
  }

  return (
    <button
      onClick={() => openGuide(pageId)}
      className="flex items-center gap-1.5 px-2.5 py-1.5 text-sm text-slate-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
      aria-label={`Open ${guide.title} guide`}
    >
      <BookOpen className="w-4 h-4" aria-hidden="true" />
      <span className="hidden sm:inline">Guide</span>
    </button>
  );
}

// Hook for auto-showing guide on first visit
export function useAutoShowGuide(pageId: string) {
  const { shouldAutoShow, openGuide } = usePageGuideStore();
  const guide = getPageGuide(pageId);

  useEffect(() => {
    if (guide && shouldAutoShow(pageId)) {
      // Small delay for page to render
      const timer = setTimeout(() => {
        openGuide(pageId);
      }, 500);
      return () => clearTimeout(timer);
    }
  }, [pageId, guide, shouldAutoShow, openGuide]);
}

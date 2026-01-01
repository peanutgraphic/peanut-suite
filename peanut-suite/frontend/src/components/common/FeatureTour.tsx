import { useEffect, useState, useCallback } from 'react';
import { createPortal } from 'react-dom';
import { useNavigate, useLocation } from 'react-router-dom';
import { getPortalRoot } from '../../utils/portalRoot';
import { X, ChevronLeft, ChevronRight, SkipForward, Link2, Users, BarChart2, MessageSquare, Rocket } from 'lucide-react';
import { clsx } from 'clsx';
import { useTourStore, tourSteps } from '../../store/useTourStore';
import Button from './Button';

interface SpotlightPosition {
  top: number;
  left: number;
  width: number;
  height: number;
}

interface TooltipPosition {
  top: number;
  left: number;
}

export default function FeatureTour() {
  const navigate = useNavigate();
  const location = useLocation();
  const {
    isActive,
    currentStep,
    nextStep,
    prevStep,
    skipTour,
    endTour,
    hasSeenWelcome,
  } = useTourStore();

  const [spotlight, setSpotlight] = useState<SpotlightPosition | null>(null);
  const [tooltipPos, setTooltipPos] = useState<TooltipPosition | null>(null);
  const [isTransitioning, setIsTransitioning] = useState(false);

  const step = tourSteps[currentStep];
  const isFirstStep = currentStep === 0;
  const isLastStep = currentStep === tourSteps.length - 1;

  // Calculate element position
  const updatePositions = useCallback(() => {
    if (!step) return;

    const element = document.querySelector(step.target);
    if (!element) {
      // Element not found, might need to navigate
      setSpotlight(null);
      return;
    }

    const rect = element.getBoundingClientRect();
    const padding = 8;

    // Spotlight position (with padding)
    setSpotlight({
      top: rect.top - padding,
      left: rect.left - padding,
      width: rect.width + padding * 2,
      height: rect.height + padding * 2,
    });

    // Calculate tooltip position based on placement
    const tooltipWidth = 320;
    const tooltipHeight = 240; // Increased to account for actual tooltip content
    const offset = 16;
    const viewportPadding = 24; // Padding from viewport edges

    let top = 0;
    let left = 0;

    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    // Determine best placement - auto-flip if not enough space
    let placement = step.placement || 'bottom';

    // Check if there's enough space for the preferred placement
    const spaceAbove = rect.top;
    const spaceBelow = viewportHeight - rect.bottom;
    const spaceLeft = rect.left;
    const spaceRight = viewportWidth - rect.right;

    // Auto-flip vertical placements
    if (placement === 'bottom' && spaceBelow < tooltipHeight + offset + viewportPadding) {
      if (spaceAbove > spaceBelow) placement = 'top';
    } else if (placement === 'top' && spaceAbove < tooltipHeight + offset + viewportPadding) {
      if (spaceBelow > spaceAbove) placement = 'bottom';
    }

    // Auto-flip horizontal placements
    if (placement === 'right' && spaceRight < tooltipWidth + offset + viewportPadding) {
      if (spaceLeft > spaceRight) placement = 'left';
    } else if (placement === 'left' && spaceLeft < tooltipWidth + offset + viewportPadding) {
      if (spaceRight > spaceLeft) placement = 'right';
    }

    switch (placement) {
      case 'right':
        top = rect.top + rect.height / 2 - tooltipHeight / 2;
        left = rect.right + offset;
        break;
      case 'left':
        top = rect.top + rect.height / 2 - tooltipHeight / 2;
        left = rect.left - tooltipWidth - offset;
        break;
      case 'bottom':
        top = rect.bottom + offset;
        left = rect.left + rect.width / 2 - tooltipWidth / 2;
        break;
      case 'top':
      default:
        top = rect.top - tooltipHeight - offset;
        left = rect.left + rect.width / 2 - tooltipWidth / 2;
        break;
    }

    // Keep tooltip in viewport with padding
    if (left < viewportPadding) left = viewportPadding;
    if (left + tooltipWidth > viewportWidth - viewportPadding) {
      left = viewportWidth - tooltipWidth - viewportPadding;
    }
    if (top < viewportPadding) top = viewportPadding;
    if (top + tooltipHeight > viewportHeight - viewportPadding) {
      top = viewportHeight - tooltipHeight - viewportPadding;
    }

    setTooltipPos({ top, left });
  }, [step]);

  // Navigate to route if needed
  useEffect(() => {
    if (!isActive || !step?.route) return;

    if (location.pathname !== step.route) {
      setIsTransitioning(true);
      navigate(step.route);
      // Wait for navigation to complete
      setTimeout(() => {
        setIsTransitioning(false);
        updatePositions();
      }, 100);
    }
  }, [isActive, step, location.pathname, navigate, updatePositions]);

  // Update positions on step change or resize
  useEffect(() => {
    if (!isActive) return;

    updatePositions();

    // Retry a few times in case element hasn't rendered
    const retries = [50, 150, 300, 500];
    const timeouts = retries.map((delay) =>
      setTimeout(updatePositions, delay)
    );

    window.addEventListener('resize', updatePositions);
    window.addEventListener('scroll', updatePositions, true);

    return () => {
      timeouts.forEach(clearTimeout);
      window.removeEventListener('resize', updatePositions);
      window.removeEventListener('scroll', updatePositions, true);
    };
  }, [isActive, currentStep, updatePositions]);

  // Handle keyboard navigation
  useEffect(() => {
    if (!isActive) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      switch (e.key) {
        case 'ArrowRight':
        case 'Enter':
          nextStep();
          break;
        case 'ArrowLeft':
          prevStep();
          break;
        case 'Escape':
          skipTour();
          break;
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isActive, nextStep, prevStep, skipTour]);

  // Don't render if tour isn't active, no step, or welcome modal hasn't been dismissed yet
  if (!isActive || !step || !hasSeenWelcome) return null;

  const progress = ((currentStep + 1) / tourSteps.length) * 100;

  return createPortal(
    <div className="fixed inset-0 z-[2147483647] pointer-events-auto" aria-modal="true" role="dialog">
      {/* Overlay with spotlight cutout */}
      <svg
        className="absolute inset-0 w-full h-full pointer-events-none"
        style={{ transition: 'opacity 0.2s' }}
      >
        <defs>
          <mask id="spotlight-mask">
            <rect x="0" y="0" width="100%" height="100%" fill="white" />
            {spotlight && (
              <rect
                x={spotlight.left}
                y={spotlight.top}
                width={spotlight.width}
                height={spotlight.height}
                rx="8"
                fill="black"
                style={{ transition: 'all 0.3s ease-out' }}
              />
            )}
          </mask>
        </defs>
        <rect
          x="0"
          y="0"
          width="100%"
          height="100%"
          fill="rgba(0, 0, 0, 0.5)"
          mask="url(#spotlight-mask)"
        />
      </svg>

      {/* Spotlight border highlight */}
      {spotlight && (
        <div
          className="absolute rounded-lg ring-4 ring-primary-500 ring-opacity-50 pointer-events-none"
          style={{
            top: spotlight.top,
            left: spotlight.left,
            width: spotlight.width,
            height: spotlight.height,
            transition: 'all 0.3s ease-out',
            boxShadow: '0 0 0 4px rgba(99, 102, 241, 0.3), 0 0 20px rgba(99, 102, 241, 0.4)',
          }}
        />
      )}

      {/* Tooltip - only render when position is calculated */}
      {tooltipPos && (
      <div
        className={clsx(
          'absolute w-80 bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden',
          isTransitioning && 'opacity-0'
        )}
        style={{
          top: tooltipPos.top,
          left: tooltipPos.left,
          transition: 'all 0.3s ease-out, opacity 0.15s',
        }}
      >
        {/* Progress bar */}
        <div className="h-1 bg-slate-100">
          <div
            className="h-full bg-primary-500 transition-all duration-300"
            style={{ width: `${progress}%` }}
          />
        </div>

        {/* Content */}
        <div className="p-5">
          <div className="flex items-start justify-between mb-3">
            <h3 className="text-lg font-semibold text-slate-900 pr-6">{step.title}</h3>
            <button
              onClick={skipTour}
              className="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded transition-colors"
              title="Close tour"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          <p className="text-slate-600 text-sm leading-relaxed mb-5">
            {step.content}
          </p>

          {/* Navigation */}
          <div className="flex items-center justify-between">
            <span className="text-xs text-slate-400">
              {currentStep + 1} of {tourSteps.length}
            </span>

            <div className="flex items-center gap-2">
              {!isFirstStep && (
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={prevStep}
                  className="!px-2"
                >
                  <ChevronLeft className="w-4 h-4" />
                </Button>
              )}

              {!isLastStep && (
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={skipTour}
                  className="text-slate-500"
                >
                  <SkipForward className="w-4 h-4 mr-1" />
                  Skip
                </Button>
              )}

              <Button
                size="sm"
                onClick={isLastStep ? endTour : nextStep}
              >
                {isLastStep ? 'Finish' : 'Next'}
                {!isLastStep && <ChevronRight className="w-4 h-4 ml-1" />}
              </Button>
            </div>
          </div>
        </div>
      </div>
      )}
    </div>,
    getPortalRoot()
  );
}

// Welcome modal for first-time users
export function WelcomeModal() {
  const { hasSeenWelcome, setHasSeenWelcome, startTour } = useTourStore();
  const [isOpen, setIsOpen] = useState(false);

  useEffect(() => {
    // Show welcome after a short delay on first visit
    if (!hasSeenWelcome) {
      const timer = setTimeout(() => setIsOpen(true), 500);
      return () => clearTimeout(timer);
    }
  }, [hasSeenWelcome]);

  const handleStartTour = () => {
    setIsOpen(false);
    setHasSeenWelcome(true);
    // Delay tour start to let welcome modal fully close first
    setTimeout(() => {
      startTour();
    }, 350);
  };

  const handleSkip = () => {
    setIsOpen(false);
    setHasSeenWelcome(true);
  };

  if (!isOpen) return null;

  return createPortal(
    <div className="fixed inset-0 z-[2147483647] flex items-center justify-center p-4 pointer-events-auto">
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
        onClick={handleSkip}
      />

      {/* Modal */}
      <div className="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-in fade-in zoom-in-95 duration-300">
        {/* Hero illustration */}
        <div className="bg-gradient-to-br from-primary-500 to-primary-600 p-8 text-center">
          <div className="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-2xl mb-4">
            <Rocket className="w-10 h-10 text-white" />
          </div>
          <h2 className="text-2xl font-bold text-white mb-2">
            Welcome to Marketing Suite!
          </h2>
          <p className="text-primary-100">
            Your all-in-one marketing toolkit
          </p>
        </div>

        {/* Content */}
        <div className="p-6">
          <div className="space-y-3 mb-6">
            <FeatureItem
              icon={Link2}
              title="Track Campaigns"
              description="Create UTM links and short URLs"
            />
            <FeatureItem
              icon={Users}
              title="Manage Contacts"
              description="Build and segment your audience"
            />
            <FeatureItem
              icon={BarChart2}
              title="Analyze Performance"
              description="Attribution and analytics insights"
            />
            <FeatureItem
              icon={MessageSquare}
              title="Convert Visitors"
              description="Popups and lead capture forms"
            />
          </div>

          <div className="flex gap-3">
            <Button
              variant="outline"
              className="flex-1"
              onClick={handleSkip}
            >
              Skip Tour
            </Button>
            <Button
              className="flex-1"
              onClick={handleStartTour}
            >
              Take the Tour
            </Button>
          </div>
        </div>
      </div>
    </div>,
    getPortalRoot()
  );
}

function FeatureItem({ icon: Icon, title, description }: { icon: React.ElementType; title: string; description: string }) {
  return (
    <div className="flex items-center gap-3">
      <div className="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center flex-shrink-0">
        <Icon className="w-5 h-5 text-primary-600" />
      </div>
      <div>
        <p className="font-medium text-slate-900">{title}</p>
        <p className="text-sm text-slate-500">{description}</p>
      </div>
    </div>
  );
}

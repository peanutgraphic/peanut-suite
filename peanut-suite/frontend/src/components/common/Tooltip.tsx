import { useState, useRef, useEffect, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { clsx } from 'clsx';
import { getPortalRoot } from '../../utils/portalRoot';

interface TooltipProps {
  content: ReactNode;
  children: ReactNode;
  position?: 'top' | 'bottom' | 'left' | 'right';
  className?: string;
}

export default function Tooltip({
  content,
  children,
  position = 'top',
  className,
}: TooltipProps) {
  const [isVisible, setIsVisible] = useState(false);
  const [coords, setCoords] = useState({ top: 0, left: 0 });
  const [actualPosition, setActualPosition] = useState(position);
  const triggerRef = useRef<HTMLDivElement>(null);
  const tooltipRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (isVisible && triggerRef.current) {
      const rect = triggerRef.current.getBoundingClientRect();
      const tooltipWidth = 200; // max-w-[200px]
      const estimatedTooltipHeight = 150; // Estimate for auto-flip check
      const gap = 8;
      const padding = 8;

      // Determine best position (auto-flip if needed)
      let bestPosition = position;

      if (position === 'top' && rect.top < estimatedTooltipHeight + gap + padding) {
        bestPosition = 'bottom';
      } else if (position === 'bottom' && window.innerHeight - rect.bottom < estimatedTooltipHeight + gap + padding) {
        bestPosition = 'top';
      }

      setActualPosition(bestPosition);

      let top = 0;
      let left = 0;

      switch (bestPosition) {
        case 'top':
          top = rect.top - gap;
          left = rect.left + rect.width / 2;
          break;
        case 'bottom':
          top = rect.bottom + gap;
          left = rect.left + rect.width / 2;
          break;
        case 'left':
          top = rect.top + rect.height / 2;
          left = rect.left - gap;
          break;
        case 'right':
          top = rect.top + rect.height / 2;
          left = rect.right + gap;
          break;
      }

      // Keep tooltip within viewport horizontally
      const halfWidth = tooltipWidth / 2;
      if (left - halfWidth < padding) left = halfWidth + padding;
      if (left + halfWidth > window.innerWidth - padding) {
        left = window.innerWidth - halfWidth - padding;
      }

      setCoords({ top, left });
    }
  }, [isVisible, position]);

  const arrowClasses = {
    top: 'top-full left-1/2 -translate-x-1/2 border-t-slate-800 border-x-transparent border-b-transparent',
    bottom: 'bottom-full left-1/2 -translate-x-1/2 border-b-slate-800 border-x-transparent border-t-transparent',
    left: 'left-full top-1/2 -translate-y-1/2 border-l-slate-800 border-y-transparent border-r-transparent',
    right: 'right-full top-1/2 -translate-y-1/2 border-r-slate-800 border-y-transparent border-l-transparent',
  };

  const transformClasses = {
    top: '-translate-x-1/2 -translate-y-full',
    bottom: '-translate-x-1/2',
    left: '-translate-x-full -translate-y-1/2',
    right: '-translate-y-1/2',
  };

  return (
    <div
      ref={triggerRef}
      className="relative inline-flex"
      onMouseEnter={() => setIsVisible(true)}
      onMouseLeave={() => setIsVisible(false)}
    >
      {children}
      {isVisible && createPortal(
        <div
          ref={tooltipRef}
          className={clsx(
            'fixed z-[2147483647] px-3 py-2 text-sm text-white bg-slate-800 rounded-lg shadow-lg max-w-[200px] pointer-events-none',
            transformClasses[actualPosition],
            className
          )}
          style={{ top: coords.top, left: coords.left }}
        >
          {content}
          <div
            className={clsx(
              'absolute w-0 h-0 border-4',
              arrowClasses[actualPosition]
            )}
          />
        </div>,
        getPortalRoot()
      )}
    </div>
  );
}

// Info icon with tooltip
import { HelpCircle } from 'lucide-react';

interface InfoTooltipProps {
  content: ReactNode;
  position?: 'top' | 'bottom' | 'left' | 'right';
}

export function InfoTooltip({ content, position = 'top' }: InfoTooltipProps) {
  return (
    <Tooltip content={content} position={position}>
      <HelpCircle className="w-4 h-4 text-slate-400 hover:text-slate-600 cursor-help" />
    </Tooltip>
  );
}

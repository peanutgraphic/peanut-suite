import type { ReactNode } from 'react';
import { clsx } from 'clsx';
import {
  Inbox,
  Link2,
  Users,
  Target,
  BarChart3,
  Server,
  Bell,
  MousePointer,
  Zap,
  FileSearch,
  Eye,
} from 'lucide-react';

// SVG Illustrations for empty states
const illustrations = {
  // Generic empty box illustration
  empty: (
    <svg viewBox="0 0 200 150" className="w-full h-full" fill="none">
      {/* Shadow */}
      <ellipse cx="100" cy="130" rx="50" ry="8" fill="#f1f5f9"/>
      {/* Box body */}
      <path d="M50 50 L100 30 L150 50 L150 100 L100 120 L50 100 Z" fill="#e2e8f0" stroke="#cbd5e1" strokeWidth="2"/>
      <path d="M50 50 L100 70 L150 50" stroke="#cbd5e1" strokeWidth="2" fill="#f8fafc"/>
      <path d="M100 70 L100 120" stroke="#cbd5e1" strokeWidth="2"/>
      {/* Dashed lines indicating empty */}
      <path d="M70 85 L90 85" stroke="#94a3b8" strokeWidth="2" strokeDasharray="4 2"/>
      <path d="M110 85 L130 85" stroke="#94a3b8" strokeWidth="2" strokeDasharray="4 2"/>
    </svg>
  ),

  // Links illustration - chain links with plus
  links: (
    <svg viewBox="0 0 200 150" className="w-full h-full" fill="none">
      {/* Shadow */}
      <ellipse cx="100" cy="125" rx="60" ry="8" fill="#f1f5f9"/>
      {/* First link */}
      <rect x="40" y="55" width="50" height="24" rx="12" fill="#dbeafe" stroke="#93c5fd" strokeWidth="2"/>
      <rect x="50" y="62" width="30" height="10" rx="5" fill="#93c5fd"/>
      {/* Chain connector */}
      <path d="M90 67 L110 67" stroke="#cbd5e1" strokeWidth="3" strokeLinecap="round"/>
      {/* Second link */}
      <rect x="110" y="55" width="50" height="24" rx="12" fill="#dbeafe" stroke="#93c5fd" strokeWidth="2"/>
      <rect x="120" y="62" width="30" height="10" rx="5" fill="#93c5fd"/>
      {/* Plus button */}
      <circle cx="100" cy="100" r="18" fill="#3b82f6"/>
      <path d="M92 100 L108 100 M100 92 L100 108" stroke="white" strokeWidth="3" strokeLinecap="round"/>
    </svg>
  ),

  // Contacts/Users illustration - address book style
  contacts: (
    <svg viewBox="0 0 200 150" className="w-full h-full" fill="none">
      {/* Shadow */}
      <ellipse cx="100" cy="130" rx="55" ry="8" fill="#f1f5f9"/>
      {/* Book/Card base */}
      <rect x="45" y="25" width="110" height="95" rx="8" fill="#f8fafc" stroke="#e2e8f0" strokeWidth="2"/>
      {/* Spine */}
      <rect x="45" y="25" width="15" height="95" rx="4" fill="#dbeafe"/>
      {/* Contact card 1 */}
      <rect x="70" y="35" width="75" height="25" rx="4" fill="#dbeafe"/>
      <circle cx="82" cy="47" r="8" fill="#93c5fd"/>
      <rect x="95" y="42" width="40" height="4" rx="2" fill="#93c5fd"/>
      <rect x="95" y="49" width="25" height="3" rx="1" fill="#bfdbfe"/>
      {/* Contact card 2 */}
      <rect x="70" y="65" width="75" height="25" rx="4" fill="#e0e7ff"/>
      <circle cx="82" cy="77" r="8" fill="#a5b4fc"/>
      <rect x="95" y="72" width="40" height="4" rx="2" fill="#a5b4fc"/>
      <rect x="95" y="79" width="25" height="3" rx="1" fill="#c7d2fe"/>
      {/* Contact card 3 */}
      <rect x="70" y="95" width="75" height="20" rx="4" fill="#fce7f3"/>
      <circle cx="82" cy="105" r="6" fill="#f9a8d4"/>
      <rect x="95" y="102" width="35" height="4" rx="2" fill="#f9a8d4"/>
    </svg>
  ),

  // Visitors illustration - footprints/activity
  visitors: (
    <svg viewBox="0 0 200 150" className="w-full h-full" fill="none">
      {/* Shadow */}
      <ellipse cx="100" cy="130" rx="60" ry="8" fill="#f1f5f9"/>
      {/* Browser window */}
      <rect x="40" y="25" width="120" height="90" rx="8" fill="#f8fafc" stroke="#e2e8f0" strokeWidth="2"/>
      {/* Browser bar */}
      <rect x="40" y="25" width="120" height="20" rx="8" fill="#f1f5f9"/>
      <circle cx="52" cy="35" r="4" fill="#ef4444"/>
      <circle cx="64" cy="35" r="4" fill="#f59e0b"/>
      <circle cx="76" cy="35" r="4" fill="#22c55e"/>
      {/* Address bar */}
      <rect x="88" y="31" width="60" height="8" rx="4" fill="#e2e8f0"/>
      {/* Cursor trail showing visitors */}
      <circle cx="70" cy="60" r="3" fill="#3b82f6" opacity="0.3"/>
      <circle cx="85" cy="70" r="4" fill="#3b82f6" opacity="0.5"/>
      <circle cx="105" cy="65" r="5" fill="#3b82f6" opacity="0.7"/>
      <circle cx="125" cy="80" r="6" fill="#3b82f6"/>
      {/* Cursor */}
      <path d="M120 75 L120 95 L128 88 L135 98 L140 95 L133 85 L142 85 Z" fill="#3b82f6"/>
      {/* Stats bars at bottom */}
      <rect x="50" y="100" width="15" height="8" rx="2" fill="#dbeafe"/>
      <rect x="70" y="95" width="15" height="13" rx="2" fill="#93c5fd"/>
      <rect x="90" y="90" width="15" height="18" rx="2" fill="#60a5fa"/>
      <rect x="110" y="85" width="15" height="23" rx="2" fill="#3b82f6"/>
      <rect x="130" y="92" width="15" height="16" rx="2" fill="#93c5fd"/>
    </svg>
  ),

  // UTM/Campaign illustration - tag with tracking
  utm: (
    <svg viewBox="0 0 200 150" className="w-full h-full" fill="none">
      {/* Shadow */}
      <ellipse cx="100" cy="130" rx="55" ry="8" fill="#f1f5f9"/>
      {/* URL bar */}
      <rect x="30" y="35" width="140" height="30" rx="6" fill="#f8fafc" stroke="#e2e8f0" strokeWidth="2"/>
      <rect x="40" y="45" width="80" height="10" rx="3" fill="#e2e8f0"/>
      {/* UTM tags */}
      <g transform="translate(0, 10)">
        <rect x="35" y="70" width="45" height="22" rx="4" fill="#dbeafe" stroke="#93c5fd" strokeWidth="1.5"/>
        <text x="57" y="84" textAnchor="middle" fill="#3b82f6" fontSize="8" fontWeight="600">source</text>
      </g>
      <g transform="translate(50, 10)">
        <rect x="35" y="70" width="50" height="22" rx="4" fill="#dcfce7" stroke="#86efac" strokeWidth="1.5"/>
        <text x="60" y="84" textAnchor="middle" fill="#22c55e" fontSize="8" fontWeight="600">medium</text>
      </g>
      <g transform="translate(105, 10)">
        <rect x="35" y="70" width="55" height="22" rx="4" fill="#fef3c7" stroke="#fcd34d" strokeWidth="1.5"/>
        <text x="62" y="84" textAnchor="middle" fill="#f59e0b" fontSize="8" fontWeight="600">campaign</text>
      </g>
      {/* Connecting lines */}
      <path d="M100 65 L57 80" stroke="#cbd5e1" strokeWidth="1.5" strokeDasharray="3 2"/>
      <path d="M100 65 L110 80" stroke="#cbd5e1" strokeWidth="1.5" strokeDasharray="3 2"/>
      <path d="M100 65 L162 80" stroke="#cbd5e1" strokeWidth="1.5" strokeDasharray="3 2"/>
      {/* Arrow down */}
      <path d="M100 100 L100 115 M93 108 L100 115 L107 108" stroke="#94a3b8" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
  ),

  // Analytics/Chart illustration - dashboard style
  analytics: (
    <svg viewBox="0 0 200 150" className="w-full h-full" fill="none">
      {/* Shadow */}
      <ellipse cx="100" cy="130" rx="60" ry="8" fill="#f1f5f9"/>
      {/* Chart background */}
      <rect x="30" y="25" width="140" height="95" rx="8" fill="#f8fafc" stroke="#e2e8f0" strokeWidth="2"/>
      {/* Grid lines */}
      <path d="M45 35 L45 105" stroke="#f1f5f9" strokeWidth="1"/>
      <path d="M45 55 L155 55" stroke="#f1f5f9" strokeWidth="1"/>
      <path d="M45 75 L155 75" stroke="#f1f5f9" strokeWidth="1"/>
      <path d="M45 95 L155 95" stroke="#f1f5f9" strokeWidth="1"/>
      {/* Bars */}
      <rect x="55" y="75" width="18" height="30" rx="3" fill="#dbeafe"/>
      <rect x="80" y="55" width="18" height="50" rx="3" fill="#93c5fd"/>
      <rect x="105" y="45" width="18" height="60" rx="3" fill="#60a5fa"/>
      <rect x="130" y="35" width="18" height="70" rx="3" fill="#3b82f6"/>
      {/* Trend line */}
      <path d="M64 70 L89 50 L114 40 L139 30" stroke="#22c55e" strokeWidth="2" strokeLinecap="round" fill="none"/>
      <circle cx="64" cy="70" r="3" fill="#22c55e"/>
      <circle cx="89" cy="50" r="3" fill="#22c55e"/>
      <circle cx="114" cy="40" r="3" fill="#22c55e"/>
      <circle cx="139" cy="30" r="3" fill="#22c55e"/>
    </svg>
  ),

  // Server/Monitor illustration - dashboard with status
  monitor: (
    <svg viewBox="0 0 200 150" className="w-full h-full" fill="none">
      {/* Shadow */}
      <ellipse cx="100" cy="130" rx="55" ry="8" fill="#f1f5f9"/>
      {/* Monitor screen */}
      <rect x="45" y="25" width="110" height="75" rx="8" fill="#1e293b" stroke="#334155" strokeWidth="2"/>
      {/* Screen content - terminal style */}
      <rect x="55" y="35" width="90" height="55" rx="4" fill="#0f172a"/>
      {/* Status indicators */}
      <circle cx="70" cy="50" r="5" fill="#22c55e"/>
      <rect x="82" y="47" width="50" height="6" rx="2" fill="#334155"/>
      <circle cx="70" cy="65" r="5" fill="#22c55e"/>
      <rect x="82" y="62" width="40" height="6" rx="2" fill="#334155"/>
      <circle cx="70" cy="80" r="5" fill="#f59e0b"/>
      <rect x="82" y="77" width="45" height="6" rx="2" fill="#334155"/>
      {/* Stand */}
      <rect x="90" y="100" width="20" height="8" fill="#e2e8f0"/>
      <rect x="80" y="108" width="40" height="6" rx="2" fill="#cbd5e1"/>
    </svg>
  ),

  // Webhooks illustration - data flow
  webhooks: (
    <svg viewBox="0 0 200 150" className="w-full h-full" fill="none">
      {/* Shadow */}
      <ellipse cx="100" cy="130" rx="55" ry="8" fill="#f1f5f9"/>
      {/* Source box */}
      <rect x="25" y="45" width="50" height="50" rx="8" fill="#f8fafc" stroke="#e2e8f0" strokeWidth="2"/>
      <rect x="35" y="55" width="30" height="6" rx="2" fill="#dbeafe"/>
      <rect x="35" y="65" width="25" height="6" rx="2" fill="#e0e7ff"/>
      <rect x="35" y="75" width="28" height="6" rx="2" fill="#dbeafe"/>
      {/* Data packets flying */}
      <rect x="85" y="55" width="12" height="8" rx="2" fill="#3b82f6"/>
      <rect x="105" y="65" width="12" height="8" rx="2" fill="#60a5fa"/>
      <rect x="92" y="78" width="12" height="8" rx="2" fill="#93c5fd"/>
      {/* Arrow path */}
      <path d="M75 70 C95 70, 95 70, 125 70" stroke="#cbd5e1" strokeWidth="2" strokeDasharray="4 2"/>
      {/* Target box */}
      <rect x="125" y="45" width="50" height="50" rx="8" fill="#dcfce7" stroke="#86efac" strokeWidth="2"/>
      <circle cx="150" cy="70" r="12" fill="#22c55e"/>
      <path d="M144 70 L148 74 L156 66" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
  ),

  // Popups illustration - modal window
  popups: (
    <svg viewBox="0 0 200 150" className="w-full h-full" fill="none">
      {/* Shadow */}
      <ellipse cx="100" cy="130" rx="55" ry="8" fill="#f1f5f9"/>
      {/* Background page */}
      <rect x="35" y="25" width="130" height="95" rx="6" fill="#f1f5f9" stroke="#e2e8f0" strokeWidth="2"/>
      <rect x="45" y="35" width="80" height="6" rx="2" fill="#e2e8f0"/>
      <rect x="45" y="45" width="60" height="6" rx="2" fill="#e2e8f0"/>
      <rect x="45" y="55" width="70" height="6" rx="2" fill="#e2e8f0"/>
      {/* Popup modal */}
      <rect x="55" y="45" width="100" height="70" rx="8" fill="white" stroke="#3b82f6" strokeWidth="2" filter="drop-shadow(0 4px 6px rgba(0,0,0,0.1))"/>
      {/* Close button */}
      <circle cx="145" cy="55" r="8" fill="#fee2e2"/>
      <path d="M142 52 L148 58 M148 52 L142 58" stroke="#ef4444" strokeWidth="2" strokeLinecap="round"/>
      {/* Modal content */}
      <rect x="65" y="65" width="60" height="6" rx="2" fill="#e2e8f0"/>
      <rect x="65" y="75" width="40" height="4" rx="1" fill="#f1f5f9"/>
      {/* Input field */}
      <rect x="65" y="85" width="80" height="10" rx="3" fill="#f8fafc" stroke="#e2e8f0" strokeWidth="1"/>
      {/* CTA button */}
      <rect x="85" y="100" width="40" height="10" rx="4" fill="#3b82f6"/>
    </svg>
  ),

  // Search/Not found illustration - magnifying glass with X
  search: (
    <svg viewBox="0 0 200 150" className="w-full h-full" fill="none">
      {/* Shadow */}
      <ellipse cx="100" cy="130" rx="45" ry="6" fill="#f1f5f9"/>
      {/* Magnifying glass */}
      <circle cx="85" cy="65" r="35" fill="#f8fafc" stroke="#cbd5e1" strokeWidth="3"/>
      <circle cx="85" cy="65" r="25" fill="#f1f5f9"/>
      {/* Handle */}
      <path d="M110 90 L135 115" stroke="#cbd5e1" strokeWidth="8" strokeLinecap="round"/>
      <path d="M110 90 L135 115" stroke="#e2e8f0" strokeWidth="5" strokeLinecap="round"/>
      {/* X in the glass */}
      <path d="M75 55 L95 75 M95 55 L75 75" stroke="#94a3b8" strokeWidth="3" strokeLinecap="round"/>
      {/* Question marks */}
      <text x="140" y="50" fill="#cbd5e1" fontSize="16" fontWeight="bold">?</text>
      <text x="50" y="110" fill="#e2e8f0" fontSize="12" fontWeight="bold">?</text>
    </svg>
  ),

  // Attribution illustration - journey path
  attribution: (
    <svg viewBox="0 0 200 150" className="w-full h-full" fill="none">
      {/* Shadow */}
      <ellipse cx="100" cy="130" rx="60" ry="8" fill="#f1f5f9"/>
      {/* Curved path */}
      <path d="M40 75 Q70 40, 100 75 Q130 110, 160 75" stroke="#e2e8f0" strokeWidth="4" fill="none"/>
      {/* Touchpoint 1 */}
      <circle cx="40" cy="75" r="16" fill="#dbeafe" stroke="#93c5fd" strokeWidth="2"/>
      <text x="40" y="80" textAnchor="middle" fill="#3b82f6" fontSize="12" fontWeight="bold">1</text>
      {/* Touchpoint 2 */}
      <circle cx="100" cy="75" r="16" fill="#e0e7ff" stroke="#a5b4fc" strokeWidth="2"/>
      <text x="100" y="80" textAnchor="middle" fill="#6366f1" fontSize="12" fontWeight="bold">2</text>
      {/* Touchpoint 3 - conversion */}
      <circle cx="160" cy="75" r="16" fill="#dcfce7" stroke="#86efac" strokeWidth="2"/>
      <path d="M154 75 L158 79 L166 71" stroke="#22c55e" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
      {/* Dotted arrows */}
      <path d="M56 68 L75 55" stroke="#94a3b8" strokeWidth="1.5" strokeDasharray="3 2" markerEnd="url(#arrow)"/>
      <path d="M116 82 L140 95" stroke="#94a3b8" strokeWidth="1.5" strokeDasharray="3 2"/>
      {/* Labels */}
      <text x="40" y="105" textAnchor="middle" fill="#64748b" fontSize="8">First Touch</text>
      <text x="100" y="105" textAnchor="middle" fill="#64748b" fontSize="8">Touchpoint</text>
      <text x="160" y="105" textAnchor="middle" fill="#64748b" fontSize="8">Conversion</text>
    </svg>
  ),
};

// Icon mapping for fallback
const iconMap = {
  empty: Inbox,
  links: Link2,
  contacts: Users,
  visitors: Eye,
  utm: Target,
  analytics: BarChart3,
  monitor: Server,
  webhooks: Bell,
  popups: MousePointer,
  search: FileSearch,
  attribution: Zap,
};

export type EmptyStateType = keyof typeof illustrations;

interface EmptyStateProps {
  type?: EmptyStateType;
  icon?: ReactNode;
  title: string;
  description?: string;
  action?: ReactNode;
  className?: string;
  compact?: boolean;
}

export default function EmptyState({
  type = 'empty',
  icon,
  title,
  description,
  action,
  className,
  compact = false,
}: EmptyStateProps) {
  const FallbackIcon = iconMap[type] || Inbox;

  return (
    <div
      className={clsx(
        'flex flex-col items-center justify-center text-center',
        compact ? 'py-8 px-4' : 'py-16 px-6',
        className
      )}
    >
      {/* Illustration or Icon */}
      <div className={clsx(
        'mb-4',
        compact ? 'w-24 h-20' : 'w-40 h-32'
      )}>
        {icon ? (
          <div className="w-full h-full flex items-center justify-center">
            <div className="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
              {icon}
            </div>
          </div>
        ) : (
          illustrations[type] || (
            <div className="w-full h-full flex items-center justify-center">
              <div className="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                <FallbackIcon className="w-8 h-8" />
              </div>
            </div>
          )
        )}
      </div>

      {/* Title */}
      <h3 className={clsx(
        'font-semibold text-slate-900',
        compact ? 'text-base mb-1' : 'text-lg mb-2'
      )}>
        {title}
      </h3>

      {/* Description */}
      {description && (
        <p className={clsx(
          'text-slate-500 max-w-md',
          compact ? 'text-sm mb-3' : 'text-base mb-6'
        )}>
          {description}
        </p>
      )}

      {/* Action */}
      {action && <div>{action}</div>}
    </div>
  );
}

// Pre-configured empty states for each module
export const emptyStates = {
  links: {
    type: 'links' as const,
    title: 'No links yet',
    description: 'Create short links to track clicks, measure campaign performance, and share branded URLs with your audience.',
  },
  contacts: {
    type: 'contacts' as const,
    title: 'No contacts yet',
    description: 'Start building your contact list. Import contacts, capture leads from forms, or add them manually to track customer journeys.',
  },
  utm: {
    type: 'utm' as const,
    title: 'No UTM campaigns yet',
    description: 'Create UTM-tagged URLs to track your marketing campaigns. See exactly which sources, mediums, and campaigns drive traffic.',
  },
  webhooks: {
    type: 'webhooks' as const,
    title: 'No webhooks received',
    description: 'Webhooks will appear here when external services send data to your site. Connect forms, payment processors, or CRMs to capture leads.',
  },
  monitor: {
    type: 'monitor' as const,
    title: 'No sites monitored',
    description: 'Add WordPress sites to monitor their health, uptime, and update status. Get alerts when issues are detected.',
  },
  analytics: {
    type: 'analytics' as const,
    title: 'No analytics data yet',
    description: 'Analytics will populate as visitors interact with your links and campaigns. Check back after driving some traffic.',
  },
  popups: {
    type: 'popups' as const,
    title: 'No popups created',
    description: 'Create engaging popups to capture leads, announce promotions, or guide visitors. Customize triggers, timing, and design.',
  },
  visitors: {
    type: 'visitors' as const,
    title: 'No visitors tracked yet',
    description: 'Add the tracking code to your website to start collecting visitor data. See who visits your site, what pages they view, and how they interact.',
  },
  attribution: {
    type: 'attribution' as const,
    title: 'No attribution data',
    description: 'See the complete customer journey from first touch to conversion. Attribution data appears when contacts interact with multiple touchpoints.',
  },
  search: {
    type: 'search' as const,
    title: 'No results found',
    description: 'Try adjusting your search terms or filters to find what you\'re looking for.',
  },
};

// Loading skeleton component
interface SkeletonProps {
  className?: string;
}

export function Skeleton({ className }: SkeletonProps) {
  return (
    <div
      className={clsx(
        'animate-pulse bg-slate-200 rounded',
        className
      )}
    />
  );
}

// Table loading skeleton
export function TableSkeleton({ rows = 5, columns = 4 }: { rows?: number; columns?: number }) {
  return (
    <div className="space-y-3">
      {Array.from({ length: rows }).map((_, i) => (
        <div key={i} className="flex gap-4">
          {Array.from({ length: columns }).map((_, j) => (
            <Skeleton key={j} className="h-8 flex-1" />
          ))}
        </div>
      ))}
    </div>
  );
}

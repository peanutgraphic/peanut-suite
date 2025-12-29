import { NavLink } from 'react-router-dom';
import { clsx } from 'clsx';
import {
  LayoutDashboard,
  Link2,
  Tag,
  Users,
  Webhook,
  Eye,
  GitBranch,
  BarChart2,
  MessageSquare,
  Activity,
  Settings,
  ChevronLeft,
  ChevronRight,
  Crown,
  UsersRound,
  Key,
  FileText,
} from 'lucide-react';
import { useVisibleFeatures, useIsAccountAdmin } from '@/store';
import type { FeatureKey } from '@/types';

interface SidebarProps {
  collapsed: boolean;
  onToggle: () => void;
}

// Get license data from WordPress
declare global {
  interface Window {
    peanutData?: {
      license?: {
        tier: 'free' | 'pro' | 'agency';
        isPro: boolean;
      };
      version?: string;
    };
  }
}

const getLicenseTier = () => window.peanutData?.license?.tier || 'free';
const getVersion = () => window.peanutData?.version || '1.0.0';
const hasTier = (required: 'free' | 'pro' | 'agency') => {
  const tierLevels = { free: 0, pro: 1, agency: 2 };
  return tierLevels[getLicenseTier()] >= tierLevels[required];
};

interface NavItem {
  name: string;
  href: string;
  icon: typeof LayoutDashboard;
  tier?: 'free' | 'pro' | 'agency';
  feature?: FeatureKey; // Maps nav item to a feature for permission checking
}

const mainNavigation: NavItem[] = [
  { name: 'Dashboard', href: '/', icon: LayoutDashboard },
  { name: 'UTM Builder', href: '/utm', icon: Tag, feature: 'utm' },
  { name: 'Links', href: '/links', icon: Link2, feature: 'links' },
  { name: 'Contacts', href: '/contacts', icon: Users, feature: 'contacts' },
  { name: 'Webhooks', href: '/webhooks', icon: Webhook, feature: 'webhooks' },
  { name: 'Visitors', href: '/visitors', icon: Eye, tier: 'pro', feature: 'visitors' },
  { name: 'Attribution', href: '/attribution', icon: GitBranch, tier: 'pro', feature: 'attribution' },
  { name: 'Analytics', href: '/analytics', icon: BarChart2, tier: 'pro', feature: 'analytics' },
  { name: 'Popups', href: '/popups', icon: MessageSquare, tier: 'pro', feature: 'popups' },
  { name: 'Monitor', href: '/monitor', icon: Activity, tier: 'agency', feature: 'monitor' },
];

const accountNavigation: NavItem[] = [
  { name: 'Team', href: '/team', icon: UsersRound },
  { name: 'API Keys', href: '/api-keys', icon: Key },
  { name: 'Audit Log', href: '/audit-log', icon: FileText },
  { name: 'Settings', href: '/settings', icon: Settings },
];

export default function Sidebar({ collapsed, onToggle }: SidebarProps) {
  const currentTier = getLicenseTier();
  const visibleFeatures = useVisibleFeatures();
  const isAdmin = useIsAccountAdmin();

  // Filter main navigation based on feature permissions
  // Dashboard always visible, other items filtered by permissions
  const filteredMainNav = mainNavigation.filter((item) => {
    // Dashboard is always visible
    if (!item.feature) return true;

    // Admins with tier access see all features (locked if tier not available)
    if (isAdmin) return true;

    // Non-admins only see features they have permission for
    return visibleFeatures.includes(item.feature);
  });

  return (
    <aside
      className={clsx(
        'fixed left-0 top-0 h-screen bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 transition-all duration-300 z-50 flex flex-col',
        collapsed ? 'w-16' : 'w-56'
      )}
      aria-label="Main navigation"
    >
      {/* Logo */}
      <div className="h-14 flex items-center justify-between px-4 border-b border-slate-200 dark:border-slate-800 flex-shrink-0">
        {!collapsed && (
          <span className="text-lg font-bold text-primary-600 dark:text-primary-400">Peanut Suite</span>
        )}
        <button
          onClick={onToggle}
          className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
          aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
          title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
        >
          {collapsed ? (
            <ChevronRight className="w-5 h-5" aria-hidden="true" />
          ) : (
            <ChevronLeft className="w-5 h-5" aria-hidden="true" />
          )}
        </button>
      </div>

      {/* Navigation */}
      <nav className="p-3 flex-1 overflow-y-auto" aria-label="Main menu">
        {/* Main Navigation */}
        <div className="space-y-1">
          {filteredMainNav.map((item) => {
            const hasAccess = !item.tier || hasTier(item.tier);
            const isLocked = item.tier && !hasAccess;

            return (
              <NavLink
                key={item.name}
                to={isLocked ? '#' : item.href}
                onClick={(e) => isLocked && e.preventDefault()}
                className={({ isActive }) =>
                  clsx(
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors min-h-[44px]',
                    'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900',
                    isLocked
                      ? 'text-slate-400 dark:text-slate-600 cursor-not-allowed'
                      : isActive
                      ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                      : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100'
                  )
                }
                aria-disabled={isLocked}
                aria-current={undefined}
                title={collapsed ? item.name : undefined}
              >
                <item.icon
                  className={clsx(
                    'w-5 h-5 flex-shrink-0',
                    isLocked ? 'text-slate-400 dark:text-slate-600' : 'text-slate-500 dark:text-slate-400'
                  )}
                  aria-hidden="true"
                />
                {!collapsed && (
                  <span className="flex-1">{item.name}</span>
                )}
                {!collapsed && isLocked && (
                  <Crown className="w-4 h-4 text-amber-500 dark:text-amber-400" aria-label="Premium feature" />
                )}
              </NavLink>
            );
          })}
        </div>

        {/* Account Navigation Divider */}
        <div className="my-4 border-t border-slate-200 dark:border-slate-800" />

        {/* Account Navigation */}
        {!collapsed && (
          <div className="px-3 mb-2">
            <span className="text-xs font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider">
              Account
            </span>
          </div>
        )}
        <div className="space-y-1">
          {accountNavigation.map((item) => {
            const hasAccess = !item.tier || hasTier(item.tier);
            const isLocked = item.tier && !hasAccess;

            return (
              <NavLink
                key={item.name}
                to={isLocked ? '#' : item.href}
                onClick={(e) => isLocked && e.preventDefault()}
                className={({ isActive }) =>
                  clsx(
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors min-h-[44px]',
                    'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900',
                    isLocked
                      ? 'text-slate-400 dark:text-slate-600 cursor-not-allowed'
                      : isActive
                      ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                      : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100'
                  )
                }
                aria-disabled={isLocked}
                aria-current={undefined}
                title={collapsed ? item.name : undefined}
              >
                <item.icon
                  className={clsx(
                    'w-5 h-5 flex-shrink-0',
                    isLocked ? 'text-slate-400 dark:text-slate-600' : 'text-slate-500 dark:text-slate-400'
                  )}
                  aria-hidden="true"
                />
                {!collapsed && (
                  <span className="flex-1">{item.name}</span>
                )}
                {!collapsed && isLocked && (
                  <Crown className="w-4 h-4 text-amber-500 dark:text-amber-400" aria-label="Premium feature" />
                )}
              </NavLink>
            );
          })}
        </div>
      </nav>

      {/* Tier Badge */}
      {!collapsed && (
        <div className="p-4 border-t border-slate-200 dark:border-slate-800 flex-shrink-0">
          <div className="flex items-center justify-between">
            <span className="text-xs text-slate-400 dark:text-slate-500">Peanut Suite v{getVersion()}</span>
            <span
              className={clsx(
                'text-xs font-medium px-2 py-0.5 rounded-full',
                currentTier === 'agency'
                  ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'
                  : currentTier === 'pro'
                  ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
                  : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'
              )}
            >
              {currentTier.charAt(0).toUpperCase() + currentTier.slice(1)}
            </span>
          </div>
        </div>
      )}
    </aside>
  );
}

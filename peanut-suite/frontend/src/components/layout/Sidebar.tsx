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
} from 'lucide-react';

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
}

const navigation: NavItem[] = [
  { name: 'Dashboard', href: '/', icon: LayoutDashboard },
  { name: 'UTM Builder', href: '/utm', icon: Tag },
  { name: 'Links', href: '/links', icon: Link2 },
  { name: 'Contacts', href: '/contacts', icon: Users },
  { name: 'Webhooks', href: '/webhooks', icon: Webhook },
  { name: 'Visitors', href: '/visitors', icon: Eye, tier: 'pro' },
  { name: 'Attribution', href: '/attribution', icon: GitBranch, tier: 'pro' },
  { name: 'Analytics', href: '/analytics', icon: BarChart2, tier: 'pro' },
  { name: 'Popups', href: '/popups', icon: MessageSquare, tier: 'pro' },
  { name: 'Monitor', href: '/monitor', icon: Activity, tier: 'agency' },
  { name: 'Settings', href: '/settings', icon: Settings },
];

export default function Sidebar({ collapsed, onToggle }: SidebarProps) {
  const currentTier = getLicenseTier();

  return (
    <aside
      className={clsx(
        'fixed left-0 top-0 h-screen bg-white border-r border-slate-200 transition-all duration-300 z-50',
        collapsed ? 'w-16' : 'w-56'
      )}
    >
      {/* Logo */}
      <div className="h-14 flex items-center justify-between px-4 border-b border-slate-200">
        {!collapsed && (
          <span className="text-lg font-bold text-primary-600">Peanut Suite</span>
        )}
        <button
          onClick={onToggle}
          className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
        >
          {collapsed ? (
            <ChevronRight className="w-5 h-5" />
          ) : (
            <ChevronLeft className="w-5 h-5" />
          )}
        </button>
      </div>

      {/* Navigation */}
      <nav className="p-3 space-y-1">
        {navigation.map((item) => {
          const hasAccess = !item.tier || hasTier(item.tier);
          const isLocked = item.tier && !hasAccess;

          return (
            <NavLink
              key={item.name}
              to={isLocked ? '#' : item.href}
              onClick={(e) => isLocked && e.preventDefault()}
              className={({ isActive }) =>
                clsx(
                  'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                  isLocked
                    ? 'text-slate-400 cursor-not-allowed'
                    : isActive
                    ? 'bg-primary-50 text-primary-700'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                )
              }
            >
              <item.icon className={clsx('w-5 h-5 flex-shrink-0', isLocked ? 'text-slate-400' : 'text-slate-500')} />
              {!collapsed && (
                <span className="flex-1">{item.name}</span>
              )}
              {!collapsed && isLocked && (
                <Crown className="w-4 h-4 text-amber-500" />
              )}
            </NavLink>
          );
        })}
      </nav>

      {/* Tier Badge */}
      {!collapsed && (
        <div className="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-200">
          <div className="flex items-center justify-between">
            <span className="text-xs text-slate-400">Peanut Suite v{getVersion()}</span>
            <span
              className={clsx(
                'text-xs font-medium px-2 py-0.5 rounded-full',
                currentTier === 'agency'
                  ? 'bg-purple-100 text-purple-700'
                  : currentTier === 'pro'
                  ? 'bg-amber-100 text-amber-700'
                  : 'bg-slate-100 text-slate-600'
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

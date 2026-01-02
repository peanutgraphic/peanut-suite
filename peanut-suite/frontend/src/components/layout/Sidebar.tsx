import { NavLink } from 'react-router-dom';
import { clsx } from 'clsx';
import {
  LayoutDashboard,
  Link2,
  Tag,
  Users,
  UsersRound,
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
  Shield,
  Search,
  Mail,
  ShoppingCart,
  Gauge,
  Info,
  Server,
  ClipboardCheck,
  ScrollText,
  Key,
  FolderKanban,
} from 'lucide-react';
import { useAccountStore } from '../../store';

interface SidebarProps {
  collapsed: boolean;
  onToggle: () => void;
}

type TierLevel = 'free' | 'pro' | 'agency';

const getLicenseTier = (): TierLevel => {
  const tier = window.peanutData?.license?.tier;
  if (tier === 'free' || tier === 'pro' || tier === 'agency') {
    return tier;
  }
  return 'free';
};
const getVersion = () => window.peanutData?.version || '1.0.0';
const getBrandName = () => window.peanutData?.brandName || 'Marketing Suite';
const hasTier = (required: TierLevel) => {
  const tierLevels: Record<TierLevel, number> = { free: 0, pro: 1, agency: 2 };
  return tierLevels[getLicenseTier()] >= tierLevels[required];
};

interface NavItem {
  name: string;
  href: string;
  icon: typeof LayoutDashboard;
  tier?: 'free' | 'pro' | 'agency';
  feature?: string; // Feature key for permission checking
  tourId?: string;
  adminOnly?: boolean; // Only show to owner/admin roles
}

const navigation: NavItem[] = [
  { name: 'Dashboard', href: '/', icon: LayoutDashboard, tourId: 'nav-dashboard' },
  { name: 'UTM Builder', href: '/utm', icon: Tag, feature: 'utm', tourId: 'nav-utm' },
  { name: 'Links', href: '/links', icon: Link2, feature: 'links', tourId: 'nav-links' },
  { name: 'Contacts', href: '/contacts', icon: Users, feature: 'contacts', tourId: 'nav-contacts' },
  { name: 'Sequences', href: '/sequences', icon: Mail, tier: 'pro', feature: 'sequences', tourId: 'nav-sequences' },
  { name: 'Webhooks', href: '/webhooks', icon: Webhook, feature: 'webhooks', tourId: 'nav-webhooks' },
  { name: 'Visitors', href: '/visitors', icon: Eye, tier: 'pro', feature: 'visitors', tourId: 'nav-visitors' },
  { name: 'Attribution', href: '/attribution', icon: GitBranch, tier: 'pro', feature: 'attribution', tourId: 'nav-attribution' },
  { name: 'Analytics', href: '/analytics', icon: BarChart2, tier: 'pro', feature: 'analytics', tourId: 'nav-analytics' },
  { name: 'Popups', href: '/popups', icon: MessageSquare, tier: 'pro', feature: 'popups', tourId: 'nav-popups' },
  { name: 'Keywords', href: '/keywords', icon: Search, tier: 'pro', feature: 'keywords', tourId: 'nav-keywords' },
  { name: 'Backlinks', href: '/backlinks', icon: Link2, tier: 'pro', feature: 'backlinks', tourId: 'nav-backlinks' },
  { name: 'WooCommerce', href: '/woocommerce', icon: ShoppingCart, tier: 'pro', feature: 'woocommerce', tourId: 'nav-woocommerce' },
  { name: 'Performance', href: '/performance', icon: Gauge, tier: 'pro', feature: 'performance', tourId: 'nav-performance' },
  { name: 'Security', href: '/security', icon: Shield, tier: 'pro', feature: 'security', tourId: 'nav-security' },
  { name: 'Monitor', href: '/monitor', icon: Activity, tier: 'agency', feature: 'monitor', tourId: 'nav-monitor' },
  { name: 'Servers', href: '/servers', icon: Server, tier: 'agency', feature: 'monitor', tourId: 'nav-servers' },
  { name: 'Health Reports', href: '/health-reports', icon: ClipboardCheck, tier: 'agency', feature: 'monitor', tourId: 'nav-health-reports' },
  { name: 'Team', href: '/team', icon: UsersRound, tier: 'pro', adminOnly: true, tourId: 'nav-team' },
  { name: 'Projects', href: '/projects', icon: FolderKanban, tier: 'pro', adminOnly: true, tourId: 'nav-projects' },
  { name: 'Audit Log', href: '/audit-log', icon: ScrollText, tier: 'pro', adminOnly: true, tourId: 'nav-audit-log' },
  { name: 'API Keys', href: '/api-keys', icon: Key, tier: 'pro', adminOnly: true, tourId: 'nav-api-keys' },
  { name: 'Settings', href: '/settings', icon: Settings, adminOnly: true, tourId: 'nav-settings' },
];

export default function Sidebar({ collapsed, onToggle }: SidebarProps) {
  const currentTier = getLicenseTier();
  const { canAccessFeature, isOwnerOrAdmin } = useAccountStore();

  // Filter navigation based on permissions
  const filteredNavigation = navigation.filter((item) => {
    // Dashboard is always visible
    if (item.href === '/') {
      return true;
    }

    // Admin-only items require owner/admin role
    if (item.adminOnly && !isOwnerOrAdmin()) {
      return false;
    }

    // Feature-based permission check
    if (item.feature && !canAccessFeature(item.feature)) {
      return false;
    }

    return true;
  });

  // Check if user only has access to Dashboard
  const hasLimitedAccess = !isOwnerOrAdmin() && filteredNavigation.length === 1;

  return (
    <aside
      data-tour="sidebar"
      className={clsx(
        'fixed left-0 top-0 h-screen bg-white border-r border-slate-200 transition-all duration-300 z-50',
        collapsed ? 'w-16' : 'w-56'
      )}
    >
      {/* Logo */}
      <div className="h-14 flex items-center justify-between px-4 border-b border-slate-200">
        {!collapsed && (
          <span className="text-lg font-bold text-primary-600">{getBrandName()}</span>
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

      {/* Navigation - scrollable area */}
      <nav className="p-3 space-y-1 overflow-y-auto" style={{ maxHeight: 'calc(100vh - 130px)' }}>
        {filteredNavigation.map((item) => {
          const hasAccess = !item.tier || hasTier(item.tier);
          const isLocked = item.tier && !hasAccess;

          // Render locked items as non-interactive elements instead of links
          if (isLocked) {
            return (
              <div
                key={item.name}
                data-tour={item.tourId}
                className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-400 cursor-not-allowed"
              >
                <item.icon className="w-5 h-5 flex-shrink-0 text-slate-400" />
                {!collapsed && (
                  <span className="flex-1">{item.name}</span>
                )}
                {!collapsed && (
                  <Crown className="w-4 h-4 text-amber-500" />
                )}
              </div>
            );
          }

          return (
            <NavLink
              key={item.name}
              to={item.href}
              data-tour={item.tourId}
              className={({ isActive }) =>
                clsx(
                  'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                  isActive
                    ? 'bg-primary-50 text-primary-700'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                )
              }
            >
              <item.icon className="w-5 h-5 flex-shrink-0 text-slate-500" />
              {!collapsed && (
                <span className="flex-1">{item.name}</span>
              )}
            </NavLink>
          );
        })}
      </nav>

      {/* Limited Access Message */}
      {hasLimitedAccess && !collapsed && (
        <div className="mx-3 mt-2 p-3 bg-slate-50 rounded-lg border border-slate-200">
          <div className="flex gap-2">
            <Info className="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5" />
            <p className="text-xs text-slate-500 leading-relaxed">
              Contact your administrator to gain access to more features.
            </p>
          </div>
        </div>
      )}

      {/* Tier Badge */}
      {!collapsed && (
        <div className="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-200">
          <div className="flex items-center justify-between">
            <span className="text-xs text-slate-400">{getBrandName()} v{getVersion()}</span>
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

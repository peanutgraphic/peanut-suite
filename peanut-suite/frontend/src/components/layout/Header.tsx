import { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Bell, Search, User, Command, Plus, Link2, Target, Users, ChevronDown, HelpCircle, LogOut } from 'lucide-react';
import type { HelpContent } from '../common';
import { PageGuideButton, ProjectSwitcher } from '../common';
import { useAccountStore } from '../../store';

interface HeaderProps {
  title: string;
  description?: string;
  onSearchClick?: () => void;
  helpContent?: HelpContent;
  onHelpClick?: () => void;
  pageGuideId?: string;
}

interface QuickCreateItem {
  label: string;
  icon: typeof Link2;
  path: string;
  description: string;
  feature: string;
}

const quickCreateItems: QuickCreateItem[] = [
  { label: 'New Link', icon: Link2, path: '/links?create=true', description: 'Create short URL', feature: 'links' },
  { label: 'New UTM', icon: Target, path: '/utm', description: 'Build UTM parameters', feature: 'utm' },
  { label: 'New Contact', icon: Users, path: '/contacts?create=true', description: 'Add lead', feature: 'contacts' },
];

export default function Header({ title, description, onSearchClick, helpContent, onHelpClick, pageGuideId }: HeaderProps) {
  const { canAccessFeature, isOwnerOrAdmin } = useAccountStore();

  // Filter quick create items by permission
  const filteredQuickCreateItems = quickCreateItems.filter(item => canAccessFeature(item.feature));

  // Check if user has any feature access (for showing/hiding header actions)
  const hasAnyAccess = isOwnerOrAdmin() || filteredQuickCreateItems.length > 0;
  const navigate = useNavigate();
  const [quickCreateOpen, setQuickCreateOpen] = useState(false);
  const [userMenuOpen, setUserMenuOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const userMenuRef = useRef<HTMLDivElement>(null);

  // Get user info
  const user = window.peanutData?.user;

  // Close dropdowns when clicking outside
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
        setQuickCreateOpen(false);
      }
      if (userMenuRef.current && !userMenuRef.current.contains(e.target as Node)) {
        setUserMenuOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleLogout = () => {
    // Use WordPress logout URL with proper nonce
    const logoutUrl = window.peanutData?.logoutUrl;
    if (logoutUrl) {
      window.location.href = logoutUrl;
    } else {
      // Fallback to WordPress logout
      window.location.href = '/wp-login.php?action=logout';
    }
  };

  const handleQuickCreate = (path: string) => {
    navigate(path);
    setQuickCreateOpen(false);
  };

  return (
    <>
      {/* Top bar - aligns with sidebar logo */}
      <header className="h-14 bg-white border-b border-slate-200 flex items-center justify-end px-6">
        {hasAnyAccess ? (
          <div className="flex items-center gap-3">
            {/* Project Switcher */}
            <ProjectSwitcher />

            {/* Quick Create - only show if user has features to create */}
            {filteredQuickCreateItems.length > 0 && (
              <div className="relative" ref={dropdownRef}>
                <button
                  onClick={() => setQuickCreateOpen(!quickCreateOpen)}
                  className="flex items-center gap-1.5 px-3 py-1.5 text-white rounded-lg transition-colors font-medium text-sm"
                  style={{ backgroundColor: '#2563eb' }}
                >
                  <Plus className="w-4 h-4" />
                  <span className="hidden sm:inline">Create</span>
                  <ChevronDown className="w-3.5 h-3.5 hidden sm:block" />
                </button>

                {quickCreateOpen && (
                  <div className="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-50">
                    {filteredQuickCreateItems.map((item) => (
                      <button
                        key={item.path}
                        onClick={() => handleQuickCreate(item.path)}
                        className="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-slate-50 transition-colors"
                      >
                        <div className="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center">
                          <item.icon className="w-4 h-4 text-primary-600" />
                        </div>
                        <div>
                          <p className="font-medium text-slate-900 text-sm">{item.label}</p>
                          <p className="text-xs text-slate-500">{item.description}</p>
                        </div>
                      </button>
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* Search */}
            <button
              onClick={onSearchClick}
              className="flex items-center gap-2 px-3 py-1.5 text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg transition-colors"
              title="Search (⌘K)"
            >
              <Search className="w-4 h-4" />
              <span className="text-sm hidden sm:inline">Search</span>
              <kbd className="hidden sm:flex items-center gap-0.5 px-1.5 py-0.5 text-xs bg-white border border-slate-200 rounded text-slate-400">
                <Command className="w-3 h-3" />K
              </kbd>
            </button>

            {/* Notifications - only show for admins */}
            {isOwnerOrAdmin() && (
              <button className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors relative">
                <Bell className="w-5 h-5" />
              </button>
            )}

            {/* User menu */}
            <div className="relative" ref={userMenuRef}>
              <button
                onClick={() => setUserMenuOpen(!userMenuOpen)}
                className="flex items-center gap-2 p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
              >
                <div className="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                  <User className="w-4 h-4 text-primary-600" />
                </div>
              </button>

              {userMenuOpen && (
                <div className="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-50">
                  {user && (
                    <div className="px-4 py-3 border-b border-slate-100">
                      <p className="font-medium text-slate-900 text-sm">{user.name}</p>
                      <p className="text-xs text-slate-500 truncate">{user.email}</p>
                    </div>
                  )}
                  <button
                    onClick={handleLogout}
                    className="w-full flex items-center gap-3 px-4 py-2.5 text-left text-red-600 hover:bg-red-50 transition-colors"
                  >
                    <LogOut className="w-4 h-4" />
                    <span className="text-sm font-medium">Sign out</span>
                  </button>
                </div>
              )}
            </div>
          </div>
        ) : (
          /* Minimal header for users with no access */
          <div className="flex items-center gap-3">
            <div className="relative" ref={userMenuRef}>
              <button
                onClick={() => setUserMenuOpen(!userMenuOpen)}
                className="flex items-center gap-2 p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
              >
                <div className="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                  <User className="w-4 h-4 text-primary-600" />
                </div>
              </button>

              {userMenuOpen && (
                <div className="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-50">
                  {user && (
                    <div className="px-4 py-3 border-b border-slate-100">
                      <p className="font-medium text-slate-900 text-sm">{user.name}</p>
                      <p className="text-xs text-slate-500 truncate">{user.email}</p>
                    </div>
                  )}
                  <button
                    onClick={handleLogout}
                    className="w-full flex items-center gap-3 px-4 py-2.5 text-left text-red-600 hover:bg-red-50 transition-colors"
                  >
                    <LogOut className="w-4 h-4" />
                    <span className="text-sm font-medium">Sign out</span>
                  </button>
                </div>
              )}
            </div>
          </div>
        )}
      </header>

      {/* Page title - aligns with sidebar nav items */}
      <div className="px-6 pt-3 pb-2 bg-slate-50">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-slate-900">{title}</h1>
            {description && (
              <p className="text-sm text-slate-500 mt-0.5">{description}</p>
            )}
          </div>
          <div className="flex items-center gap-2">
            {/* Page Guide Button */}
            {pageGuideId && <PageGuideButton pageId={pageGuideId} />}

            {/* How To Help Button */}
            {helpContent && onHelpClick && (
              <button
                onClick={onHelpClick}
                className="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-slate-600 hover:text-primary-600 bg-white hover:bg-primary-50 border border-slate-200 hover:border-primary-200 rounded-lg transition-colors whitespace-nowrap"
                title="How to use this page"
              >
                <HelpCircle className="w-4 h-4" />
                <span>How to</span>
              </button>
            )}
          </div>
        </div>
      </div>
    </>
  );
}

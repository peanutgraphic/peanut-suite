import { Bell, Search, User, Menu } from 'lucide-react';
import ThemeToggle from '@/components/common/ThemeToggle';
import AccountSwitcher from '@/components/common/AccountSwitcher';

interface HeaderProps {
  title: string;
  description?: string;
  onMenuToggle?: () => void;
}

export default function Header({ title, description, onMenuToggle }: HeaderProps) {
  return (
    <header className="h-14 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 md:px-6">
      <div className="flex items-center gap-3">
        {/* Mobile menu toggle */}
        {onMenuToggle && (
          <button
            onClick={onMenuToggle}
            className="md:hidden p-2 text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
            aria-label="Toggle menu"
            aria-expanded="false"
          >
            <Menu className="w-5 h-5" aria-hidden="true" />
          </button>
        )}

        {/* Account Switcher (for multi-account users) */}
        <AccountSwitcher className="hidden md:flex" />

        <div className="border-l border-slate-200 dark:border-slate-700 pl-3 hidden md:block">
          <h1 className="text-lg font-semibold text-slate-900 dark:text-slate-100">{title}</h1>
          {description && (
            <p className="text-sm text-slate-500 dark:text-slate-400 hidden sm:block">{description}</p>
          )}
        </div>

        {/* Mobile: Show title without account switcher */}
        <div className="md:hidden">
          <h1 className="text-lg font-semibold text-slate-900 dark:text-slate-100">{title}</h1>
        </div>
      </div>

      <div className="flex items-center gap-2 md:gap-3">
        {/* Search */}
        <button
          className="p-2 text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
          aria-label="Search"
          title="Search"
        >
          <Search className="w-5 h-5" aria-hidden="true" />
        </button>

        {/* Theme Toggle */}
        <ThemeToggle variant="button" className="hidden sm:flex" />

        {/* Notifications */}
        <button
          className="p-2 text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors relative focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
          aria-label="Notifications (1 unread)"
          title="Notifications"
        >
          <Bell className="w-5 h-5" aria-hidden="true" />
          <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full" aria-hidden="true" />
          <span className="sr-only">1 unread notification</span>
        </button>

        {/* User menu */}
        <button
          className="flex items-center gap-2 p-1.5 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 min-w-[44px] min-h-[44px]"
          aria-label="User menu"
          title="User menu"
        >
          <div className="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center">
            <User className="w-4 h-4 text-primary-600 dark:text-primary-400" aria-hidden="true" />
          </div>
        </button>
      </div>
    </header>
  );
}

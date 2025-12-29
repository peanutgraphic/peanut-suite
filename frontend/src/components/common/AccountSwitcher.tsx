import { useState, useRef, useEffect } from 'react';
import { ChevronDown, Check, Building2, Plus } from 'lucide-react';
import { useAccountStore, useCurrentAccount, useAccounts, useHasMultipleAccounts } from '@/store';
import { accountsApi } from '@/api';
import { toast } from '@/store';
import type { Account } from '@/types';

interface AccountSwitcherProps {
  compact?: boolean;
  className?: string;
}

export default function AccountSwitcher({ compact = false, className = '' }: AccountSwitcherProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  const currentAccount = useCurrentAccount();
  const accounts = useAccounts();
  const hasMultipleAccounts = useHasMultipleAccounts();
  const { setCurrentAccount, setAccounts, setAccountStats } = useAccountStore();

  // Close dropdown when clicking outside
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Load accounts on mount
  useEffect(() => {
    async function loadAccounts() {
      try {
        const accountList = await accountsApi.getAll();
        setAccounts(accountList);

        // If no current account, load it
        if (!currentAccount) {
          const current = await accountsApi.getCurrent();
          setCurrentAccount(current, current.role);
          const stats = await accountsApi.getStats(current.id);
          setAccountStats(stats);
        }
      } catch {
        // Silent fail - will show login required or similar
      }
    }
    loadAccounts();
  }, [currentAccount, setAccounts, setCurrentAccount, setAccountStats]);

  const handleSwitch = async (account: Account) => {
    if (account.id === currentAccount?.id) {
      setIsOpen(false);
      return;
    }

    setIsLoading(true);
    try {
      // Just switch to the selected account in the store
      setCurrentAccount(account, account.role);

      // Fetch stats for the new account
      const stats = await accountsApi.getStats(account.id);
      setAccountStats(stats);

      toast.success(`Switched to ${account.name}`);
      setIsOpen(false);

      // Reload page to refresh all data
      window.location.reload();
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Failed to switch account');
    } finally {
      setIsLoading(false);
    }
  };

  // Don't render if no account or single account (unless in compact mode for display)
  if (!currentAccount) {
    return null;
  }

  if (!hasMultipleAccounts && !compact) {
    // Show current account name without dropdown
    return (
      <div className={`flex items-center gap-2 ${className}`}>
        <div className="w-7 h-7 bg-primary-100 dark:bg-primary-900/30 rounded-md flex items-center justify-center">
          <Building2 className="w-4 h-4 text-primary-600 dark:text-primary-400" />
        </div>
        <span className="text-sm font-medium text-slate-700 dark:text-slate-300 truncate max-w-[120px]">
          {currentAccount.name}
        </span>
      </div>
    );
  }

  return (
    <div ref={dropdownRef} className={`relative ${className}`}>
      <button
        onClick={() => setIsOpen(!isOpen)}
        disabled={isLoading}
        className="flex items-center gap-2 px-2 py-1.5 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
        aria-haspopup="listbox"
        aria-expanded={isOpen}
        aria-label={`Current account: ${currentAccount.name}. Click to switch accounts.`}
      >
        <div className="w-7 h-7 bg-primary-100 dark:bg-primary-900/30 rounded-md flex items-center justify-center">
          <Building2 className="w-4 h-4 text-primary-600 dark:text-primary-400" />
        </div>
        {!compact && (
          <>
            <span className="text-sm font-medium truncate max-w-[120px]">
              {currentAccount.name}
            </span>
            <ChevronDown
              className={`w-4 h-4 text-slate-400 transition-transform ${isOpen ? 'rotate-180' : ''}`}
            />
          </>
        )}
      </button>

      {isOpen && (
        <div
          className="absolute top-full left-0 mt-1 w-64 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg z-50 py-1"
          role="listbox"
          aria-label="Select account"
        >
          <div className="px-3 py-2 border-b border-slate-200 dark:border-slate-700">
            <p className="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              Switch Account
            </p>
          </div>

          <div className="max-h-64 overflow-y-auto py-1">
            {accounts.map((account) => (
              <button
                key={account.id}
                onClick={() => handleSwitch(account)}
                disabled={isLoading}
                className={`w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors ${
                  account.id === currentAccount.id
                    ? 'bg-primary-50 dark:bg-primary-900/20'
                    : ''
                }`}
                role="option"
                aria-selected={account.id === currentAccount.id}
              >
                <div className="w-8 h-8 bg-slate-100 dark:bg-slate-700 rounded-md flex items-center justify-center flex-shrink-0">
                  <Building2 className="w-4 h-4 text-slate-500 dark:text-slate-400" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">
                    {account.name}
                  </p>
                  <p className="text-xs text-slate-500 dark:text-slate-400">
                    {account.role || 'member'} &middot; {account.tier}
                  </p>
                </div>
                {account.id === currentAccount.id && (
                  <Check className="w-4 h-4 text-primary-600 dark:text-primary-400 flex-shrink-0" />
                )}
              </button>
            ))}
          </div>

          {/* Create new account option - only for admins */}
          <div className="border-t border-slate-200 dark:border-slate-700 pt-1">
            <button
              onClick={() => {
                setIsOpen(false);
                // Navigate to create account - this would typically be handled by settings
                window.location.hash = '#/settings?tab=account';
              }}
              className="w-full flex items-center gap-3 px-3 py-2 text-left text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
            >
              <div className="w-8 h-8 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-md flex items-center justify-center">
                <Plus className="w-4 h-4" />
              </div>
              <span className="text-sm">Create new account</span>
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

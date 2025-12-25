import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { Account, AccountStats, MemberRole } from '@/types';

interface AccountState {
  // Current account context
  currentAccount: Account | null;
  currentRole: MemberRole | null;
  accountStats: AccountStats | null;

  // All accounts user has access to
  accounts: Account[];

  // Loading state
  isLoading: boolean;

  // Actions
  setCurrentAccount: (account: Account, role?: MemberRole) => void;
  setAccounts: (accounts: Account[]) => void;
  setAccountStats: (stats: AccountStats) => void;
  setLoading: (loading: boolean) => void;
  switchAccount: (accountId: number) => void;
  clearAccount: () => void;
}

export const useAccountStore = create<AccountState>()(
  persist(
    (set, get) => ({
      currentAccount: null,
      currentRole: null,
      accountStats: null,
      accounts: [],
      isLoading: false,

      setCurrentAccount: (account, role) => set({
        currentAccount: account,
        currentRole: role || account.role || 'member'
      }),

      setAccounts: (accounts) => set({ accounts }),

      setAccountStats: (stats) => set({ accountStats: stats }),

      setLoading: (loading) => set({ isLoading: loading }),

      switchAccount: (accountId) => {
        const { accounts } = get();
        const account = accounts.find((a) => a.id === accountId);
        if (account) {
          set({
            currentAccount: account,
            currentRole: account.role || 'member',
            accountStats: null // Clear stats, will be refetched
          });
        }
      },

      clearAccount: () => set({
        currentAccount: null,
        currentRole: null,
        accountStats: null,
        accounts: []
      }),
    }),
    {
      name: 'peanut-account-storage',
      partialize: (state) => ({
        // Only persist the current account ID, not full data
        currentAccountId: state.currentAccount?.id
      }),
    }
  )
);

// Selectors
export const useCurrentAccount = () => useAccountStore((state) => state.currentAccount);
export const useCurrentRole = () => useAccountStore((state) => state.currentRole);
export const useAccounts = () => useAccountStore((state) => state.accounts);
export const useAccountStats = () => useAccountStore((state) => state.accountStats);

// Permission helpers
export const useIsAccountOwner = () => {
  const role = useAccountStore((state) => state.currentRole);
  return role === 'owner';
};

export const useIsAccountAdmin = () => {
  const role = useAccountStore((state) => state.currentRole);
  return role === 'owner' || role === 'admin';
};

export const useCanManageTeam = () => {
  const role = useAccountStore((state) => state.currentRole);
  return role === 'owner' || role === 'admin';
};

export const useCanManageApiKeys = () => {
  const role = useAccountStore((state) => state.currentRole);
  return role === 'owner' || role === 'admin';
};

export const useHasMultipleAccounts = () => {
  const accounts = useAccountStore((state) => state.accounts);
  return accounts.length > 1;
};

import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { Account, AccountStats, MemberRole, FeaturePermissions, AvailableFeatures, FeatureKey } from '@/types';

interface AccountState {
  // Current account context
  currentAccount: Account | null;
  currentRole: MemberRole | null;
  accountStats: AccountStats | null;

  // Feature permissions for current user
  featurePermissions: FeaturePermissions | null;
  availableFeatures: AvailableFeatures | null;

  // All accounts user has access to
  accounts: Account[];

  // Loading state
  isLoading: boolean;

  // Actions
  setCurrentAccount: (account: Account, role?: MemberRole) => void;
  setAccounts: (accounts: Account[]) => void;
  setAccountStats: (stats: AccountStats) => void;
  setFeaturePermissions: (permissions: FeaturePermissions) => void;
  setAvailableFeatures: (features: AvailableFeatures) => void;
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
      featurePermissions: null,
      availableFeatures: null,
      accounts: [],
      isLoading: false,

      setCurrentAccount: (account, role) => set({
        currentAccount: account,
        currentRole: role || account.role || 'member'
      }),

      setAccounts: (accounts) => set({ accounts }),

      setAccountStats: (stats) => set({ accountStats: stats }),

      setFeaturePermissions: (permissions) => set({ featurePermissions: permissions }),

      setAvailableFeatures: (features) => set({ availableFeatures: features }),

      setLoading: (loading) => set({ isLoading: loading }),

      switchAccount: (accountId) => {
        const { accounts } = get();
        const account = accounts.find((a) => a.id === accountId);
        if (account) {
          set({
            currentAccount: account,
            currentRole: account.role || 'member',
            accountStats: null, // Clear stats, will be refetched
            featurePermissions: null, // Clear permissions, will be refetched
          });
        }
      },

      clearAccount: () => set({
        currentAccount: null,
        currentRole: null,
        accountStats: null,
        featurePermissions: null,
        availableFeatures: null,
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

// Feature permission helpers
export const useFeaturePermissions = () => useAccountStore((state) => state.featurePermissions);
export const useAvailableFeatures = () => useAccountStore((state) => state.availableFeatures);

export const useCanAccessFeature = (feature: FeatureKey): boolean => {
  const role = useAccountStore((state) => state.currentRole);
  const permissions = useAccountStore((state) => state.featurePermissions);
  const availableFeatures = useAccountStore((state) => state.availableFeatures);

  // Owners and admins always have access to available features
  if (role === 'owner' || role === 'admin') {
    return availableFeatures?.[feature]?.available ?? true;
  }

  // Check feature availability first
  if (availableFeatures && !availableFeatures[feature]?.available) {
    return false;
  }

  // Then check user permissions
  return permissions?.[feature]?.access ?? false;
};

export const useVisibleFeatures = (): FeatureKey[] => {
  const role = useAccountStore((state) => state.currentRole);
  const permissions = useAccountStore((state) => state.featurePermissions);
  const availableFeatures = useAccountStore((state) => state.availableFeatures);

  // Admins see all available features
  if (role === 'owner' || role === 'admin') {
    if (!availableFeatures) return [];
    return (Object.keys(availableFeatures) as FeatureKey[]).filter(
      (key) => availableFeatures[key]?.available
    );
  }

  // Others only see features they have permission for and are available
  if (!permissions || !availableFeatures) return [];
  return (Object.entries(permissions) as [FeatureKey, { access: boolean }][])
    .filter(([key, perm]) => perm.access && availableFeatures[key]?.available)
    .map(([key]) => key);
};

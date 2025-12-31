import { create } from 'zustand';
import type { AccountRole, FeaturePermissions } from '../types';
import { authApi } from '../api/endpoints';

// User data from peanutData
interface User {
  id: number;
  name: string;
  email: string;
  avatar: string;
}

// Available feature info from backend
interface AvailableFeature {
  name: string;
  tier: string;
  available: boolean;
}

// Account data from peanutData
interface AccountContext {
  id: number;
  name: string;
  slug: string;
  tier: 'free' | 'pro' | 'agency';
  role: AccountRole;
  permissions: FeaturePermissions | null;
  available_features: Record<string, AvailableFeature>;
}

interface AccountState {
  user: User | null;
  account: AccountContext | null;
  isInitialized: boolean;
  isLoading: boolean;
  error: string | null;

  // Actions
  initialize: () => void;
  fetchCurrentUser: () => Promise<void>;
  setUser: (user: User | null) => void;
  setAccount: (account: AccountContext | null) => void;
  clearError: () => void;

  // Selectors / Computed values
  getAccountId: () => number | null;
  getUserRole: () => AccountRole;
  canAccessFeature: (feature: string) => boolean;
  isOwnerOrAdmin: () => boolean;
}

// Get initial data from window.peanutData (injected by PHP)
declare global {
  interface Window {
    peanutData?: {
      version?: string;
      brandName?: string;
      logoutUrl?: string;
      license?: {
        tier: string;
        isPro: boolean;
      };
      user?: User;
      account?: AccountContext;
    };
  }
}

export const useAccountStore = create<AccountState>()((set, get) => ({
  user: null,
  account: null,
  isInitialized: false,
  isLoading: false,
  error: null,

  initialize: () => {
    const peanutData = window.peanutData;

    if (peanutData) {
      set({
        user: peanutData.user || null,
        account: peanutData.account || null,
        isInitialized: true,
        isLoading: false,
      });
    } else {
      set({ isInitialized: true, isLoading: false });
    }
  },

  fetchCurrentUser: async () => {
    set({ isLoading: true, error: null });

    try {
      const response = await authApi.getCurrentUser();

      if (response.success) {
        set({
          user: response.user,
          account: response.account,
          isLoading: false,
          isInitialized: true,
        });
      } else {
        set({
          error: 'Failed to fetch user data',
          isLoading: false,
          isInitialized: true,
        });
      }
    } catch (err) {
      set({
        error: err instanceof Error ? err.message : 'Failed to fetch user data',
        isLoading: false,
        isInitialized: true,
      });
    }
  },

  setUser: (user) => set({ user }),
  setAccount: (account) => set({ account }),
  clearError: () => set({ error: null }),

  // Get the current account ID (or null if no account)
  getAccountId: (): number | null => {
    const { account } = get();
    return account?.id ?? null;
  },

  // Get the current user's role in the account
  getUserRole: (): AccountRole => {
    const { account } = get();
    return account?.role || 'owner';
  },

  canAccessFeature: (feature: string): boolean => {
    const { account } = get();

    // No account = no restrictions (fallback to license tier)
    if (!account) {
      return true;
    }

    // Owner and admin have access to all features
    if (account.role === 'owner' || account.role === 'admin') {
      return true;
    }

    // Check if feature is available for the account's tier
    const availableFeature = account.available_features?.[feature];
    if (availableFeature && !availableFeature.available) {
      return false; // Feature not available for this tier
    }

    // For team members (member/viewer), check specific permissions
    // If no permission is set for this feature, deny access by default
    if (account.permissions) {
      const featurePermission = account.permissions[feature as keyof FeaturePermissions];
      if (featurePermission !== undefined) {
        return featurePermission?.access ?? false;
      }
    }

    // Team members: deny access to features without explicit permission
    return false;
  },

  isOwnerOrAdmin: (): boolean => {
    const { account } = get();
    if (!account) return true; // Fallback to owner behavior
    return account.role === 'owner' || account.role === 'admin';
  },
}));

// Initialize store on load
if (typeof window !== 'undefined') {
  // Wait for DOM to be ready to ensure peanutData is available
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      useAccountStore.getState().initialize();
    });
  } else {
    useAccountStore.getState().initialize();
  }
}

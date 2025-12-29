import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export type Theme = 'light' | 'dark' | 'system';

interface ThemeState {
  theme: Theme;
  isDark: boolean;
  setTheme: (theme: Theme) => void;
  toggleTheme: () => void;
}

// Get system preference
function getSystemTheme(): 'light' | 'dark' {
  if (typeof window === 'undefined') return 'light';
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

// Apply theme to document
function applyTheme(theme: Theme) {
  const root = document.documentElement;
  const isDark = theme === 'dark' || (theme === 'system' && getSystemTheme() === 'dark');

  if (isDark) {
    root.classList.add('dark');
  } else {
    root.classList.remove('dark');
  }

  return isDark;
}

export const useThemeStore = create<ThemeState>()(
  persist(
    (set, get) => ({
      theme: 'system',
      isDark: false,

      setTheme: (theme) => {
        const isDark = applyTheme(theme);
        set({ theme, isDark });
      },

      toggleTheme: () => {
        const { theme } = get();
        const newTheme = theme === 'dark' || (theme === 'system' && getSystemTheme() === 'dark') ? 'light' : 'dark';
        const isDark = applyTheme(newTheme);
        set({ theme: newTheme, isDark });
      },
    }),
    {
      name: 'peanut-theme-storage',
      onRehydrateStorage: () => (state) => {
        // Apply theme when store is rehydrated
        if (state) {
          const isDark = applyTheme(state.theme);
          state.isDark = isDark;
        }
      },
    }
  )
);

// Initialize theme on import
if (typeof window !== 'undefined') {
  const store = useThemeStore.getState();
  const isDark = applyTheme(store.theme);
  useThemeStore.setState({ isDark });

  // Listen for system theme changes
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    const { theme, setTheme } = useThemeStore.getState();
    if (theme === 'system') {
      setTheme('system'); // Re-apply system theme
    }
  });
}

import { Sun, Moon, Monitor } from 'lucide-react';
import { clsx } from 'clsx';
import { useThemeStore, type Theme } from '@/store/useThemeStore';

interface ThemeToggleProps {
  variant?: 'button' | 'dropdown';
  className?: string;
  showLabel?: boolean;
}

export default function ThemeToggle({ variant = 'button', className, showLabel = false }: ThemeToggleProps) {
  const { theme, setTheme } = useThemeStore();

  if (variant === 'dropdown') {
    return (
      <div className={clsx('space-y-1', className)}>
        {showLabel && (
          <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            Theme
          </label>
        )}
        <div className="grid grid-cols-3 gap-2" role="radiogroup" aria-label="Theme selection">
          <ThemeOption
            value="light"
            icon={Sun}
            label="Light"
            selected={theme === 'light'}
            onClick={() => setTheme('light')}
          />
          <ThemeOption
            value="dark"
            icon={Moon}
            label="Dark"
            selected={theme === 'dark'}
            onClick={() => setTheme('dark')}
          />
          <ThemeOption
            value="system"
            icon={Monitor}
            label="System"
            selected={theme === 'system'}
            onClick={() => setTheme('system')}
          />
        </div>
      </div>
    );
  }

  // Simple toggle button (light <-> dark)
  const { toggleTheme, isDark } = useThemeStore();

  return (
    <button
      onClick={toggleTheme}
      className={clsx(
        'flex items-center gap-2 px-3 py-2 rounded-lg transition-colors',
        'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700',
        'text-slate-700 dark:text-slate-300',
        'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
        className
      )}
      aria-label={`Switch to ${isDark ? 'light' : 'dark'} mode`}
      title={`Switch to ${isDark ? 'light' : 'dark'} mode`}
    >
      {isDark ? (
        <Sun className="w-5 h-5" aria-hidden="true" />
      ) : (
        <Moon className="w-5 h-5" aria-hidden="true" />
      )}
      {showLabel && <span className="text-sm font-medium">{isDark ? 'Light' : 'Dark'}</span>}
    </button>
  );
}

interface ThemeOptionProps {
  value: Theme;
  icon: React.ComponentType<{ className?: string }>;
  label: string;
  selected: boolean;
  onClick: () => void;
}

function ThemeOption({ value: _value, icon: Icon, label, selected, onClick }: ThemeOptionProps) {
  return (
    <button
      onClick={onClick}
      className={clsx(
        'flex flex-col items-center gap-2 p-3 rounded-lg border-2 transition-all',
        'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
        {
          'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300':
            selected,
          'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-600':
            !selected,
        }
      )}
      role="radio"
      aria-checked={selected}
      aria-label={`${label} theme`}
    >
      <Icon className="w-5 h-5" aria-hidden="true" />
      <span className="text-xs font-medium">{label}</span>
    </button>
  );
}

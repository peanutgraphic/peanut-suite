import { X, Trash2, Download, Archive, CheckCircle2 } from 'lucide-react';
import { clsx } from 'clsx';

interface BulkAction {
  label: string;
  icon: React.ReactNode;
  onClick: () => void;
  variant?: 'default' | 'danger';
  loading?: boolean;
}

interface BulkActionsBarProps {
  selectedCount: number;
  onClear: () => void;
  actions: BulkAction[];
  entityName?: string;
}

export default function BulkActionsBar({
  selectedCount,
  onClear,
  actions,
  entityName = 'items',
}: BulkActionsBarProps) {
  if (selectedCount === 0) return null;

  return (
    <div className="fixed bottom-6 left-1/2 -translate-x-1/2 z-[2147483647] animate-slide-up" style={{ marginLeft: '140px' }}>
      <div className="bg-slate-900 text-white rounded-xl shadow-2xl px-4 py-3 flex items-center gap-4">
        {/* Selection count */}
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 bg-primary-500 rounded-lg flex items-center justify-center font-bold text-sm">
            {selectedCount}
          </div>
          <span className="text-sm text-slate-300">
            {entityName} selected
          </span>
        </div>

        {/* Divider */}
        <div className="w-px h-8 bg-slate-700" />

        {/* Actions */}
        <div className="flex items-center gap-2">
          {actions.map((action, index) => (
            <button
              key={index}
              onClick={action.onClick}
              disabled={action.loading}
              className={clsx(
                'flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                action.variant === 'danger'
                  ? 'bg-red-500/20 text-red-300 hover:bg-red-500/30'
                  : 'bg-slate-700 text-slate-200 hover:bg-slate-600',
                action.loading && 'opacity-50 cursor-not-allowed'
              )}
            >
              {action.icon}
              {action.label}
            </button>
          ))}
        </div>

        {/* Clear selection */}
        <button
          onClick={onClear}
          className="p-1.5 text-slate-400 hover:text-white hover:bg-slate-700 rounded-lg transition-colors"
          title="Clear selection"
        >
          <X className="w-4 h-4" />
        </button>
      </div>
    </div>
  );
}

// Pre-made action builders for common use cases
export const bulkActions = {
  delete: (onClick: () => void, loading?: boolean): BulkAction => ({
    label: 'Delete',
    icon: <Trash2 className="w-4 h-4" />,
    onClick,
    variant: 'danger',
    loading,
  }),
  export: (onClick: () => void): BulkAction => ({
    label: 'Export',
    icon: <Download className="w-4 h-4" />,
    onClick,
  }),
  archive: (onClick: () => void, loading?: boolean): BulkAction => ({
    label: 'Archive',
    icon: <Archive className="w-4 h-4" />,
    onClick,
    loading,
  }),
  markComplete: (onClick: () => void, loading?: boolean): BulkAction => ({
    label: 'Complete',
    icon: <CheckCircle2 className="w-4 h-4" />,
    onClick,
    loading,
  }),
};

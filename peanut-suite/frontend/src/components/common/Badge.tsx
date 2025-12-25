import { clsx } from 'clsx';
import type { ReactNode } from 'react';

interface BadgeProps {
  children: ReactNode;
  variant?: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info';
  size?: 'sm' | 'md';
  className?: string;
}

export default function Badge({
  children,
  variant = 'default',
  size = 'md',
  className,
}: BadgeProps) {
  const variants = {
    default: 'bg-slate-100 text-slate-700',
    primary: 'bg-primary-100 text-primary-700',
    success: 'bg-green-100 text-green-700',
    warning: 'bg-amber-100 text-amber-700',
    danger: 'bg-red-100 text-red-700',
    info: 'bg-blue-100 text-blue-700',
  };

  const sizes = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-xs',
  };

  return (
    <span
      className={clsx(
        'inline-flex items-center font-medium rounded-full',
        variants[variant],
        sizes[size],
        className
      )}
    >
      {children}
    </span>
  );
}

// Status badge for common status values
interface StatusBadgeProps {
  status: string;
  className?: string;
}

export function StatusBadge({ status, className }: StatusBadgeProps) {
  const statusConfig: Record<string, { variant: BadgeProps['variant']; label: string }> = {
    // General statuses
    active: { variant: 'success', label: 'Active' },
    inactive: { variant: 'default', label: 'Inactive' },
    draft: { variant: 'default', label: 'Draft' },
    paused: { variant: 'warning', label: 'Paused' },
    archived: { variant: 'default', label: 'Archived' },

    // Contact statuses
    lead: { variant: 'info', label: 'Lead' },
    contacted: { variant: 'primary', label: 'Contacted' },
    qualified: { variant: 'warning', label: 'Qualified' },
    customer: { variant: 'success', label: 'Customer' },
    churned: { variant: 'danger', label: 'Churned' },

    // Health statuses
    healthy: { variant: 'success', label: 'Healthy' },
    warning: { variant: 'warning', label: 'Warning' },
    critical: { variant: 'danger', label: 'Critical' },
    offline: { variant: 'danger', label: 'Offline' },
  };

  const config = statusConfig[status.toLowerCase()] || {
    variant: 'default' as const,
    label: status,
  };

  return (
    <Badge variant={config.variant} className={className}>
      {config.label}
    </Badge>
  );
}

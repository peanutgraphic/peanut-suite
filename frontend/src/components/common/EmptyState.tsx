import type { ReactNode } from 'react';
import { clsx } from 'clsx';
import {
  Inbox,
  Search,
  AlertCircle,
  CheckCircle2,
  Users,
  Link2,
  BarChart3,
  Bell,
  Shield
} from 'lucide-react';

interface EmptyStateProps {
  icon?: ReactNode;
  title: string;
  description?: string;
  action?: ReactNode;
  className?: string;
  variant?: 'default' | 'search' | 'error' | 'success' | 'no-data';
  size?: 'sm' | 'md' | 'lg';
}

export default function EmptyState({
  icon,
  title,
  description,
  action,
  className,
  variant = 'default',
  size = 'md',
}: EmptyStateProps) {
  const sizeClasses = {
    sm: 'py-8',
    md: 'py-12',
    lg: 'py-16',
  };

  const iconSizes = {
    sm: 'w-10 h-10',
    md: 'w-12 h-12',
    lg: 'w-16 h-16',
  };

  const iconWrapperSizes = {
    sm: 'w-5 h-5',
    md: 'w-6 h-6',
    lg: 'w-8 h-8',
  };

  const variantStyles = {
    default: 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500',
    search: 'bg-blue-50 dark:bg-blue-900/20 text-blue-500 dark:text-blue-400',
    error: 'bg-red-50 dark:bg-red-900/20 text-red-500 dark:text-red-400',
    success: 'bg-green-50 dark:bg-green-900/20 text-green-500 dark:text-green-400',
    'no-data': 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500',
  };

  const defaultIcons = {
    default: Inbox,
    search: Search,
    error: AlertCircle,
    success: CheckCircle2,
    'no-data': BarChart3,
  };

  const DefaultIcon = defaultIcons[variant];

  return (
    <div
      className={clsx(
        'flex flex-col items-center justify-center px-4 text-center',
        sizeClasses[size],
        className
      )}
      role="status"
      aria-live="polite"
    >
      <div className={clsx(
        'rounded-full flex items-center justify-center mb-4',
        iconSizes[size],
        variantStyles[variant]
      )}>
        {icon || <DefaultIcon className={iconWrapperSizes[size]} aria-hidden="true" />}
      </div>
      <h3 className={clsx(
        'font-medium text-slate-900 dark:text-slate-100 mb-1',
        size === 'sm' ? 'text-base' : size === 'lg' ? 'text-xl' : 'text-lg'
      )}>
        {title}
      </h3>
      {description && (
        <p className={clsx(
          'text-slate-500 dark:text-slate-400 max-w-sm mb-4',
          size === 'sm' ? 'text-xs' : 'text-sm'
        )}>
          {description}
        </p>
      )}
      {action && <div className="mt-2">{action}</div>}
    </div>
  );
}

// Type for specific empty state variants where title has a default value
type EmptyStateVariantProps = Partial<Pick<EmptyStateProps, 'title' | 'description'>> &
  Pick<EmptyStateProps, 'action' | 'className'>;

// Specific empty state variants
export function NoDataEmptyState({
  title = 'No data available',
  description = 'Data will appear here once it becomes available.',
  action,
  className,
}: EmptyStateVariantProps) {
  return (
    <EmptyState
      variant="no-data"
      icon={<BarChart3 className="w-6 h-6" />}
      title={title}
      description={description}
      action={action}
      className={className}
    />
  );
}

export function NoResultsEmptyState({
  title = 'No results found',
  description = 'Try adjusting your search or filters.',
  action,
  className,
}: EmptyStateVariantProps) {
  return (
    <EmptyState
      variant="search"
      icon={<Search className="w-6 h-6" />}
      title={title}
      description={description}
      action={action}
      className={className}
    />
  );
}

export function NoContactsEmptyState({
  title = 'No contacts yet',
  description = 'Contacts will appear here when visitors submit forms or interact with your site.',
  action,
  className,
}: EmptyStateVariantProps) {
  return (
    <EmptyState
      variant="default"
      icon={<Users className="w-6 h-6" />}
      title={title}
      description={description}
      action={action}
      className={className}
    />
  );
}

export function NoLinksEmptyState({
  title = 'No links created',
  description = 'Create your first trackable link to start monitoring clicks and conversions.',
  action,
  className,
}: EmptyStateVariantProps) {
  return (
    <EmptyState
      variant="default"
      icon={<Link2 className="w-6 h-6" />}
      title={title}
      description={description}
      action={action}
      className={className}
    />
  );
}

export function ErrorEmptyState({
  title = 'Something went wrong',
  description = 'We encountered an error loading this data. Please try again.',
  action,
  className,
}: EmptyStateVariantProps) {
  return (
    <EmptyState
      variant="error"
      icon={<AlertCircle className="w-6 h-6" />}
      title={title}
      description={description}
      action={action}
      className={className}
    />
  );
}

export function NoNotificationsEmptyState({
  title = 'No notifications',
  description = "You're all caught up! Notifications will appear here.",
  className,
}: Omit<EmptyStateProps, 'variant' | 'icon' | 'action'>) {
  return (
    <EmptyState
      variant="success"
      icon={<Bell className="w-6 h-6" />}
      title={title}
      description={description}
      className={className}
      size="sm"
    />
  );
}

export function NoAccessEmptyState({
  title = 'Access restricted',
  description = 'Upgrade your plan to access this feature.',
  action,
  className,
}: EmptyStateVariantProps) {
  return (
    <EmptyState
      variant="default"
      icon={<Shield className="w-6 h-6" />}
      title={title}
      description={description}
      action={action}
      className={className}
    />
  );
}

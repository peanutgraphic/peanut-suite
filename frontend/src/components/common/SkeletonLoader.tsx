import { clsx } from 'clsx';

interface SkeletonProps {
  className?: string;
  animate?: boolean;
}

function SkeletonBase({ className, animate = true }: SkeletonProps) {
  return (
    <div
      className={clsx(
        'bg-slate-200 dark:bg-slate-700 rounded',
        {
          'animate-pulse': animate,
        },
        className
      )}
      aria-busy="true"
      aria-live="polite"
    />
  );
}

// Text skeleton variants
export function TextSkeleton({ lines = 3, className }: { lines?: number; className?: string }) {
  return (
    <div className={clsx('space-y-3', className)}>
      {Array.from({ length: lines }).map((_, i) => (
        <SkeletonBase
          key={i}
          className={clsx('h-4', {
            'w-full': i < lines - 1,
            'w-3/4': i === lines - 1,
          })}
        />
      ))}
    </div>
  );
}

// Card skeleton
export function CardSkeleton({ className }: { className?: string }) {
  return (
    <div className={clsx('bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700', className)}>
      <div className="space-y-4">
        <div className="flex items-start justify-between">
          <div className="flex-1 space-y-2">
            <SkeletonBase className="h-6 w-1/3" />
            <SkeletonBase className="h-4 w-2/3" />
          </div>
          <SkeletonBase className="h-10 w-10 rounded-full" />
        </div>
        <TextSkeleton lines={2} />
      </div>
    </div>
  );
}

// Table row skeleton
export function TableRowSkeleton({ columns = 5 }: { columns?: number }) {
  return (
    <tr className="border-b border-slate-200 dark:border-slate-700">
      {Array.from({ length: columns }).map((_, i) => (
        <td key={i} className="px-4 py-3">
          <SkeletonBase className="h-4 w-full" />
        </td>
      ))}
    </tr>
  );
}

// Table skeleton
export function TableSkeleton({ rows = 5, columns = 5, className }: { rows?: number; columns?: number; className?: string }) {
  return (
    <div className={clsx('bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden', className)}>
      <table className="w-full">
        <thead className="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
          <tr>
            {Array.from({ length: columns }).map((_, i) => (
              <th key={i} className="px-4 py-3 text-left">
                <SkeletonBase className="h-4 w-24" />
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {Array.from({ length: rows }).map((_, i) => (
            <TableRowSkeleton key={i} columns={columns} />
          ))}
        </tbody>
      </table>
    </div>
  );
}

// Stat card skeleton
export function StatCardSkeleton({ className }: { className?: string }) {
  return (
    <div className={clsx('bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700', className)}>
      <div className="flex items-center justify-between mb-4">
        <SkeletonBase className="h-5 w-32" />
        <SkeletonBase className="h-8 w-8 rounded" />
      </div>
      <SkeletonBase className="h-8 w-24 mb-2" />
      <SkeletonBase className="h-4 w-40" />
    </div>
  );
}

// Chart skeleton
export function ChartSkeleton({ className, height = 'h-64' }: { className?: string; height?: string }) {
  return (
    <div className={clsx('bg-white dark:bg-slate-800 rounded-lg p-6 border border-slate-200 dark:border-slate-700', className)}>
      <div className="mb-4">
        <SkeletonBase className="h-6 w-48 mb-2" />
        <SkeletonBase className="h-4 w-32" />
      </div>
      <SkeletonBase className={clsx('w-full', height)} />
    </div>
  );
}

// Avatar skeleton
export function AvatarSkeleton({ size = 'md', className }: { size?: 'sm' | 'md' | 'lg'; className?: string }) {
  const sizes = {
    sm: 'h-8 w-8',
    md: 'h-10 w-10',
    lg: 'h-12 w-12',
  };

  return <SkeletonBase className={clsx('rounded-full', sizes[size], className)} />;
}

// List item skeleton
export function ListItemSkeleton({ showAvatar = true, className }: { showAvatar?: boolean; className?: string }) {
  return (
    <div className={clsx('flex items-center gap-3 p-4', className)}>
      {showAvatar && <AvatarSkeleton />}
      <div className="flex-1 space-y-2">
        <SkeletonBase className="h-4 w-3/4" />
        <SkeletonBase className="h-3 w-1/2" />
      </div>
    </div>
  );
}

// Form skeleton
export function FormSkeleton({ fields = 4, className }: { fields?: number; className?: string }) {
  return (
    <div className={clsx('space-y-6', className)}>
      {Array.from({ length: fields }).map((_, i) => (
        <div key={i} className="space-y-2">
          <SkeletonBase className="h-4 w-24" />
          <SkeletonBase className="h-10 w-full" />
        </div>
      ))}
      <div className="flex gap-3 pt-4">
        <SkeletonBase className="h-10 w-24" />
        <SkeletonBase className="h-10 w-24" />
      </div>
    </div>
  );
}

// Page skeleton
export function PageSkeleton({ className }: { className?: string }) {
  return (
    <div className={clsx('space-y-6', className)}>
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <SkeletonBase className="h-8 w-64" />
          <SkeletonBase className="h-4 w-96" />
        </div>
        <SkeletonBase className="h-10 w-32" />
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <StatCardSkeleton key={i} />
        ))}
      </div>
      <TableSkeleton rows={8} columns={6} />
    </div>
  );
}

// Default export for simple use cases
export default function SkeletonLoader({
  variant = 'text',
  className,
  ...props
}: {
  variant?: 'text' | 'card' | 'table' | 'stat' | 'chart' | 'avatar' | 'list' | 'form' | 'page';
  className?: string;
  [key: string]: any;
}) {
  const variants = {
    text: TextSkeleton,
    card: CardSkeleton,
    table: TableSkeleton,
    stat: StatCardSkeleton,
    chart: ChartSkeleton,
    avatar: AvatarSkeleton,
    list: ListItemSkeleton,
    form: FormSkeleton,
    page: PageSkeleton,
  };

  const Component = variants[variant];
  return <Component className={className} {...props} />;
}

import { type ReactNode } from 'react';
import { clsx } from 'clsx';
import {
  flexRender,
  getCoreRowModel,
  useReactTable,
  type ColumnDef,
  type RowSelectionState,
  type OnChangeFn,
} from '@tanstack/react-table';
import { ChevronUp, ChevronDown, ChevronsUpDown } from 'lucide-react';
import EmptyState from './EmptyState';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
interface TableProps<T> {
  data: T[];
  columns: ColumnDef<T, any>[];
  loading?: boolean;
  emptyState?: ReactNode;
  rowSelection?: RowSelectionState;
  onRowSelectionChange?: OnChangeFn<RowSelectionState>;
  onRowClick?: (row: T) => void;
  className?: string;
}

export default function Table<T>({
  data,
  columns,
  loading = false,
  emptyState,
  rowSelection,
  onRowSelectionChange,
  onRowClick,
  className,
}: TableProps<T>) {
  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    state: {
      rowSelection: rowSelection || {},
    },
    onRowSelectionChange,
    enableRowSelection: !!onRowSelectionChange,
  });

  if (!loading && data.length === 0) {
    return (
      <div className={className}>
        {emptyState || <EmptyState title="No data" description="No items found." />}
      </div>
    );
  }

  return (
    <div className={clsx('overflow-x-auto', className)}>
      <table className="w-full">
        <thead>
          {table.getHeaderGroups().map((headerGroup) => (
            <tr key={headerGroup.id} className="border-b border-slate-200">
              {headerGroup.headers.map((header) => (
                <th
                  key={header.id}
                  className="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider"
                  style={{ width: header.getSize() }}
                >
                  {header.isPlaceholder
                    ? null
                    : flexRender(header.column.columnDef.header, header.getContext())}
                </th>
              ))}
            </tr>
          ))}
        </thead>
        <tbody className="divide-y divide-slate-100">
          {loading ? (
            Array.from({ length: 5 }).map((_, i) => (
              <tr key={i}>
                {columns.map((_, j) => (
                  <td key={j} className="px-4 py-3">
                    <div className="h-5 bg-slate-100 rounded animate-pulse" />
                  </td>
                ))}
              </tr>
            ))
          ) : (
            table.getRowModel().rows.map((row) => (
              <tr
                key={row.id}
                className={clsx(
                  'hover:bg-slate-50 transition-colors',
                  onRowClick && 'cursor-pointer',
                  row.getIsSelected() && 'bg-primary-50'
                )}
                onClick={() => onRowClick?.(row.original)}
              >
                {row.getVisibleCells().map((cell) => (
                  <td key={cell.id} className="px-4 py-3 text-sm text-slate-700">
                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                  </td>
                ))}
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
}

// Sortable header helper
interface SortableHeaderProps {
  title: string;
  sorted?: 'asc' | 'desc' | false;
  onSort?: () => void;
}

export function SortableHeader({ title, sorted, onSort }: SortableHeaderProps) {
  return (
    <button
      onClick={onSort}
      className="flex items-center gap-1 hover:text-slate-700 transition-colors"
    >
      {title}
      {sorted === 'asc' ? (
        <ChevronUp className="w-4 h-4" />
      ) : sorted === 'desc' ? (
        <ChevronDown className="w-4 h-4" />
      ) : (
        <ChevronsUpDown className="w-4 h-4 opacity-50" />
      )}
    </button>
  );
}

// Checkbox column helper
export function createCheckboxColumn<T>(): ColumnDef<T, unknown> {
  return {
    id: 'select',
    header: ({ table }) => (
      <input
        type="checkbox"
        checked={table.getIsAllRowsSelected()}
        onChange={table.getToggleAllRowsSelectedHandler()}
        className="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
      />
    ),
    cell: ({ row }) => (
      <input
        type="checkbox"
        checked={row.getIsSelected()}
        onChange={row.getToggleSelectedHandler()}
        onClick={(e) => e.stopPropagation()}
        className="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
      />
    ),
    size: 40,
  };
}

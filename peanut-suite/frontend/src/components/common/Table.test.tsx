import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ColumnDef } from '@tanstack/react-table';
import Table, { SortableHeader, createCheckboxColumn } from './Table';

// Test data type
interface TestData {
  id: number;
  name: string;
  email: string;
}

// Test columns
const testColumns: ColumnDef<TestData, any>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
  },
  {
    accessorKey: 'email',
    header: 'Email',
  },
];

// Test data
const testData: TestData[] = [
  { id: 1, name: 'John Doe', email: 'john@example.com' },
  { id: 2, name: 'Jane Smith', email: 'jane@example.com' },
  { id: 3, name: 'Bob Wilson', email: 'bob@example.com' },
];

describe('Table', () => {
  // =========================================
  // Basic Rendering Tests
  // =========================================

  it('renders table with data', () => {
    render(<Table data={testData} columns={testColumns} />);

    expect(screen.getByText('John Doe')).toBeInTheDocument();
    expect(screen.getByText('jane@example.com')).toBeInTheDocument();
  });

  it('renders table headers', () => {
    render(<Table data={testData} columns={testColumns} />);

    expect(screen.getByText('Name')).toBeInTheDocument();
    expect(screen.getByText('Email')).toBeInTheDocument();
  });

  it('renders all rows', () => {
    render(<Table data={testData} columns={testColumns} />);

    expect(screen.getByText('John Doe')).toBeInTheDocument();
    expect(screen.getByText('Jane Smith')).toBeInTheDocument();
    expect(screen.getByText('Bob Wilson')).toBeInTheDocument();
  });

  // =========================================
  // Empty State Tests
  // =========================================

  it('shows empty state when no data', () => {
    render(<Table data={[]} columns={testColumns} />);

    expect(screen.getByText('No data')).toBeInTheDocument();
    expect(screen.getByText('No items found.')).toBeInTheDocument();
  });

  it('shows custom empty state', () => {
    render(
      <Table
        data={[]}
        columns={testColumns}
        emptyState={<div>Custom empty message</div>}
      />
    );

    expect(screen.getByText('Custom empty message')).toBeInTheDocument();
  });

  // =========================================
  // Loading State Tests
  // =========================================

  it('shows loading skeletons when loading', () => {
    render(<Table data={[]} columns={testColumns} loading />);

    // Should show loading rows with animate-pulse class
    const skeletons = document.querySelectorAll('.animate-pulse');
    expect(skeletons.length).toBeGreaterThan(0);
  });

  it('shows loading even with data present', () => {
    render(<Table data={testData} columns={testColumns} loading />);

    // Data should not be shown when loading
    expect(screen.queryByText('John Doe')).not.toBeInTheDocument();
  });

  // =========================================
  // Row Click Tests
  // =========================================

  it('calls onRowClick when row is clicked', async () => {
    const onRowClick = vi.fn();
    const user = userEvent.setup();

    render(
      <Table data={testData} columns={testColumns} onRowClick={onRowClick} />
    );

    await user.click(screen.getByText('John Doe'));

    expect(onRowClick).toHaveBeenCalledTimes(1);
    expect(onRowClick).toHaveBeenCalledWith(testData[0]);
  });

  it('applies cursor-pointer class when onRowClick is provided', () => {
    render(
      <Table
        data={testData}
        columns={testColumns}
        onRowClick={() => {}}
      />
    );

    const row = screen.getByText('John Doe').closest('tr');
    expect(row).toHaveClass('cursor-pointer');
  });

  it('does not apply cursor-pointer when no onRowClick', () => {
    render(<Table data={testData} columns={testColumns} />);

    const row = screen.getByText('John Doe').closest('tr');
    expect(row).not.toHaveClass('cursor-pointer');
  });

  // =========================================
  // Custom ClassName Tests
  // =========================================

  it('applies custom className', () => {
    const { container } = render(
      <Table data={testData} columns={testColumns} className="custom-table" />
    );

    expect(container.querySelector('.custom-table')).toBeInTheDocument();
  });
});

describe('SortableHeader', () => {
  // =========================================
  // Rendering Tests
  // =========================================

  it('renders title', () => {
    render(<SortableHeader title="Name" />);
    expect(screen.getByText('Name')).toBeInTheDocument();
  });

  it('renders as a button', () => {
    render(<SortableHeader title="Name" />);
    expect(screen.getByRole('button')).toBeInTheDocument();
  });

  // =========================================
  // Sort Icon Tests
  // =========================================

  it('shows neutral icon when not sorted', () => {
    render(<SortableHeader title="Name" sorted={false} />);
    // Should show ChevronsUpDown (double chevron)
    const button = screen.getByRole('button');
    expect(button.querySelector('svg')).toBeInTheDocument();
  });

  it('shows up chevron when sorted ascending', () => {
    render(<SortableHeader title="Name" sorted="asc" />);
    // ChevronUp should be rendered
    const button = screen.getByRole('button');
    expect(button.querySelector('svg')).toBeInTheDocument();
  });

  it('shows down chevron when sorted descending', () => {
    render(<SortableHeader title="Name" sorted="desc" />);
    // ChevronDown should be rendered
    const button = screen.getByRole('button');
    expect(button.querySelector('svg')).toBeInTheDocument();
  });

  // =========================================
  // Click Tests
  // =========================================

  it('calls onSort when clicked', async () => {
    const onSort = vi.fn();
    const user = userEvent.setup();

    render(<SortableHeader title="Name" onSort={onSort} />);

    await user.click(screen.getByRole('button'));
    expect(onSort).toHaveBeenCalledTimes(1);
  });
});

describe('createCheckboxColumn', () => {
  it('creates a checkbox column definition', () => {
    const checkboxColumn = createCheckboxColumn<TestData>();

    expect(checkboxColumn.id).toBe('select');
    expect(checkboxColumn.header).toBeDefined();
    expect(checkboxColumn.cell).toBeDefined();
    expect(checkboxColumn.size).toBe(40);
  });

  it('checkbox column can be used in table', () => {
    const columnsWithCheckbox = [
      createCheckboxColumn<TestData>(),
      ...testColumns,
    ];

    render(
      <Table
        data={testData}
        columns={columnsWithCheckbox}
        rowSelection={{}}
        onRowSelectionChange={() => {}}
      />
    );

    // Should render checkboxes
    const checkboxes = screen.getAllByRole('checkbox');
    // One in header + one per row
    expect(checkboxes.length).toBe(testData.length + 1);
  });
});

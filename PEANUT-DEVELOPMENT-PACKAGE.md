# Peanut Development Package

> A comprehensive design system and development guide for building WordPress plugins with React admin interfaces.

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [UI Components](#ui-components)
4. [Page Patterns](#page-patterns)
5. [API Patterns](#api-patterns)
6. [State Management](#state-management)
7. [Backend Services](#backend-services)
8. [REST Controllers](#rest-controllers)
9. [Database Schema](#database-schema)
10. [Multi-Tenancy](#multi-tenancy)
11. [Permissions System](#permissions-system)
12. [Styling Guide](#styling-guide)
13. [Implementation Checklist](#implementation-checklist)

---

## Overview

The Peanut Development Package is a battle-tested architecture for building modern WordPress plugins with:

- **React 18** frontend with TypeScript
- **TanStack Query** for server state
- **Zustand** for client state
- **Tailwind CSS** for styling
- **TanStack Table** for data tables
- **Chart.js** for visualizations
- **WordPress REST API** backend
- **Multi-tenant** architecture support

---

## Architecture

### Data Flow

```
User Action
    ↓
React Component
    ↓
useMutation / useQuery (TanStack Query)
    ↓
API Client (Axios)
    ↓
WordPress REST API
    ↓
PHP Controller
    ↓
Service Class
    ↓
Database
    ↓
Response
    ↓
React Query Cache
    ↓
UI Re-render
```

### File Structure

```
plugin-name/
├── plugin-name.php              # Main plugin file
├── core/
│   ├── class-plugin-core.php    # Core initialization
│   ├── api/
│   │   ├── class-rest-controller.php  # Base controller
│   │   └── class-*-controller.php     # Resource controllers
│   ├── database/
│   │   └── class-database.php   # Schema & migrations
│   └── services/
│       └── class-*-service.php  # Business logic
├── modules/
│   └── module-name/
│       ├── class-module.php     # Module initialization
│       ├── api/                 # Module controllers
│       └── services/            # Module services
├── frontend/
│   ├── package.json
│   ├── vite.config.ts
│   ├── tsconfig.json
│   └── src/
│       ├── main.tsx             # Entry point
│       ├── App.tsx              # Routes & providers
│       ├── api/
│       │   ├── client.ts        # Axios config
│       │   └── endpoints.ts     # API functions
│       ├── components/
│       │   ├── common/          # Reusable UI
│       │   └── layout/          # Layout components
│       ├── pages/               # Page components
│       ├── store/               # Zustand stores
│       ├── types/               # TypeScript types
│       ├── constants/           # Static data
│       └── utils/               # Helper functions
└── assets/
    └── dist/                    # Built frontend
```

---

## UI Components

### Form Components

#### Button
```tsx
import { Button } from '../components/common';

<Button
  variant="primary"      // primary | secondary | outline | ghost | danger | success
  size="md"              // sm | md | lg
  loading={false}
  disabled={false}
  icon={<Plus />}
  iconPosition="left"    // left | right
  onClick={() => {}}
>
  Click Me
</Button>
```

#### Input & Textarea
```tsx
import { Input, Textarea } from '../components/common';

<Input
  label="Email"
  type="email"
  placeholder="you@example.com"
  value={email}
  onChange={(e) => setEmail(e.target.value)}
  error="Invalid email"
  hint="We'll never share your email"
  leftIcon={<Mail />}
  required
  fullWidth
/>

<Textarea
  label="Description"
  value={description}
  onChange={(e) => setDescription(e.target.value)}
  rows={4}
/>
```

#### Select
```tsx
import { Select } from '../components/common';

<Select
  label="Status"
  options={[
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
  ]}
  value={status}
  onChange={(e) => setStatus(e.target.value)}
  placeholder="Select status"
  tooltip="Current item status"
/>
```

#### Entity Selectors
```tsx
import { ProjectSelector, ClientSelector } from '../components/common';

<ClientSelector
  value={clientId}
  onChange={(id) => setClientId(id)}
  label="Client"
  required
/>

<ProjectSelector
  value={projectId}
  onChange={(id) => setProjectId(id)}
  label="Project"
  required
/>
```

### Display Components

#### Card
```tsx
import { Card, CardHeader, StatCard } from '../components/common';

<Card padding="md">
  <CardHeader
    title="Recent Activity"
    description="Last 7 days"
    action={<Button size="sm">View All</Button>}
  />
  {/* Content */}
</Card>

<StatCard
  title="Total Revenue"
  value="$12,345"
  change={{ type: 'increase', value: '+12%' }}
  icon={<DollarSign />}
  tooltip="Revenue this month"
/>
```

#### Badge & StatusBadge
```tsx
import { Badge, StatusBadge } from '../components/common';

<Badge variant="primary" size="sm">New</Badge>
<Badge variant="success">Active</Badge>
<Badge variant="danger">Expired</Badge>

<StatusBadge status="active" />    // Green
<StatusBadge status="lead" />      // Blue
<StatusBadge status="customer" />  // Purple
<StatusBadge status="churned" />   // Red
```

#### Table
```tsx
import { Table, createCheckboxColumn, SortableHeader } from '../components/common';
import { createColumnHelper } from '@tanstack/react-table';

const columnHelper = createColumnHelper<Item>();

const columns = [
  createCheckboxColumn<Item>(),
  columnHelper.accessor('name', {
    header: ({ column }) => <SortableHeader column={column} title="Name" />,
    cell: (info) => info.getValue(),
  }),
  columnHelper.accessor('status', {
    header: 'Status',
    cell: (info) => <StatusBadge status={info.getValue()} />,
  }),
  columnHelper.display({
    id: 'actions',
    cell: (info) => (
      <Button size="sm" variant="ghost" onClick={() => handleEdit(info.row.original)}>
        Edit
      </Button>
    ),
  }),
];

<Table
  data={items}
  columns={columns}
  loading={isLoading}
  rowSelection={selectedRows}
  onRowSelectionChange={setSelectedRows}
  onRowClick={(row) => navigate(`/items/${row.id}`)}
/>
```

#### Pagination
```tsx
import { Pagination } from '../components/common';

<Pagination
  page={page}
  totalPages={data.total_pages}
  total={data.total}
  perPage={20}
  onPageChange={setPage}
/>
```

### Feedback Components

#### Modal & ConfirmModal
```tsx
import { Modal, ConfirmModal } from '../components/common';

<Modal
  isOpen={isOpen}
  onClose={() => setIsOpen(false)}
  title="Create Item"
  size="md"  // sm | md | lg | xl
>
  {/* Form content */}
  <div className="flex justify-end gap-3 mt-6 pt-4 border-t">
    <Button variant="outline" onClick={() => setIsOpen(false)}>Cancel</Button>
    <Button onClick={handleSubmit} loading={isPending}>Save</Button>
  </div>
</Modal>

<ConfirmModal
  isOpen={deleteId !== null}
  onClose={() => setDeleteId(null)}
  onConfirm={() => deleteMutation.mutate(deleteId)}
  title="Delete Item"
  message="Are you sure? This cannot be undone."
  confirmText="Delete"
  variant="danger"
  loading={deleteMutation.isPending}
/>
```

#### Toast Notifications
```tsx
import { useToast } from '../components/common';

const toast = useToast();

toast.success('Item created successfully');
toast.error('Failed to create item');
toast.info('Processing your request...');
toast.warning('This action cannot be undone');
```

#### Empty State & Loading
```tsx
import { EmptyState, Skeleton, TableSkeleton, emptyStates } from '../components/common';

// Predefined empty states
<EmptyState type="contacts" />
<EmptyState type="links" />

// Custom empty state
<EmptyState
  title="No items found"
  description="Create your first item to get started"
  action={<Button onClick={() => setCreateOpen(true)}>Create Item</Button>}
/>

// Loading states
<Skeleton className="h-4 w-32" />
<TableSkeleton rows={5} columns={4} />
```

### Utility Components

#### Tooltip
```tsx
import { Tooltip, InfoTooltip } from '../components/common';

<Tooltip content="This is helpful information">
  <Button>Hover me</Button>
</Tooltip>

<InfoTooltip content="Explanation of this field" />
```

#### Charts
```tsx
import { LineChart, BarChart, DoughnutChart, Sparkline } from '../components/common';

<LineChart
  data={chartData}
  options={chartOptions}
/>

<Sparkline data={[10, 20, 15, 25, 30]} color="green" />
```

---

## Page Patterns

### List Page Pattern

Standard structure for pages that display a list of items with CRUD operations:

```tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { createColumnHelper } from '@tanstack/react-table';
import { Plus, Trash2, Search, Filter, Download } from 'lucide-react';
import { Layout } from '../components/layout';
import {
  Card, Button, Input, Table, Pagination, Modal, ConfirmModal,
  StatusBadge, createCheckboxColumn, useToast, SampleDataBanner
} from '../components/common';
import { itemsApi } from '../api/endpoints';
import type { Item } from '../types';
import { useFilterStore, useProjectStore } from '../store';
import { exportToCSV } from '../utils';

const columnHelper = createColumnHelper<Item>();

export default function Items() {
  const queryClient = useQueryClient();
  const toast = useToast();
  const { currentProject } = useProjectStore();

  // Pagination & Selection
  const [page, setPage] = useState(1);
  const [selectedRows, setSelectedRows] = useState<Record<string, boolean>>({});

  // Modals
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [deleteId, setDeleteId] = useState<number | null>(null);

  // Filters
  const { itemFilters, setItemFilter, resetItemFilters } = useFilterStore();

  // Sample data toggle
  const [showSampleData, setShowSampleData] = useState(true);

  // Fetch data
  const { data, isLoading } = useQuery({
    queryKey: ['items', page, itemFilters, currentProject?.id],
    queryFn: () => itemsApi.getAll({
      page,
      per_page: 20,
      project_id: currentProject?.id,
      search: itemFilters.search || undefined,
      status: itemFilters.status || undefined,
    }),
  });

  // Mutations
  const createMutation = useMutation({
    mutationFn: itemsApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['items'] });
      setCreateModalOpen(false);
      toast.success('Item created successfully');
    },
    onError: () => toast.error('Failed to create item'),
  });

  const deleteMutation = useMutation({
    mutationFn: itemsApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['items'] });
      setDeleteId(null);
      toast.success('Item deleted');
    },
  });

  // Sample data handling
  const hasNoRealData = !isLoading && (!data?.data || data.data.length === 0);
  const displaySampleData = hasNoRealData && showSampleData;
  const displayData = displaySampleData ? sampleItems : (data?.data || []);

  // Table columns
  const columns = [
    createCheckboxColumn<Item>(),
    columnHelper.accessor('name', {
      header: 'Name',
      cell: (info) => <span className="font-medium">{info.getValue()}</span>,
    }),
    columnHelper.accessor('status', {
      header: 'Status',
      cell: (info) => <StatusBadge status={info.getValue()} />,
    }),
    columnHelper.accessor('created_at', {
      header: 'Created',
      cell: (info) => new Date(info.getValue()).toLocaleDateString(),
    }),
    columnHelper.display({
      id: 'actions',
      cell: (info) => (
        <button
          onClick={() => setDeleteId(info.row.original.id)}
          className="p-1.5 text-slate-400 hover:text-red-600 rounded"
        >
          <Trash2 className="w-4 h-4" />
        </button>
      ),
    }),
  ];

  const selectedCount = Object.values(selectedRows).filter(Boolean).length;

  return (
    <Layout title="Items" description="Manage your items">
      {/* Header Actions */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-4">
          <Input
            type="text"
            placeholder="Search..."
            leftIcon={<Search className="w-4 h-4" />}
            value={itemFilters.search}
            onChange={(e) => setItemFilter('search', e.target.value)}
            className="w-64"
          />
          <Button variant="outline" size="sm" icon={<Filter />} onClick={resetItemFilters}>
            Clear Filters
          </Button>
        </div>
        <div className="flex items-center gap-3">
          {selectedCount > 0 && (
            <Button variant="danger" size="sm" icon={<Trash2 />}>
              Delete ({selectedCount})
            </Button>
          )}
          <Button variant="outline" size="sm" icon={<Download />} onClick={() => exportToCSV(displayData)}>
            Export
          </Button>
          <Button icon={<Plus />} onClick={() => setCreateModalOpen(true)}>
            Add Item
          </Button>
        </div>
      </div>

      {/* Sample Data Banner */}
      {displaySampleData && <SampleDataBanner onDismiss={() => setShowSampleData(false)} />}

      {/* Filters Card */}
      <Card className="mb-6">
        <div className="flex flex-wrap gap-4">
          <div className="flex-1 min-w-[150px]">
            <label className="text-xs font-medium text-slate-500 mb-1 block">Status</label>
            <select
              className="w-full border rounded-lg px-3 py-2 text-sm"
              value={itemFilters.status}
              onChange={(e) => setItemFilter('status', e.target.value)}
            >
              <option value="">All</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
      </Card>

      {/* Table */}
      <Card>
        <Table
          data={displayData}
          columns={columns}
          loading={isLoading}
          rowSelection={selectedRows}
          onRowSelectionChange={setSelectedRows}
        />
        {data && data.total_pages > 1 && (
          <Pagination
            page={page}
            totalPages={data.total_pages}
            total={data.total}
            perPage={20}
            onPageChange={setPage}
          />
        )}
      </Card>

      {/* Create Modal */}
      <Modal isOpen={createModalOpen} onClose={() => setCreateModalOpen(false)} title="Add Item">
        {/* Form fields */}
        <div className="flex justify-end gap-3 mt-6 pt-4 border-t">
          <Button variant="outline" onClick={() => setCreateModalOpen(false)}>Cancel</Button>
          <Button onClick={handleCreate} loading={createMutation.isPending}>Create</Button>
        </div>
      </Modal>

      {/* Delete Confirmation */}
      <ConfirmModal
        isOpen={deleteId !== null}
        onClose={() => setDeleteId(null)}
        onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
        title="Delete Item"
        message="Are you sure? This cannot be undone."
        confirmText="Delete"
        variant="danger"
        loading={deleteMutation.isPending}
      />
    </Layout>
  );
}
```

### Detail Page Pattern

```tsx
export default function ItemDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toast = useToast();
  const queryClient = useQueryClient();

  const { data: item, isLoading } = useQuery({
    queryKey: ['item', id],
    queryFn: () => itemsApi.getById(Number(id)),
    enabled: !!id,
  });

  const updateMutation = useMutation({
    mutationFn: (data) => itemsApi.update(Number(id), data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['item', id] });
      toast.success('Item updated');
    },
  });

  if (isLoading) return <Layout><TableSkeleton /></Layout>;
  if (!item) return <Layout><EmptyState title="Item not found" /></Layout>;

  return (
    <Layout title={item.name} description="Item details">
      {/* Tabs: Overview, Activity, Settings */}
      <Card>
        {/* Detail content */}
      </Card>
    </Layout>
  );
}
```

### Dashboard Pattern

```tsx
export default function Dashboard() {
  const [period, setPeriod] = useState<'7d' | '30d' | '90d'>('7d');

  const { data: stats } = useQuery({
    queryKey: ['dashboard-stats', period],
    queryFn: () => dashboardApi.getStats(period),
  });

  return (
    <Layout title="Dashboard" description="Overview of your activity">
      {/* Stat Cards Row */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <StatCard title="Total Items" value={stats?.items || 0} icon={<Box />} />
        <StatCard title="Active" value={stats?.active || 0} icon={<Check />} />
        <StatCard
          title="Growth"
          value={`${stats?.growth || 0}%`}
          change={{ type: 'increase', value: '+5%' }}
          icon={<TrendingUp />}
        />
      </div>

      {/* Charts */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader title="Activity Over Time" />
          <LineChart data={stats?.timeline} />
        </Card>
        <Card>
          <CardHeader title="By Category" />
          <DoughnutChart data={stats?.categories} />
        </Card>
      </div>

      {/* Recent Activity */}
      <Card className="mt-6">
        <CardHeader title="Recent Activity" action={<Button size="sm">View All</Button>} />
        <ul>{/* Activity items */}</ul>
      </Card>
    </Layout>
  );
}
```

---

## API Patterns

### Client Configuration

```tsx
// api/client.ts
import axios from 'axios';

declare global {
  interface Window {
    peanutData?: {
      apiUrl: string;
      nonce: string;
      user?: { id: number; name: string; email: string };
      account?: { id: number; name: string; tier: string };
    };
  }
}

const api = axios.create({
  baseURL: window.peanutData?.apiUrl || '/wp-json/plugin/v1',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': window.peanutData?.nonce || '',
  },
});

// Response interceptor - unwrap data
api.interceptors.response.use(
  (response) => response.data?.data ?? response.data,
  (error) => {
    const message = error.response?.data?.message || 'An error occurred';
    return Promise.reject(new Error(message));
  }
);

// Helper for POST-as-GET (WAF bypass)
export const getAsPost = async <T>(url: string, params?: object): Promise<T> => {
  const response = await api.post(url, params);
  return response as T;
};

export default api;
```

### Endpoint Organization

```tsx
// api/endpoints.ts
import api, { getAsPost } from './client';
import type { Item, ItemFormData, PaginatedResponse } from '../types';

interface ItemParams {
  page?: number;
  per_page?: number;
  search?: string;
  status?: string;
  project_id?: number;
}

export const itemsApi = {
  // List with pagination
  getAll: (params?: ItemParams): Promise<PaginatedResponse<Item>> =>
    api.get('/items', { params }),

  // Single item
  getById: (id: number): Promise<Item> =>
    api.get(`/items/${id}`),

  // Create
  create: (data: ItemFormData): Promise<Item> =>
    api.post('/items', data),

  // Update
  update: (id: number, data: Partial<ItemFormData>): Promise<Item> =>
    api.put(`/items/${id}`, data),

  // Delete
  delete: (id: number): Promise<void> =>
    api.delete(`/items/${id}`),

  // Bulk delete (use POST for WAF)
  bulkDelete: (ids: number[]): Promise<void> =>
    getAsPost('/items/bulk-delete', { ids }),

  // Custom actions
  archive: (id: number): Promise<Item> =>
    api.post(`/items/${id}/archive`),

  getStats: (id: number, days: number = 30): Promise<ItemStats> =>
    api.get(`/items/${id}/stats`, { params: { days } }),
};
```

---

## State Management

### Zustand Store Pattern

```tsx
// store/useItemStore.ts
import { create } from 'zustand';
import { persist } from 'zustand/middleware';

interface ItemState {
  // State
  currentItem: Item | null;
  items: Item[];
  isLoading: boolean;
  error: string | null;

  // Actions
  setCurrentItem: (item: Item | null) => void;
  setItems: (items: Item[]) => void;
  setLoading: (loading: boolean) => void;
  setError: (error: string | null) => void;
  reset: () => void;
}

const initialState = {
  currentItem: null,
  items: [],
  isLoading: false,
  error: null,
};

export const useItemStore = create<ItemState>()(
  persist(
    (set) => ({
      ...initialState,

      setCurrentItem: (item) => set({ currentItem: item }),
      setItems: (items) => set({ items }),
      setLoading: (isLoading) => set({ isLoading }),
      setError: (error) => set({ error }),
      reset: () => set(initialState),
    }),
    {
      name: 'plugin-item-store',
      partialize: (state) => ({ currentItem: state.currentItem }),
    }
  )
);

// Selectors
export const useCurrentItemId = () => useItemStore((s) => s.currentItem?.id);
```

### Filter Store Pattern

```tsx
// store/useFilterStore.ts
import { create } from 'zustand';

interface FilterState {
  itemFilters: {
    search: string;
    status: string;
    dateFrom: string;
    dateTo: string;
  };
  setItemFilter: (key: string, value: string) => void;
  resetItemFilters: () => void;
}

const defaultFilters = {
  search: '',
  status: '',
  dateFrom: '',
  dateTo: '',
};

export const useFilterStore = create<FilterState>((set) => ({
  itemFilters: { ...defaultFilters },

  setItemFilter: (key, value) =>
    set((state) => ({
      itemFilters: { ...state.itemFilters, [key]: value },
    })),

  resetItemFilters: () =>
    set({ itemFilters: { ...defaultFilters } }),
}));
```

---

## Backend Services

### Service Class Pattern

```php
<?php
/**
 * Item Service
 */

if (!defined('ABSPATH')) exit;

class Plugin_Item_Service {

    private const STATUS_ACTIVE = 'active';
    private const STATUS_ARCHIVED = 'archived';

    // Tier-based limits
    private const TIER_LIMITS = [
        'free' => 10,
        'pro' => 100,
        'agency' => -1, // Unlimited
    ];

    /**
     * Get item by ID
     */
    public static function get_by_id(int $id): ?array {
        global $wpdb;
        $table = Plugin_Database::items_table();

        $item = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        return $item ?: null;
    }

    /**
     * Get all items with filters
     */
    public static function get_all(array $filters = []): array {
        global $wpdb;
        $table = Plugin_Database::items_table();

        $where = ['1=1'];
        $values = [];

        // Account isolation
        if (!empty($filters['account_id'])) {
            $where[] = 'account_id = %d';
            $values[] = $filters['account_id'];
        }

        // Project filter
        if (!empty($filters['project_id'])) {
            $where[] = 'project_id = %d';
            $values[] = $filters['project_id'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $values[] = $filters['status'];
        }

        // Search
        if (!empty($filters['search'])) {
            $where[] = '(name LIKE %s OR description LIKE %s)';
            $search = '%' . $wpdb->esc_like($filters['search']) . '%';
            $values[] = $search;
            $values[] = $search;
        }

        $where_sql = implode(' AND ', $where);

        // Pagination
        $page = max(1, intval($filters['page'] ?? 1));
        $per_page = min(100, max(1, intval($filters['per_page'] ?? 20)));
        $offset = ($page - 1) * $per_page;

        // Order
        $order_by = $filters['order_by'] ?? 'created_at';
        $order = strtoupper($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        // Count total
        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $total = $wpdb->get_var($wpdb->prepare($count_sql, $values));

        // Get items
        $sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d";
        $values[] = $per_page;
        $values[] = $offset;

        $items = $wpdb->get_results($wpdb->prepare($sql, $values), ARRAY_A);

        return [
            'data' => $items,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page),
        ];
    }

    /**
     * Create item
     */
    public static function create(array $data): array {
        global $wpdb;
        $table = Plugin_Database::items_table();

        $insert_data = [
            'account_id' => $data['account_id'],
            'project_id' => $data['project_id'] ?? null,
            'user_id' => get_current_user_id(),
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'status' => self::STATUS_ACTIVE,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $wpdb->insert($table, $insert_data);
        $id = $wpdb->insert_id;

        return self::get_by_id($id);
    }

    /**
     * Update item
     */
    public static function update(int $id, array $data): ?array {
        global $wpdb;
        $table = Plugin_Database::items_table();

        $update_data = ['updated_at' => current_time('mysql')];

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }

        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
        }

        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }

        $wpdb->update($table, $update_data, ['id' => $id]);

        return self::get_by_id($id);
    }

    /**
     * Delete item
     */
    public static function delete(int $id): bool {
        global $wpdb;
        $table = Plugin_Database::items_table();

        return $wpdb->delete($table, ['id' => $id]) !== false;
    }

    /**
     * Check if can create (tier limits)
     */
    public static function can_create(int $account_id): bool {
        $limits = self::get_limits($account_id);
        return $limits['can_create'];
    }

    /**
     * Get tier limits
     */
    public static function get_limits(int $account_id): array {
        global $wpdb;

        $tier = Plugin_Account_Service::get_tier($account_id);
        $max = self::TIER_LIMITS[$tier] ?? self::TIER_LIMITS['free'];

        $table = Plugin_Database::items_table();
        $current = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE account_id = %d", $account_id)
        );

        return [
            'max' => $max,
            'current' => $current,
            'can_create' => $max === -1 || $current < $max,
            'tier' => $tier,
        ];
    }
}
```

---

## REST Controllers

### Base Controller

```php
<?php
/**
 * Base REST Controller
 */

abstract class Plugin_REST_Controller {

    protected string $namespace = 'plugin/v1';
    protected string $rest_base = '';

    abstract public function register_routes(): void;

    /**
     * Standard permission check
     */
    public function permission_callback(WP_REST_Request $request): bool {
        return is_user_logged_in();
    }

    /**
     * Admin-only permission
     */
    public function admin_permission_callback(WP_REST_Request $request): bool {
        return current_user_can('manage_options');
    }

    /**
     * Scope-based permission for API keys
     */
    protected function with_scope(string $scope): callable {
        return function($request) use ($scope) {
            // Check API key header
            $api_key = $request->get_header('X-API-Key');
            if ($api_key) {
                return Plugin_Api_Keys_Service::validate_key($api_key, $scope);
            }
            // Fall back to user auth
            return is_user_logged_in();
        };
    }

    /**
     * Get current account ID
     */
    protected function get_account_id(WP_REST_Request $request): int {
        return Plugin_Account_Service::get_current_account_id();
    }

    /**
     * Standard pagination params
     */
    protected function get_pagination(WP_REST_Request $request): array {
        return [
            'page' => $request->get_param('page') ?? 1,
            'per_page' => $request->get_param('per_page') ?? 20,
        ];
    }

    /**
     * Success response
     */
    protected function success($data, int $status = 200): WP_REST_Response {
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    /**
     * Paginated response
     */
    protected function paginated(array $result): WP_REST_Response {
        return new WP_REST_Response([
            'success' => true,
            'data' => $result['data'],
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'total_pages' => $result['total_pages'],
        ]);
    }

    /**
     * Error response
     */
    protected function error(string $code, string $message, int $status = 400): WP_Error {
        return new WP_Error($code, $message, ['status' => $status]);
    }
}
```

### Resource Controller

```php
<?php
/**
 * Items REST Controller
 */

class Plugin_Items_Controller extends Plugin_REST_Controller {

    protected string $rest_base = 'items';

    public function register_routes(): void {
        // List & Create
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => $this->with_scope('items:read'),
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => $this->with_scope('items:write'),
            ],
        ]);

        // Single item
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => $this->with_scope('items:read'),
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => $this->with_scope('items:write'),
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => $this->with_scope('items:write'),
            ],
        ]);

        // Bulk delete
        register_rest_route($this->namespace, '/' . $this->rest_base . '/bulk-delete', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'bulk_delete'],
                'permission_callback' => $this->with_scope('items:write'),
            ],
        ]);

        // Limits
        register_rest_route($this->namespace, '/' . $this->rest_base . '/limits', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_limits'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response {
        $filters = array_merge(
            $this->get_pagination($request),
            [
                'account_id' => $this->get_account_id($request),
                'project_id' => $request->get_param('project_id'),
                'search' => $request->get_param('search'),
                'status' => $request->get_param('status'),
            ]
        );

        $result = Plugin_Item_Service::get_all($filters);
        return $this->paginated($result);
    }

    public function get_item(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $item = Plugin_Item_Service::get_by_id($request->get_param('id'));

        if (!$item) {
            return $this->error('not_found', 'Item not found', 404);
        }

        return $this->success($item);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $account_id = $this->get_account_id($request);

        if (!Plugin_Item_Service::can_create($account_id)) {
            return $this->error('limit_reached', 'Item limit reached for your plan', 403);
        }

        $data = [
            'account_id' => $account_id,
            'project_id' => $request->get_param('project_id'),
            'name' => $request->get_param('name'),
            'description' => $request->get_param('description'),
        ];

        $item = Plugin_Item_Service::create($data);
        return $this->success($item, 201);
    }

    public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $id = $request->get_param('id');
        $item = Plugin_Item_Service::get_by_id($id);

        if (!$item) {
            return $this->error('not_found', 'Item not found', 404);
        }

        $data = array_filter([
            'name' => $request->get_param('name'),
            'description' => $request->get_param('description'),
            'status' => $request->get_param('status'),
        ], fn($v) => $v !== null);

        $updated = Plugin_Item_Service::update($id, $data);
        return $this->success($updated);
    }

    public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $id = $request->get_param('id');

        if (!Plugin_Item_Service::delete($id)) {
            return $this->error('delete_failed', 'Failed to delete item', 500);
        }

        return $this->success(['deleted' => true]);
    }

    public function bulk_delete(WP_REST_Request $request): WP_REST_Response {
        $ids = $request->get_param('ids') ?? [];

        foreach ($ids as $id) {
            Plugin_Item_Service::delete($id);
        }

        return $this->success(['deleted' => count($ids)]);
    }

    public function get_limits(WP_REST_Request $request): WP_REST_Response {
        $account_id = $this->get_account_id($request);
        $limits = Plugin_Item_Service::get_limits($account_id);
        return $this->success($limits);
    }
}
```

---

## Database Schema

### Database Manager

```php
<?php
/**
 * Database Manager
 */

class Plugin_Database {

    private const DB_VERSION = '1.0.0';

    public static function table(string $name): string {
        global $wpdb;
        return $wpdb->prefix . 'plugin_' . $name;
    }

    public static function items_table(): string {
        return self::table('items');
    }

    public static function create_tables(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Items table
        $sql = "CREATE TABLE " . self::items_table() . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            account_id bigint(20) UNSIGNED NOT NULL,
            project_id bigint(20) UNSIGNED DEFAULT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            name varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            description text,
            status varchar(50) DEFAULT 'active',
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY account_slug (account_id, slug),
            KEY account_id (account_id),
            KEY project_id (project_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql);

        update_option('plugin_db_version', self::DB_VERSION);
    }

    public static function maybe_upgrade(): void {
        $current_version = get_option('plugin_db_version', '0.0.0');

        if (version_compare($current_version, self::DB_VERSION, '<')) {
            self::create_tables();
            self::run_migrations($current_version);
        }
    }

    private static function run_migrations(string $from_version): void {
        // Example migration
        if (version_compare($from_version, '1.0.0', '<')) {
            self::migrate_to_1_0_0();
        }
    }

    private static function migrate_to_1_0_0(): void {
        // Migration logic
    }
}
```

### Standard Table Schema

```sql
CREATE TABLE wp_plugin_items (
    -- Primary key
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Multi-tenancy
    account_id bigint(20) UNSIGNED NOT NULL,
    project_id bigint(20) UNSIGNED DEFAULT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,

    -- Core fields
    name varchar(255) NOT NULL,
    slug varchar(100) NOT NULL,
    description text,
    status varchar(50) DEFAULT 'active',

    -- JSON for flexible data
    settings longtext,
    custom_fields longtext,

    -- Timestamps
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Keys
    PRIMARY KEY (id),
    UNIQUE KEY account_slug (account_id, slug),
    KEY account_id (account_id),
    KEY project_id (project_id),
    KEY user_id (user_id),
    KEY status (status),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Multi-Tenancy

### Account Hierarchy

```
Account (Organization)
├── Owner (1)
├── Members (N) with roles: admin, member, viewer
├── Projects (N)
│   ├── Members (N) with project-specific roles
│   └── Items scoped to project
└── Clients (N)
    ├── Contacts (N)
    └── Projects (N)
```

### Data Isolation Pattern

Every query must include account_id:

```php
// Service method
public static function get_all(int $account_id, array $filters = []): array {
    global $wpdb;
    $table = self::table();

    $sql = "SELECT * FROM {$table} WHERE account_id = %d";
    $values = [$account_id];

    // Additional filters...

    return $wpdb->get_results($wpdb->prepare($sql, $values), ARRAY_A);
}

// Controller
public function get_items(WP_REST_Request $request): WP_REST_Response {
    $account_id = $this->get_account_id($request);
    $items = Plugin_Item_Service::get_all($account_id, $filters);
    return $this->paginated($items);
}
```

---

## Permissions System

### Role Hierarchy

```
owner   → Full access, can delete account
admin   → Full access except delete account
member  → Create, read, update own items
viewer  → Read only
```

### Feature Permissions

```php
// Check feature access
$can_access = Plugin_Account_Service::can_access_feature($account_id, $user_id, 'advanced_analytics');

// Permission structure in database
$permissions = [
    'links' => ['access' => true],
    'analytics' => ['access' => true],
    'team' => ['access' => false],
];
```

### Tier-Based Features

```php
const TIER_FEATURES = [
    'free' => ['links', 'utms', 'contacts'],
    'pro' => ['links', 'utms', 'contacts', 'analytics', 'popups', 'webhooks'],
    'agency' => ['*'], // All features
];

public static function has_feature(string $tier, string $feature): bool {
    $features = self::TIER_FEATURES[$tier] ?? [];
    return in_array('*', $features) || in_array($feature, $features);
}
```

---

## Styling Guide

### Tailwind Classes

```tsx
// Colors
text-slate-900      // Primary text
text-slate-600      // Secondary text
text-slate-400      // Muted text
text-primary-600    // Brand color
text-red-600        // Error/danger
text-green-600      // Success
text-amber-600      // Warning

// Backgrounds
bg-white            // Cards
bg-slate-50         // Page background
bg-primary-50       // Highlighted areas
bg-red-50           // Error states

// Borders
border-slate-200    // Default borders
border-slate-300    // Input borders
border-primary-500  // Focus states

// Spacing
gap-2, gap-3, gap-4 // Flex gaps
p-4, p-6            // Card padding
mb-4, mb-6          // Section margins

// Shadows
shadow-sm           // Cards
shadow-lg           // Modals/dropdowns

// Rounded
rounded-lg          // Cards, inputs
rounded-full        // Avatars, badges
```

### Component Patterns

```tsx
// Card
<div className="bg-white rounded-lg border border-slate-200 shadow-sm p-6">

// Form input
<input className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm
                  focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />

// Button primary
<button className="px-4 py-2 bg-primary-600 text-white rounded-lg font-medium
                   hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500" />

// Button outline
<button className="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg font-medium
                   hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500" />

// Badge
<span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                 bg-primary-100 text-primary-800" />

// Table row hover
<tr className="hover:bg-slate-50 cursor-pointer" />
```

---

## Implementation Checklist

### New Entity Checklist

#### Backend
- [ ] Create database table in `class-database.php`
- [ ] Create service class `class-{entity}-service.php`
  - [ ] get_by_id()
  - [ ] get_all() with filters
  - [ ] create()
  - [ ] update()
  - [ ] delete()
  - [ ] can_create() (tier limits)
  - [ ] get_limits()
- [ ] Create REST controller `class-{entity}-controller.php`
  - [ ] GET /{entities} - list
  - [ ] POST /{entities} - create
  - [ ] GET /{entities}/{id} - get
  - [ ] PUT /{entities}/{id} - update
  - [ ] DELETE /{entities}/{id} - delete
  - [ ] POST /{entities}/bulk-delete
  - [ ] GET /{entities}/limits
- [ ] Register controller in core class
- [ ] Add migration if needed

#### Frontend
- [ ] Add types to `types/index.ts`
  - [ ] Entity interface
  - [ ] FormData interface
  - [ ] Status type
- [ ] Add API endpoints to `api/endpoints.ts`
- [ ] Create list page `pages/{Entities}.tsx`
- [ ] Create detail page `pages/{Entity}Detail.tsx` (if needed)
- [ ] Add route to `App.tsx`
- [ ] Add navigation to `Sidebar.tsx`
- [ ] Add filter state to `useFilterStore.ts` (if needed)
- [ ] Add sample data to `constants/sampleData.ts`
- [ ] Add page description to `constants/pageDescriptions.ts`

### New Feature Checklist

- [ ] Define feature name in tier config
- [ ] Add to permissions system
- [ ] Gate UI with tier check
- [ ] Gate API with scope check
- [ ] Add upgrade prompt for locked features

---

## Quick Reference

### Common Imports

```tsx
// React & Hooks
import { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';

// Table
import { createColumnHelper } from '@tanstack/react-table';

// Icons
import { Plus, Trash2, Search, Filter, Download, Upload, Edit, Eye } from 'lucide-react';

// Components
import { Layout } from '../components/layout';
import {
  Card, CardHeader, StatCard,
  Button, Input, Select, Textarea,
  Table, Pagination, createCheckboxColumn,
  Modal, ConfirmModal,
  Badge, StatusBadge,
  EmptyState, Skeleton, TableSkeleton,
  useToast, InfoTooltip,
  ProjectSelector, ClientSelector,
} from '../components/common';

// API & Types
import { entityApi } from '../api/endpoints';
import type { Entity, EntityFormData } from '../types';

// Store
import { useFilterStore, useProjectStore } from '../store';

// Utils
import { exportToCSV } from '../utils';
```

### Standard Query Pattern

```tsx
const { data, isLoading, error } = useQuery({
  queryKey: ['entities', page, filters, projectId],
  queryFn: () => entityApi.getAll({ page, ...filters, project_id: projectId }),
  enabled: !!projectId,
});
```

### Standard Mutation Pattern

```tsx
const mutation = useMutation({
  mutationFn: entityApi.create,
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['entities'] });
    setModalOpen(false);
    toast.success('Created successfully');
  },
  onError: (error) => {
    toast.error(error.message || 'Failed to create');
  },
});
```

---

*This document serves as the complete reference for Peanut-style WordPress plugin development.*

import api from './client';
import type {
  UTM,
  UTMFormData,
  Link,
  LinkFormData,
  LinkClick,
  Contact,
  ContactFormData,
  ContactActivity,
  Popup,
  PopupFormData,
  MonitorSite,
  MonitorHealth,
  Webhook,
  WebhookStats,
  WebhookFilters,
  Visitor,
  VisitorEvent,
  VisitorStats,
  Conversion,
  Touch,
  AttributionModel,
  AttributionStats,
  AttributionReport,
  ChannelPerformance,
  AnalyticsOverview,
  AnalyticsRealtime,
  AnalyticsTimeline,
  AnalyticsSources,
  AnalyticsDevices,
  AnalyticsFunnel,
  AnalyticsComparison,
  DashboardStats,
  TimelineData,
  ActivityItem,
  Settings,
  Module,
  License,
  Tag,
  PaginatedResponse,
  Account,
  AccountStats,
  AccountMember,
  ApiKey,
  ApiKeyWithSecret,
  ApiKeyFormData,
  ApiKeyStats,
  AuditLogEntry,
  AuditLogFilters,
  MemberRole,
} from '../types';

// Pagination params
interface PaginationParams {
  page?: number;
  per_page?: number;
  search?: string;
  order_by?: string;
  order?: 'ASC' | 'DESC';
}

// ============================================
// UTM Endpoints
// ============================================
export const utmApi = {
  getAll: async (params?: PaginationParams & {
    utm_source?: string;
    utm_medium?: string;
    utm_campaign?: string;
    program?: string;
    tag?: string;
    is_archived?: boolean;
  }) => {
    const { data } = await api.get<PaginatedResponse<UTM>>('/utms', { params });
    return data;
  },

  getById: async (id: number) => {
    const { data } = await api.get<UTM>(`/utms/${id}`);
    return data;
  },

  create: async (utm: UTMFormData) => {
    const { data } = await api.post<{ id: number; utm: UTM }>('/utms', utm);
    return data;
  },

  update: async (id: number, utm: Partial<UTMFormData>) => {
    const { data } = await api.put<{ utm: UTM }>(`/utms/${id}`, utm);
    return data;
  },

  delete: async (id: number) => {
    await api.delete(`/utms/${id}`);
  },

  bulkDelete: async (ids: number[]) => {
    await api.post('/utms/bulk-delete', { ids });
  },

  export: async () => {
    const { data } = await api.get('/utms/export', { responseType: 'blob' });
    return data;
  },
};

// ============================================
// Links Endpoints
// ============================================
export const linksApi = {
  getAll: async (params?: PaginationParams & {
    is_active?: boolean;
    status?: string;
  }) => {
    const { data } = await api.get<PaginatedResponse<Link>>('/links', { params });
    return data;
  },

  getById: async (id: number) => {
    const { data } = await api.get<Link>(`/links/${id}`);
    return data;
  },

  create: async (link: LinkFormData) => {
    const { data } = await api.post<{ id: number; link: Link }>('/links', link);
    return data;
  },

  update: async (id: number, link: Partial<LinkFormData>) => {
    const { data } = await api.put<{ link: Link }>(`/links/${id}`, link);
    return data;
  },

  delete: async (id: number) => {
    await api.delete(`/links/${id}`);
  },

  getClicks: async (id: number, params?: { days?: number }) => {
    const { data } = await api.get<{ clicks: LinkClick[]; stats: Record<string, unknown> }>(
      `/links/${id}/clicks`,
      { params }
    );
    return data;
  },

  createFromUtm: async (utmId: number, slug?: string) => {
    const { data } = await api.post<{ id: number; link: Link }>('/links/from-utm', {
      utm_id: utmId,
      slug,
    });
    return data;
  },

  generateQR: async (id: number) => {
    const { data } = await api.get<{ qr_url: string }>(`/links/${id}/qr`);
    return data;
  },
};

// ============================================
// Contacts Endpoints
// ============================================
export const contactsApi = {
  getAll: async (params?: PaginationParams & {
    status?: string;
    source?: string;
    tag?: string;
  }) => {
    const { data } = await api.get<PaginatedResponse<Contact>>('/contacts', { params });
    return data;
  },

  getById: async (id: number) => {
    const { data } = await api.get<Contact>(`/contacts/${id}`);
    return data;
  },

  create: async (contact: ContactFormData) => {
    const { data } = await api.post<{ id: number; contact: Contact }>('/contacts', contact);
    return data;
  },

  update: async (id: number, contact: Partial<ContactFormData>) => {
    const { data } = await api.put<{ contact: Contact }>(`/contacts/${id}`, contact);
    return data;
  },

  delete: async (id: number) => {
    await api.delete(`/contacts/${id}`);
  },

  getActivities: async (id: number) => {
    const { data } = await api.get<ContactActivity[]>(`/contacts/${id}/activities`);
    return data;
  },

  addActivity: async (id: number, activity: { type: string; description: string }) => {
    const { data } = await api.post<ContactActivity>(`/contacts/${id}/activities`, activity);
    return data;
  },

  export: async () => {
    const { data } = await api.get('/contacts/export', { responseType: 'blob' });
    return data;
  },

  bulkDelete: async (ids: number[]) => {
    await api.post('/contacts/bulk-delete', { ids });
  },

  bulkUpdateStatus: async (ids: number[], status: string) => {
    await api.post('/contacts/bulk-status', { ids, status });
  },
};

// ============================================
// Popups Endpoints
// ============================================
export const popupsApi = {
  getAll: async (params?: PaginationParams & {
    status?: string;
    type?: string;
  }) => {
    const { data } = await api.get<PaginatedResponse<Popup>>('/popups', { params });
    return data;
  },

  getById: async (id: number) => {
    const { data } = await api.get<Popup>(`/popups/${id}`);
    return data;
  },

  create: async (popup: PopupFormData) => {
    const { data } = await api.post<{ id: number; popup: Popup }>('/popups', popup);
    return data;
  },

  update: async (id: number, popup: Partial<PopupFormData>) => {
    const { data } = await api.put<{ popup: Popup }>(`/popups/${id}`, popup);
    return data;
  },

  delete: async (id: number) => {
    await api.delete(`/popups/${id}`);
  },

  duplicate: async (id: number) => {
    const { data } = await api.post<{ id: number; popup: Popup }>(`/popups/${id}/duplicate`);
    return data;
  },

  getStats: async (id: number, days?: number) => {
    const { data } = await api.get<{
      daily: { date: string; views: number; conversions: number; dismissals: number }[];
      totals: { views: number; conversions: number; dismissals: number; conversion_rate: number };
    }>(`/popups/${id}/stats`, { params: { days } });
    return data;
  },

  bulkAction: async (action: 'delete' | 'activate' | 'pause' | 'archive', ids: number[]) => {
    await api.post('/popups/bulk', { action, ids });
  },

  getDefaults: async () => {
    const { data } = await api.get<PopupFormData>('/popups/defaults');
    return data;
  },

  getTriggers: async () => {
    const { data } = await api.get<{
      triggers: Record<string, string>;
      positions: Record<string, string[]>;
    }>('/popups/triggers');
    return data;
  },
};

// ============================================
// Dashboard Endpoints
// ============================================
export const dashboardApi = {
  getStats: async (period?: '7d' | '30d' | '90d' | 'year') => {
    const { data } = await api.get<DashboardStats>('/dashboard', { params: { period } });
    return data;
  },

  getTimeline: async (period?: '7d' | '30d' | '90d') => {
    const { data } = await api.get<TimelineData[]>('/dashboard/timeline', { params: { period } });
    return data;
  },

  getActivity: async (limit?: number) => {
    const { data } = await api.get<ActivityItem[]>('/dashboard/activity', { params: { limit } });
    return data;
  },
};

// ============================================
// Settings Endpoints
// ============================================
export const settingsApi = {
  get: async () => {
    const { data } = await api.get<Settings>('/settings');
    return data;
  },

  update: async (settings: Partial<Settings>) => {
    const { data } = await api.post<Settings>('/settings', settings);
    return data;
  },

  getModules: async () => {
    const { data } = await api.get<Module[]>('/modules');
    return data;
  },

  activateModule: async (id: string) => {
    await api.post(`/modules/${id}/activate`);
  },

  deactivateModule: async (id: string) => {
    await api.post(`/modules/${id}/deactivate`);
  },

  getLicense: async () => {
    const { data } = await api.get<License>('/license');
    return data;
  },

  activateLicense: async (key: string) => {
    const { data } = await api.post<License>('/license/activate', { key });
    return data;
  },

  deactivateLicense: async () => {
    await api.delete('/license/deactivate');
  },
};

// ============================================
// Tags Endpoints
// ============================================
export const tagsApi = {
  list: async () => {
    const { data } = await api.get<Tag[]>('/tags');
    return data;
  },

  create: async (tag: { name: string; color?: string }) => {
    const { data } = await api.post<Tag>('/tags', tag);
    return data;
  },

  delete: async (id: number) => {
    await api.delete(`/tags/${id}`);
  },
};

// ============================================
// Monitor Endpoints (Agency)
// ============================================
export const monitorApi = {
  getSites: async (params?: PaginationParams) => {
    const { data } = await api.get<PaginatedResponse<MonitorSite>>('/monitor/sites', { params });
    return data;
  },

  getSite: async (id: number) => {
    const { data } = await api.get<MonitorSite>(`/monitor/sites/${id}`);
    return data;
  },

  addSite: async (site: { url: string; name?: string; site_key?: string }) => {
    const { data } = await api.post<{ id: number; site: MonitorSite }>('/monitor/sites', site);
    return data;
  },

  removeSite: async (id: number) => {
    await api.delete(`/monitor/sites/${id}`);
  },

  refreshSite: async (id: number) => {
    const { data } = await api.post<MonitorSite>(`/monitor/sites/${id}/refresh`);
    return data;
  },

  getSiteHealth: async (id: number) => {
    const { data } = await api.get<MonitorHealth>(`/monitor/sites/${id}/health`);
    return data;
  },

  getSiteUptime: async (id: number, days?: number) => {
    const { data } = await api.get<{
      daily: { date: string; uptime: number; response_time: number }[];
      incidents: number;
      avg_response: number;
    }>(`/monitor/sites/${id}/uptime`, { params: { days } });
    return data;
  },

  runUpdates: async (id: number, type: string, items: string[]) => {
    const { data } = await api.post<{ success: boolean; updated: string[] }>(
      `/monitor/sites/${id}/updates`,
      { type, items }
    );
    return data;
  },

  getSiteAnalytics: async (id: number) => {
    const { data } = await api.get(`/monitor/sites/${id}/analytics`);
    return data;
  },
};

// ============================================
// Webhooks Endpoints
// ============================================
export const webhooksApi = {
  getAll: async (params?: PaginationParams & {
    source?: string;
    event?: string;
    status?: string;
  }) => {
    const { data } = await api.get<PaginatedResponse<Webhook>>('/webhooks', { params });
    return data;
  },

  getById: async (id: number) => {
    const { data } = await api.get<Webhook>(`/webhooks/${id}`);
    return data;
  },

  reprocess: async (id: number) => {
    const { data } = await api.post<{ message: string; webhook: Webhook }>(
      `/webhooks/${id}/reprocess`
    );
    return data;
  },

  getStats: async () => {
    const { data } = await api.get<WebhookStats>('/webhooks/stats');
    return data;
  },

  getFilters: async () => {
    const { data } = await api.get<WebhookFilters>('/webhooks/filters');
    return data;
  },

  bulkDelete: async (ids: number[]) => {
    const { data } = await api.post<{ message: string; deleted: number }>(
      '/webhooks/bulk-delete',
      { ids }
    );
    return data;
  },
};

// ============================================
// Visitors Endpoints
// ============================================
export const visitorsApi = {
  getAll: async (params?: PaginationParams & {
    identified_only?: boolean;
  }) => {
    const { data } = await api.get<PaginatedResponse<Visitor>>('/visitors', { params });
    return data;
  },

  getById: async (id: number) => {
    const { data } = await api.get<Visitor>(`/visitors/${id}`);
    return data;
  },

  delete: async (id: number) => {
    await api.delete(`/visitors/${id}`);
  },

  getEvents: async (id: number, params?: { limit?: number; event_type?: string }) => {
    const { data } = await api.get<{ visitor_id: string; events: VisitorEvent[]; total: number }>(
      `/visitors/${id}/events`,
      { params }
    );
    return data;
  },

  getStats: async () => {
    const { data } = await api.get<VisitorStats>('/visitors/stats');
    return data;
  },

  getSnippet: async () => {
    const { data } = await api.get<{
      snippet: string;
      script_url: string;
      api_url: string;
      site_id: string;
    }>('/visitors/snippet');
    return data;
  },
};

// ============================================
// Attribution Endpoints
// ============================================
export const attributionApi = {
  getConversions: async (params?: PaginationParams & {
    conversion_type?: string;
    date_from?: string;
    date_to?: string;
  }) => {
    const { data } = await api.get<PaginatedResponse<Conversion>>('/attribution/conversions', { params });
    return data;
  },

  getConversion: async (id: number, model?: string) => {
    const { data } = await api.get<Conversion>(`/attribution/conversions/${id}`, {
      params: model ? { model } : undefined,
    });
    return data;
  },

  getTouches: async (params: { visitor_id?: string; conversion_id?: number }) => {
    const { data } = await api.get<{ visitor_id: string; touches: Touch[]; total: number }>(
      '/attribution/touches',
      { params }
    );
    return data;
  },

  getReport: async (params?: {
    model?: string;
    date_from?: string;
    date_to?: string;
  }) => {
    const { data } = await api.get<AttributionReport>('/attribution/report', { params });
    return data;
  },

  compareModels: async (params?: {
    date_from?: string;
    date_to?: string;
  }) => {
    const { data } = await api.get<{
      date_range: { from: string; to: string };
      models: Record<string, string>;
      comparison: Record<string, ChannelPerformance[]>;
    }>('/attribution/compare', { params });
    return data;
  },

  getChannels: async (params?: {
    model?: string;
    date_from?: string;
    date_to?: string;
  }) => {
    const { data } = await api.get<{ model: string; channels: ChannelPerformance[] }>(
      '/attribution/channels',
      { params }
    );
    return data;
  },

  getStats: async () => {
    const { data } = await api.get<AttributionStats>('/attribution/stats');
    return data;
  },

  getModels: async () => {
    const { data } = await api.get<AttributionModel[]>('/attribution/models');
    return data;
  },
};

// ============================================
// Analytics Endpoints
// ============================================
export const analyticsApi = {
  getOverview: async (period?: '7d' | '30d' | '90d' | 'year') => {
    const { data } = await api.get<AnalyticsOverview>('/analytics/overview', {
      params: period ? { period } : undefined,
    });
    return data;
  },

  getRealtime: async () => {
    const { data } = await api.get<AnalyticsRealtime>('/analytics/realtime');
    return data;
  },

  getTimeline: async (period?: '7d' | '30d' | '90d' | 'year') => {
    const { data } = await api.get<AnalyticsTimeline>('/analytics/timeline', {
      params: period ? { period } : undefined,
    });
    return data;
  },

  getSources: async (period?: '7d' | '30d' | '90d' | 'year') => {
    const { data } = await api.get<AnalyticsSources>('/analytics/sources', {
      params: period ? { period } : undefined,
    });
    return data;
  },

  getDevices: async (period?: '7d' | '30d' | '90d' | 'year') => {
    const { data } = await api.get<AnalyticsDevices>('/analytics/devices', {
      params: period ? { period } : undefined,
    });
    return data;
  },

  getFunnel: async (period?: '7d' | '30d' | '90d' | 'year') => {
    const { data } = await api.get<AnalyticsFunnel>('/analytics/funnel', {
      params: period ? { period } : undefined,
    });
    return data;
  },

  compare: async (period?: '7d' | '30d' | '90d' | 'year') => {
    const { data } = await api.get<AnalyticsComparison>('/analytics/compare', {
      params: period ? { period } : undefined,
    });
    return data;
  },

  triggerAggregation: async (date?: string) => {
    const { data } = await api.post<{ success: boolean; date: string; result: unknown }>(
      '/analytics/aggregate',
      date ? { date } : undefined
    );
    return data;
  },
};

// ============================================
// Accounts Endpoints (Multi-tenancy)
// ============================================
export const accountsApi = {
  // Get current account context
  getCurrent: async () => {
    const { data } = await api.get<{
      account: Account;
      role: MemberRole;
      stats: AccountStats;
    }>('/accounts/current');
    return data;
  },

  // Get all accounts for current user
  getAll: async () => {
    const { data } = await api.get<{ accounts: Account[] }>('/accounts');
    return data;
  },

  // Switch to a different account
  switch: async (accountId: number) => {
    const { data } = await api.post<{
      message: string;
      account: Account;
    }>('/accounts/switch', { account_id: accountId });
    return data;
  },

  // Get account by ID
  getById: async (id: number) => {
    const { data } = await api.get<{
      account: Account;
      stats: AccountStats;
    }>(`/accounts/${id}`);
    return data;
  },

  // Update account
  update: async (id: number, data: { name?: string; settings?: Record<string, unknown> }) => {
    const { data: result } = await api.put<{
      message: string;
      account: Account;
    }>(`/accounts/${id}`, data);
    return result;
  },

  // Get account stats
  getStats: async (id: number) => {
    const { data } = await api.get<{ stats: AccountStats }>(`/accounts/${id}/stats`);
    return data;
  },

  // ========== Members ==========

  // Get all members
  getMembers: async (accountId: number) => {
    const { data } = await api.get<{ members: AccountMember[] }>(
      `/accounts/${accountId}/members`
    );
    return data;
  },

  // Add member
  addMember: async (accountId: number, userId: number, role: MemberRole = 'member') => {
    const { data } = await api.post<{
      message: string;
      member: AccountMember;
    }>(`/accounts/${accountId}/members`, { user_id: userId, role });
    return data;
  },

  // Update member role
  updateMember: async (accountId: number, userId: number, role: MemberRole) => {
    const { data } = await api.put<{
      message: string;
      member: AccountMember;
    }>(`/accounts/${accountId}/members/${userId}`, { role });
    return data;
  },

  // Remove member
  removeMember: async (accountId: number, userId: number) => {
    const { data } = await api.delete<{ message: string }>(
      `/accounts/${accountId}/members/${userId}`
    );
    return data;
  },

  // Transfer ownership
  transferOwnership: async (accountId: number, newOwnerId: number) => {
    const { data } = await api.post<{ message: string }>(
      `/accounts/${accountId}/transfer`,
      { new_owner_id: newOwnerId }
    );
    return data;
  },

  // ========== API Keys ==========

  // Get all API keys
  getApiKeys: async (accountId: number, includeRevoked = false) => {
    const { data } = await api.get<{
      api_keys: ApiKey[];
      stats: ApiKeyStats;
    }>(`/accounts/${accountId}/api-keys`, {
      params: { include_revoked: includeRevoked },
    });
    return data;
  },

  // Create API key
  createApiKey: async (accountId: number, keyData: ApiKeyFormData) => {
    const { data } = await api.post<{
      message: string;
      api_key: ApiKeyWithSecret;
    }>(`/accounts/${accountId}/api-keys`, keyData);
    return data;
  },

  // Update API key
  updateApiKey: async (
    accountId: number,
    keyId: number,
    updates: Partial<ApiKeyFormData>
  ) => {
    const { data } = await api.put<{
      message: string;
      api_key: ApiKey;
    }>(`/accounts/${accountId}/api-keys/${keyId}`, updates);
    return data;
  },

  // Revoke API key
  revokeApiKey: async (accountId: number, keyId: number) => {
    const { data } = await api.delete<{ message: string }>(
      `/accounts/${accountId}/api-keys/${keyId}`
    );
    return data;
  },

  // Regenerate API key
  regenerateApiKey: async (accountId: number, keyId: number) => {
    const { data } = await api.post<{
      message: string;
      api_key: ApiKeyWithSecret;
    }>(`/accounts/${accountId}/api-keys/${keyId}/regenerate`);
    return data;
  },

  // ========== Audit Log ==========

  // Get audit log
  getAuditLog: async (accountId: number, params?: PaginationParams & AuditLogFilters) => {
    const { data } = await api.get<PaginatedResponse<AuditLogEntry>>(
      `/accounts/${accountId}/audit-log`,
      { params }
    );
    return data;
  },

  // Export audit log
  exportAuditLog: async (
    accountId: number,
    format: 'csv' | 'json' = 'csv',
    dateFrom?: string,
    dateTo?: string
  ) => {
    const { data } = await api.get<{
      filename: string;
      content: string;
      mime_type: string;
    }>(`/accounts/${accountId}/audit-log/export`, {
      params: { format, date_from: dateFrom, date_to: dateTo },
    });
    return data;
  },
};

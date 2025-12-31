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
  AccountMember,
  AccountMemberFormData,
  AccountRole,
  FeaturePermissions,
  // Plesk Server types
  PleskServer,
  ServerHealth,
  ServerHealthHistory,
  PleskDomain,
  PleskService,
  ServerFormData,
  ServersOverview,
  // Health Report types
  HealthReportSettings,
  HealthReportSettingsFormData,
  HealthReport,
  HealthReportPreview,
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
// Security Endpoints
// ============================================
export const securityApi = {
  getSettings: async () => {
    const { data } = await api.get<{
      hide_login_enabled: boolean;
      login_slug: string;
      redirect_slug: string;
      limit_login_enabled: boolean;
      max_attempts: number;
      lockout_duration: number;
      lockout_increment: boolean;
      ip_whitelist: string[];
      ip_blacklist: string[];
      notify_login_success: boolean;
      notify_login_failed: boolean;
      notify_lockout: boolean;
      notify_email: string;
      '2fa_enabled': boolean;
      '2fa_method': 'email' | 'totp';
      '2fa_roles': string[];
    }>('/security/settings');
    return data;
  },

  updateSettings: async (settings: Record<string, unknown>) => {
    const { data } = await api.post<{ success: boolean; settings: Record<string, unknown> }>(
      '/security/settings',
      settings
    );
    return data;
  },

  getLoginAttempts: async () => {
    const { data } = await api.get<Array<{
      id: number;
      ip_address: string;
      username: string;
      attempt_time: string;
      status: 'success' | 'failed';
    }>>('/security/attempts');
    return data;
  },

  getLockouts: async () => {
    const { data } = await api.get<Array<{
      id: number;
      ip_address: string;
      lockout_until: string;
      attempts: number;
      created_at: string;
    }>>('/security/lockouts');
    return data;
  },

  unlockIp: async (ip: string) => {
    const { data } = await api.delete<{ success: boolean }>(`/security/unlock/${encodeURIComponent(ip)}`);
    return data;
  },
};

// ============================================
// Backlinks Endpoints
// ============================================
export const backlinksApi = {
  getAll: async (params?: PaginationParams & {
    status?: 'active' | 'lost' | 'broken' | 'pending';
    domain?: string;
  }) => {
    const { data } = await api.get<{
      backlinks: Array<{
        id: number;
        source_url: string;
        source_domain: string;
        target_url: string;
        anchor_text: string;
        link_type: 'dofollow' | 'nofollow' | 'ugc' | 'sponsored';
        status: 'active' | 'lost' | 'broken' | 'pending';
        first_seen: string;
        last_checked: string;
        domain_authority: number | null;
      }>;
      total: number;
      page: number;
      per_page: number;
      total_pages: number;
      stats: {
        total: number;
        active: number;
        lost: number;
        broken: number;
        dofollow: number;
        nofollow: number;
        unique_domains: number;
        new_30_days: number;
        lost_7_days: number;
      };
    }>('/backlinks', { params });
    return data;
  },

  triggerDiscovery: async () => {
    const { data } = await api.post<{ success: boolean; discovered: number; message: string }>(
      '/backlinks/discover'
    );
    return data;
  },

  triggerVerify: async () => {
    const { data } = await api.post<{ success: boolean; verified: number; lost: number; message: string }>(
      '/backlinks/verify'
    );
    return data;
  },

  delete: async (id: number) => {
    const { data } = await api.delete<{ success: boolean }>(`/backlinks/${id}`);
    return data;
  },

  getSettings: async () => {
    const { data } = await api.get<{
      alert_on_lost: boolean;
      alert_email: string;
      auto_verify_days: number;
      target_domains: string[];
    }>('/backlinks/settings');
    return data;
  },

  updateSettings: async (settings: Record<string, unknown>) => {
    const { data } = await api.post<{ success: boolean; settings: Record<string, unknown> }>(
      '/backlinks/settings',
      settings
    );
    return data;
  },
};

// ============================================
// SEO/Keywords Endpoints
// ============================================
export const seoApi = {
  getKeywords: async () => {
    const { data } = await api.get<{
      keywords: Array<{
        id: number;
        keyword: string;
        target_url: string;
        search_engine: string;
        location: string;
        current_position: number | null;
        previous_position: number | null;
        change: number;
        last_checked: string;
        created_at: string;
      }>;
    }>('/seo/keywords');
    return data;
  },

  addKeyword: async (data: { keyword: string; target_url?: string; search_engine?: string; location?: string }) => {
    const { data: response } = await api.post<{ id: number; message: string }>('/seo/keywords', data);
    return response;
  },

  deleteKeyword: async (id: number) => {
    const { data } = await api.delete<{ message: string }>(`/seo/keywords/${id}`);
    return data;
  },

  getKeywordHistory: async (id: number, days?: number) => {
    const { data } = await api.get<{
      history: Array<{
        position: number | null;
        ranking_url: string;
        checked_at: string;
      }>;
    }>(`/seo/keywords/${id}/history`, { params: { days } });
    return data;
  },

  checkRankings: async () => {
    const { data } = await api.post<{ checked: number }>('/seo/keywords/check');
    return data;
  },

  runAudit: async (url?: string) => {
    const { data } = await api.post<{
      success: boolean;
      url: string;
      score: number;
      grade: string;
      issues: Array<{
        category: string;
        severity: 'critical' | 'warning' | 'info' | 'passed';
        title: string;
        description: string;
        recommendation?: string;
      }>;
      summary: { critical: number; warning: number; info: number; passed: number };
    }>('/seo/audit', { url });
    return data;
  },

  getAuditResults: async () => {
    const { data } = await api.get<{
      url: string;
      results: Record<string, unknown>;
      timestamp: number;
    } | { error: string }>('/seo/audit/results');
    return data;
  },
};

// ============================================
// Sequences Endpoints
// ============================================
export const sequencesApi = {
  getAll: async () => {
    const { data } = await api.get<{
      sequences: Array<{
        id: number;
        name: string;
        description: string;
        trigger_type: string;
        trigger_value: string;
        status: 'draft' | 'active' | 'paused';
        active_subscribers: number;
        completed_subscribers: number;
        created_at: string;
      }>;
    }>('/sequences');
    return data;
  },

  getById: async (id: number) => {
    const { data } = await api.get<{
      id: number;
      name: string;
      description: string;
      trigger_type: string;
      trigger_value: string;
      status: string;
      emails: Array<{
        id: number;
        sequence_id: number;
        subject: string;
        body: string;
        delay_days: number;
        delay_hours: number;
        status: string;
        created_at: string;
      }>;
      stats: { active: number; completed: number; paused: number };
    }>(`/sequences/${id}`);
    return data;
  },

  create: async (data: { name: string; description?: string; trigger_type?: string; trigger_value?: string }) => {
    const { data: response } = await api.post<{ id: number }>('/sequences', data);
    return response;
  },

  update: async (id: number, data: Record<string, unknown>) => {
    const { data: response } = await api.patch<{ message: string }>(`/sequences/${id}`, data);
    return response;
  },

  delete: async (id: number) => {
    const { data } = await api.delete<{ message: string }>(`/sequences/${id}`);
    return data;
  },

  addEmail: async (sequenceId: number, email: { subject: string; body: string; delay_days: number; delay_hours: number }) => {
    const { data } = await api.post<{ id: number }>(`/sequences/${sequenceId}/emails`, email);
    return data;
  },

  updateEmail: async (emailId: number, email: Record<string, unknown>) => {
    const { data } = await api.patch<{ message: string }>(`/sequences/emails/${emailId}`, email);
    return data;
  },

  deleteEmail: async (emailId: number) => {
    const { data } = await api.delete<{ message: string }>(`/sequences/emails/${emailId}`);
    return data;
  },

  getSubscribers: async (sequenceId: number) => {
    const { data } = await api.get<{
      subscribers: Array<{
        id: number;
        sequence_id: number;
        contact_id: number;
        email: string;
        current_email_id: number | null;
        next_send_at: string | null;
        emails_sent: number;
        status: 'active' | 'completed' | 'paused';
        enrolled_at: string;
      }>;
    }>(`/sequences/${sequenceId}/subscribers`);
    return data;
  },

  enrollContact: async (sequenceId: number, data: { contact_id?: number; email?: string }) => {
    const { data: response } = await api.post<{ message: string }>(`/sequences/${sequenceId}/enroll`, data);
    return response;
  },
};

// ============================================
// WooCommerce Endpoints
// ============================================
export const woocommerceApi = {
  getRevenueStats: async (days?: number) => {
    const { data } = await api.get<{
      total_revenue: number;
      by_source: Array<{ source: string; orders: number; revenue: number }>;
      by_campaign: Array<{ campaign: string; source: string; orders: number; revenue: number }>;
      daily: Array<{ date: string; revenue: number; orders: number }>;
      attribution_rate: number;
      total_orders: number;
      attributed_orders: number;
    }>('/woocommerce/revenue', { params: { days } });
    return data;
  },

  getAttributionReport: async (params?: { days?: number; group_by?: 'source' | 'medium' | 'campaign' }) => {
    const { data } = await api.get<{
      report: Array<{
        channel: string;
        orders: number;
        customers: number;
        revenue: number;
        avg_order_value: number;
        first_order: string;
        last_order: string;
      }>;
      group_by: string;
      period_days: number;
    }>('/woocommerce/attribution', { params });
    return data;
  },

  getAttributedOrders: async (params?: PaginationParams & { source?: string; campaign?: string }) => {
    const { data } = await api.get<{
      orders: Array<{
        id: number;
        order_id: number;
        customer_email: string;
        order_total: number;
        utm_source: string;
        utm_medium: string;
        utm_campaign: string;
        created_at: string;
      }>;
      total: number;
      page: number;
      per_page: number;
      total_pages: number;
    }>('/woocommerce/orders', { params });
    return data;
  },
};

// ============================================
// White-Label Endpoints
// ============================================
export const whitelabelApi = {
  getSettings: async () => {
    const { data } = await api.get<{
      enabled: boolean;
      company_name: string;
      logo_url: string;
      logo_width: number;
      primary_color: string;
      secondary_color: string;
      accent_color: string;
      email_footer: string;
      report_footer: string;
      hide_peanut_branding: boolean;
      custom_css: string;
    }>('/whitelabel/settings');
    return data;
  },

  updateSettings: async (settings: Record<string, unknown>) => {
    const { data } = await api.post<{ message: string; settings: Record<string, unknown> }>(
      '/whitelabel/settings',
      settings
    );
    return data;
  },

  uploadLogo: async (file: File) => {
    const formData = new FormData();
    formData.append('logo', file);
    const { data } = await api.post<{ url: string; message: string }>(
      '/whitelabel/logo',
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    );
    return data;
  },
};

// ============================================
// Reports Endpoints
// ============================================
export const reportsApi = {
  getSettings: async () => {
    const { data } = await api.get<{
      settings: {
        enabled: boolean;
        frequency: 'daily' | 'weekly' | 'monthly';
        day_of_week: number;
        day_of_month: number;
        time: string;
        recipients: string[];
        include_sections: Record<string, boolean>;
        attach_pdf: boolean;
        custom_logo: string;
        custom_footer: string;
      };
      next_scheduled: string | null;
      log: Array<{
        sent_at: string;
        frequency: string;
        period: string;
        recipients: number;
      }>;
    }>('/reports/settings');
    return data;
  },

  updateSettings: async (settings: Record<string, unknown>) => {
    const { data } = await api.post<{ success: boolean; settings: Record<string, unknown> }>(
      '/reports/settings',
      settings
    );
    return data;
  },

  preview: async () => {
    const { data } = await api.get<{ data: Record<string, unknown>; html: string }>('/reports/preview');
    return data;
  },

  sendNow: async () => {
    const { data } = await api.post<{ success: boolean; message: string }>('/reports/send-now');
    return data;
  },
};

// ============================================
// Performance / Core Web Vitals Endpoints
// ============================================
export const performanceApi = {
  getSettings: async () => {
    const { data } = await api.get<{
      api_key: string;
      api_key_set: boolean;
      strategy: 'mobile' | 'desktop';
      auto_check_enabled: boolean;
      check_frequency: 'daily' | 'weekly';
      urls: string[];
      alert_enabled: boolean;
      alert_threshold: number;
      alert_email: string;
    }>('/performance/settings');
    return data;
  },

  updateSettings: async (settings: Record<string, unknown>) => {
    const { data } = await api.post<{ success: boolean; settings: Record<string, unknown> }>(
      '/performance/settings',
      settings
    );
    return data;
  },

  getScores: async (strategy?: 'mobile' | 'desktop') => {
    const { data } = await api.get<{
      scores: Array<{
        id: number;
        url: string;
        strategy: 'mobile' | 'desktop';
        overall_score: number;
        performance_score: number;
        accessibility_score: number;
        best_practices_score: number;
        seo_score: number;
        lcp_ms: number;
        fid_ms: number;
        inp_ms: number;
        cls: number;
        fcp_ms: number;
        ttfb_ms: number;
        tti_ms: number;
        tbt_ms: number;
        speed_index: number;
        opportunities: Array<{
          id: string;
          title: string;
          description: string;
          savings_ms: number;
          savings_bytes: number;
        }>;
        diagnostics: Array<{
          id: string;
          title: string;
          description: string;
          score: number | null;
        }>;
        checked_at: string;
      }>;
      averages: {
        overall: number;
        performance: number;
        accessibility: number;
        best_practices: number;
        seo: number;
        lcp: number;
        cls: number;
        fid: number;
      };
      strategy: 'mobile' | 'desktop';
    }>('/performance/scores', { params: { strategy } });
    return data;
  },

  getHistory: async (params?: { url?: string; days?: number; strategy?: 'mobile' | 'desktop' }) => {
    const { data } = await api.get<{
      history: Array<{
        date: string;
        overall_score: number;
        performance_score: number;
        lcp_ms: number;
        cls: number;
        fid_ms: number;
      }>;
      url: string;
      days: number;
    }>('/performance/history', { params });
    return data;
  },

  runCheck: async (url?: string, strategy?: 'mobile' | 'desktop') => {
    const { data } = await api.post<{
      success: boolean;
      score?: {
        url: string;
        overall_score: number;
        performance_score: number;
        accessibility_score: number;
        best_practices_score: number;
        seo_score: number;
        lcp_ms: number;
        cls: number;
        fid_ms: number;
        opportunities: Array<{ id: string; title: string; savings_ms: number }>;
        diagnostics: Array<{ id: string; title: string; score: number | null }>;
      };
      error?: string;
    }>('/performance/check', { url, strategy });
    return data;
  },

  getUrls: async () => {
    const { data } = await api.get<{ urls: string[] }>('/performance/urls');
    return data;
  },

  addUrl: async (url: string) => {
    const { data } = await api.post<{ success: boolean; urls: string[] }>('/performance/urls', { url });
    return data;
  },

  deleteUrl: async (id: number) => {
    const { data } = await api.delete<{ success: boolean; urls: string[] }>(`/performance/urls/${id}`);
    return data;
  },
};

// ============================================
// Auth Endpoints
// ============================================
export const authApi = {
  getCurrentUser: async () => {
    const { data } = await api.get<{
      success: boolean;
      user: {
        id: number;
        name: string;
        email: string;
        avatar: string;
      };
      account: {
        id: number;
        name: string;
        slug: string;
        tier: 'free' | 'pro' | 'agency';
        role: 'owner' | 'admin' | 'member' | 'viewer';
        permissions: Record<string, { access: boolean }> | null;
        available_features: Record<string, { name: string; tier: string; available: boolean }>;
      } | null;
    }>('/auth/me');
    return data;
  },

  logout: async () => {
    const { data } = await api.post<{ success: boolean; redirect_url: string }>('/auth/logout');
    return data;
  },
};

// ============================================
// Accounts & Team Endpoints
// ============================================
export const accountsApi = {
  getAll: async () => {
    const { data } = await api.get<{ success: boolean; data: Account[] }>('/accounts');
    return data.data;
  },

  getById: async (id: number) => {
    const { data } = await api.get<{ success: boolean; data: Account }>(`/accounts/${id}`);
    return data.data;
  },

  update: async (id: number, accountData: { name?: string; settings?: Record<string, unknown> }) => {
    const { data } = await api.put<{ success: boolean; data: Account }>(`/accounts/${id}`, accountData);
    return data.data;
  },

  getStats: async (id: number) => {
    const { data } = await api.get<{
      success: boolean;
      data: {
        members: number;
        utms: number;
        links: number;
        contacts: number;
      };
    }>(`/accounts/${id}/stats`);
    return data.data;
  },

  // Team Members
  getMembers: async (accountId: number) => {
    const { data } = await api.get<{ success: boolean; data: AccountMember[] }>(
      `/accounts/${accountId}/members`
    );
    return data.data;
  },

  addMember: async (accountId: number, member: AccountMemberFormData) => {
    const { data } = await api.post<{ success: boolean; data: AccountMember }>(
      `/accounts/${accountId}/members`,
      member
    );
    return data.data;
  },

  updateMemberRole: async (accountId: number, userId: number, role: AccountRole) => {
    const { data } = await api.put<{ success: boolean; data: AccountMember[] }>(
      `/accounts/${accountId}/members/${userId}`,
      { role }
    );
    return data.data;
  },

  updateMemberPermissions: async (
    accountId: number,
    userId: number,
    permissions: FeaturePermissions
  ) => {
    const { data } = await api.put<{ success: boolean; data: AccountMember[] }>(
      `/accounts/${accountId}/members/${userId}`,
      { permissions }
    );
    return data.data;
  },

  removeMember: async (accountId: number, userId: number) => {
    await api.delete(`/accounts/${accountId}/members/${userId}`);
  },

  transferOwnership: async (accountId: number, newOwnerId: number) => {
    const { data } = await api.post<{ success: boolean; message: string }>(
      `/accounts/${accountId}/transfer`,
      { new_owner_id: newOwnerId }
    );
    return data;
  },

  // Available features list
  getAvailableFeatures: async () => {
    const { data } = await api.get<{
      success: boolean;
      data: Array<{
        id: string;
        name: string;
        description: string;
        tier: 'free' | 'pro' | 'agency';
      }>;
    }>('/accounts/features');
    return data.data;
  },

  // Team Login Settings
  getLoginSettings: async (accountId: number) => {
    const { data } = await api.get<{
      success: boolean;
      data: {
        login_page_id: number | null;
        login_page_url: string | null;
        logo_url: string;
        title: string;
        redirect_url: string;
        shortcode: string;
      };
    }>(`/accounts/${accountId}/login-settings`);
    return data.data;
  },

  updateLoginSettings: async (accountId: number, settings: {
    login_page_id?: number | null;
    login_page_url?: string | null;
    logo_url?: string;
    title?: string;
    redirect_url?: string;
  }) => {
    const { data } = await api.put<{
      success: boolean;
      data: {
        login_page_id: number | null;
        login_page_url: string | null;
        logo_url: string;
        title: string;
        redirect_url: string;
        shortcode: string;
      };
    }>(`/accounts/${accountId}/login-settings`, settings);
    return data.data;
  },
};

// ============================================
// Plesk Server Monitoring Endpoints
// ============================================
export const serversApi = {
  // List servers
  getServers: async (params?: {
    page?: number;
    per_page?: number;
    search?: string;
    status?: 'active' | 'disconnected' | 'error';
    orderby?: string;
    order?: 'ASC' | 'DESC';
  }) => {
    const { data } = await api.get<{
      success: boolean;
      data: {
        data: PleskServer[];
        total: number;
        page: number;
        per_page: number;
        total_pages: number;
      };
    }>('/monitor/servers', { params });
    return data.data;
  },

  // Get overview stats
  getOverview: async () => {
    const { data } = await api.get<{
      success: boolean;
      data: ServersOverview;
    }>('/monitor/servers/overview');
    return data.data;
  },

  // Get single server
  getServer: async (id: number) => {
    const { data } = await api.get<{
      success: boolean;
      data: PleskServer;
    }>(`/monitor/servers/${id}`);
    return data.data;
  },

  // Add server
  addServer: async (serverData: ServerFormData) => {
    const { data } = await api.post<{
      success: boolean;
      data: PleskServer;
      message: string;
    }>('/monitor/servers', serverData);
    return data;
  },

  // Update server
  updateServer: async (id: number, serverData: Partial<ServerFormData>) => {
    const { data } = await api.put<{
      success: boolean;
      data: PleskServer;
      message: string;
    }>(`/monitor/servers/${id}`, serverData);
    return data;
  },

  // Delete server
  deleteServer: async (id: number) => {
    const { data } = await api.delete<{
      success: boolean;
      message: string;
    }>(`/monitor/servers/${id}`);
    return data;
  },

  // Force health check
  checkHealth: async (id: number) => {
    const { data } = await api.post<{
      success: boolean;
      data: {
        server: PleskServer;
        health: ServerHealth;
      };
      message: string;
    }>(`/monitor/servers/${id}/check`);
    return data;
  },

  // Get health history
  getHealthHistory: async (id: number, days = 30) => {
    const { data } = await api.get<{
      success: boolean;
      data: {
        server_id: number;
        days: number;
        history: ServerHealthHistory[];
      };
    }>(`/monitor/servers/${id}/health`, { params: { days } });
    return data.data;
  },

  // Get domains
  getDomains: async (id: number) => {
    const { data } = await api.get<{
      success: boolean;
      data: {
        server_id: number;
        domains: PleskDomain[];
      };
    }>(`/monitor/servers/${id}/domains`);
    return data.data;
  },

  // Get services
  getServices: async (id: number) => {
    const { data } = await api.get<{
      success: boolean;
      data: {
        server_id: number;
        services: PleskService[];
      };
    }>(`/monitor/servers/${id}/services`);
    return data.data;
  },
};

// ============================================
// Health Reports Endpoints
// ============================================
export const healthReportsApi = {
  // Get settings
  getSettings: async () => {
    const { data } = await api.get<{
      success: boolean;
      data: HealthReportSettings;
    }>('/health-reports/settings');
    return data.data;
  },

  // Update settings
  updateSettings: async (settings: HealthReportSettingsFormData) => {
    const { data } = await api.post<{
      success: boolean;
      data: HealthReportSettings;
      message: string;
    }>('/health-reports/settings', settings);
    return data;
  },

  // Get latest report
  getLatest: async () => {
    const { data } = await api.get<{
      success: boolean;
      data: HealthReport | null;
    }>('/health-reports/latest');
    return data.data;
  },

  // Get report history
  getHistory: async (params?: { page?: number; per_page?: number }) => {
    const { data } = await api.get<{
      success: boolean;
      data: {
        data: HealthReport[];
        total: number;
        page: number;
        per_page: number;
        total_pages: number;
      };
    }>('/health-reports/history', { params });
    return data.data;
  },

  // Get single report
  getReport: async (id: number) => {
    const { data } = await api.get<{
      success: boolean;
      data: HealthReport;
    }>(`/health-reports/${id}`);
    return data.data;
  },

  // Generate preview (current state)
  preview: async () => {
    const { data } = await api.get<{
      success: boolean;
      data: HealthReportPreview;
    }>('/health-reports/preview');
    return data.data;
  },

  // Generate and save new report
  generate: async () => {
    const { data } = await api.post<{
      success: boolean;
      data: HealthReport;
      message: string;
    }>('/health-reports/generate');
    return data;
  },

  // Send report immediately
  send: async (id?: number) => {
    const { data } = await api.post<{
      success: boolean;
      message: string;
    }>('/health-reports/send', { report_id: id });
    return data;
  },

  // Get available sites and servers for selection
  getAvailableItems: async () => {
    const { data } = await api.get<{
      success: boolean;
      data: {
        sites: Array<{ id: number; name: string; url: string }>;
        servers: Array<{ id: number; name: string; host: string }>;
      };
    }>('/health-reports/available-items');
    return data.data;
  },
};

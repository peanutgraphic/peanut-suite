// API Response types
export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message?: string;
  code?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  total_pages: number;
  page: number;
  per_page: number;
}

// UTM Types
export interface UTM {
  id: number;
  base_url: string;
  utm_source: string;
  utm_medium: string;
  utm_campaign: string;
  utm_term?: string;
  utm_content?: string;
  full_url: string;
  program?: string;
  tags: string[];
  notes?: string;
  click_count: number;
  is_archived: boolean;
  created_at: string;
  updated_at: string;
}

export interface UTMFormData {
  base_url: string;
  utm_source: string;
  utm_medium: string;
  utm_campaign: string;
  utm_term?: string;
  utm_content?: string;
  program?: string;
  tags?: string[];
  notes?: string;
}

// Link Types
export interface Link {
  id: number;
  destination_url: string;
  slug: string;
  short_url: string;
  title?: string;
  utm_id?: number;
  click_count: number;
  unique_clicks?: number;
  status?: 'active' | 'inactive' | 'expired';
  is_active: boolean;
  password_protected?: boolean;
  has_password?: boolean;
  expires_at?: string;
  qr_code_url?: string;
  created_at: string;
  updated_at?: string;
}

export interface LinkClick {
  id: number;
  link_id: number;
  ip_hash: string;
  user_agent: string;
  referrer?: string;
  device: string;
  browser: string;
  os: string;
  country?: string;
  clicked_at: string;
}

export interface LinkFormData {
  destination_url: string;
  slug?: string;
  title?: string;
  utm_id?: number;
  password?: string;
  expires_at?: string;
}

// Contact Types
export type ContactStatus = 'lead' | 'contacted' | 'qualified' | 'customer' | 'churned';

export interface Contact {
  id: number;
  email: string;
  first_name?: string;
  last_name?: string;
  phone?: string;
  company?: string;
  status: ContactStatus;
  source?: string;
  source_detail?: string;
  score: number;
  tags: string[];
  custom_fields: Record<string, unknown>;
  last_activity_at?: string;
  created_at: string;
  updated_at: string;
}

export interface ContactActivity {
  id: number;
  contact_id: number;
  type: string;
  description: string;
  created_at: string;
}

export interface ContactFormData {
  email: string;
  first_name?: string;
  last_name?: string;
  phone?: string;
  company?: string;
  status?: ContactStatus;
  source?: string;
  tags?: string[];
  custom_fields?: Record<string, unknown>;
}

// Popup Types
export type PopupType = 'modal' | 'slide-in' | 'bar' | 'fullscreen';
export type PopupStatus = 'draft' | 'active' | 'paused' | 'archived';
export type TriggerType = 'time_delay' | 'scroll_percent' | 'scroll_element' | 'exit_intent' | 'click' | 'page_views' | 'inactivity';

export interface PopupFormField {
  name: string;
  type: 'email' | 'text' | 'tel' | 'textarea' | 'select' | 'checkbox';
  label: string;
  placeholder?: string;
  required?: boolean;
  options?: { value: string; label: string }[];
}

export interface PopupTrigger {
  type: TriggerType;
  delay?: number;
  percent?: number;
  selector?: string;
  offset?: number;
  sensitivity?: number;
  count?: number;
  timeout?: number;
}

export interface PopupDisplayRules {
  pages?: 'all' | 'homepage' | { mode: 'include' | 'exclude'; ids?: number[]; types?: string[]; url_patterns?: string[] };
  devices?: ('desktop' | 'tablet' | 'mobile')[];
  user_status?: 'all' | 'logged_in' | 'logged_out' | { roles: string[] };
  referrer?: {
    mode?: 'include' | 'exclude';
    patterns?: string[];
    include_direct?: boolean;
    include_internal?: boolean;
  };
}

export interface PopupStyles {
  background_color?: string;
  text_color?: string;
  button_color?: string;
  button_text_color?: string;
  border_radius?: number;
  max_width?: number;
}

export interface PopupSettings {
  animation?: 'fade' | 'slide' | 'scale';
  overlay?: boolean;
  overlay_color?: string;
  close_button?: boolean;
  close_on_overlay?: boolean;
  close_on_esc?: boolean;
  hide_after_dismiss_days?: number;
  hide_after_convert_days?: number;
}

export interface Popup {
  id: number;
  name: string;
  type: PopupType;
  position: string;
  status: PopupStatus;
  priority: number;
  title?: string;
  content?: string;
  image_url?: string;
  form_fields: PopupFormField[];
  button_text: string;
  success_message: string;
  triggers: PopupTrigger;
  display_rules: PopupDisplayRules;
  styles: PopupStyles;
  settings: PopupSettings;
  views: number;
  conversions: number;
  conversion_rate: number;
  start_date?: string;
  end_date?: string;
  created_at: string;
  updated_at: string;
}

export interface PopupFormData {
  name: string;
  type?: PopupType;
  position?: string;
  status?: PopupStatus;
  priority?: number;
  title?: string;
  content?: string;
  image_url?: string;
  form_fields?: PopupFormField[];
  button_text?: string;
  success_message?: string;
  triggers?: PopupTrigger;
  display_rules?: PopupDisplayRules;
  styles?: PopupStyles;
  settings?: PopupSettings;
  start_date?: string;
  end_date?: string;
}

// Dashboard Types
export interface DashboardStats {
  utm_total: number;
  utm_created: number;
  utm_clicks: number;
  utm_top_campaigns: { utm_campaign: string; clicks: number; count: number }[];
  links_total: number;
  links_clicks: number;
  links_unique_clicks: number;
  contacts_total: number;
  contacts_new: number;
  contacts_by_status: Record<ContactStatus, number>;
  popups_active: number;
  popups_views: number;
  popups_conversions: number;
  popups_conversion_rate: number;
}

export interface TimelineData {
  date: string;
  utm_clicks: number;
  link_clicks: number;
  contacts: number;
  conversions: number;
}

export interface ActivityItem {
  id: string;
  type: 'utm' | 'link' | 'contact' | 'popup';
  action: string;
  description: string;
  timestamp: string;
}

// Settings Types
export interface Settings {
  link_prefix: string;
  track_clicks: boolean;
  anonymize_ip: boolean;
}

export interface Module {
  id: string;
  name: string;
  description: string;
  icon: string;
  active: boolean;
  pro: boolean;
  tier?: string;
  locked: boolean;
  required_tier: string;
}

export interface License {
  status: 'active' | 'expired' | 'invalid' | 'free';
  tier: 'free' | 'pro' | 'agency' | 'enterprise';
  expires_at?: string;
  features: Record<string, number | boolean>;
}

// Tag Types
export interface Tag {
  id: number;
  name: string;
  color: string;
}

// Monitor Types (Agency)
export interface MonitorSiteHealthChecks {
  ssl?: { enabled: boolean };
  debug_mode?: boolean;
  disk_space?: {
    used_formatted: string;
    free_formatted: string;
    used_percent: number;
  };
  plugins?: {
    active: number;
    inactive: number;
    updates_available: number;
    needing_update?: { slug: string; name: string; version: string; new_version: string }[];
  };
  server?: {
    memory_limit: string;
    max_execution_time: string | number;
  };
  backup?: {
    last_backup: string | null;
  };
  database?: {
    mysql_version: string;
  };
}

export interface MonitorSite {
  id: number;
  url: string;
  name?: string;
  status: 'connected' | 'disconnected' | 'error';
  health_score: number;
  uptime_percent: number;
  wp_version?: string;
  php_version?: string;
  ssl_enabled: boolean;
  updates_available: number;
  last_checked?: string;
  created_at: string;
  updated_at: string;
  health?: {
    checks?: MonitorSiteHealthChecks;
  };
}

export interface MonitorHealth {
  site_id: number;
  wordpress_version: string;
  php_version: string;
  mysql_version: string;
  ssl_enabled: boolean;
  multisite: boolean;
  debug_mode: boolean;
  memory_limit: string;
  max_execution_time: number;
  disk_total: string;
  disk_used: string;
  disk_free: string;
  disk_percent: number;
  plugins_active: number;
  plugins_inactive: number;
  plugins_updates: number;
  plugin_updates?: { slug: string; name: string; current: string; new: string }[];
  theme_updates?: { slug: string; name: string; current: string; new: string }[];
  last_backup?: string;
  checked_at: string;
}

// Webhook Types
export type WebhookStatus = 'pending' | 'processing' | 'processed' | 'failed';

export interface Webhook {
  id: number;
  source: string;
  event: string;
  payload: Record<string, unknown>;
  signature: string | null;
  ip_address: string | null;
  processed_at: string | null;
  status: WebhookStatus;
  error_message: string | null;
  retry_count: number;
  created_at: string;
}

export interface WebhookStats {
  total: number;
  pending: number;
  processing: number;
  processed: number;
  failed: number;
  today: number;
}

export interface WebhookFilters {
  sources: string[];
  events: string[];
  statuses: WebhookStatus[];
}

// Visitor Types
export interface Visitor {
  id: number;
  visitor_id: string;
  first_seen: string;
  last_seen: string;
  total_visits: number;
  total_pageviews: number;
  device_type: 'desktop' | 'mobile' | 'tablet' | null;
  browser: string | null;
  os: string | null;
  country: string | null;
  region: string | null;
  city: string | null;
  customer_id: number | null;
  contact_id: number | null;
  email: string | null;
  created_at: string;
  recent_events?: VisitorEvent[];
}

export interface VisitorEvent {
  id: number;
  visitor_id: string;
  session_id: string | null;
  event_type: string;
  page_url: string | null;
  page_title: string | null;
  referrer: string | null;
  utm_source: string | null;
  utm_medium: string | null;
  utm_campaign: string | null;
  utm_term: string | null;
  utm_content: string | null;
  custom_data: Record<string, unknown> | null;
  created_at: string;
}

export interface VisitorStats {
  total: number;
  identified: number;
  anonymous: number;
  today: number;
  this_week: number;
  total_pageviews: number;
  events_today: number;
}

// Attribution Types
export interface Touch {
  id: number;
  visitor_id: string;
  session_id: string | null;
  touch_type: string;
  channel: string | null;
  source: string | null;
  medium: string | null;
  campaign: string | null;
  content: string | null;
  term: string | null;
  landing_page: string | null;
  referrer: string | null;
  touch_time: string;
  conversion_id: number | null;
  created_at: string;
}

export interface Conversion {
  id: number;
  visitor_id: string;
  conversion_type: string;
  conversion_value: number;
  source: string | null;
  source_id: string | null;
  customer_email: string | null;
  customer_name: string | null;
  metadata: Record<string, unknown> | null;
  converted_at: string;
  created_at: string;
  touches?: Touch[];
  attribution?: AttributionResult[];
}

export interface AttributionResult {
  id: number;
  conversion_id: number;
  touch_id: number;
  model: string;
  credit: number;
  calculated_at: string;
  channel?: string;
  source?: string;
  medium?: string;
  campaign?: string;
  touch_time?: string;
}

export interface AttributionModel {
  id: string;
  name: string;
  description: string;
}

export interface ChannelPerformance {
  channel: string;
  conversions: number;
  attributed_credit: number;
  attributed_value: number;
  touches: number;
}

export interface AttributionStats {
  total_conversions: number;
  total_value: number;
  today_conversions: number;
  today_value: number;
  month_conversions: number;
  month_value: number;
  total_touches: number;
}

export interface AttributionReport {
  model: string;
  model_name: string;
  model_description: string;
  date_range: {
    from: string;
    to: string;
  };
  channels: ChannelPerformance[];
  stats: AttributionStats;
}

// Analytics Types
export interface AnalyticsOverview {
  period: {
    from: string;
    to: string;
  };
  realtime: AnalyticsRealtime;
  summary: {
    visitors: {
      total: number;
      change: number;
      trend: 'up' | 'down';
    };
    pageviews: {
      total: number;
    };
    conversions: {
      total: number;
      value: number;
      change: number;
      trend: 'up' | 'down';
    };
  };
  breakdown: Record<string, Record<string, { count: number; value: number }>>;
}

export interface AnalyticsRealtime {
  active_visitors?: number;
  recent_events?: number;
  today_pageviews?: number;
  today_conversions?: number;
  today_revenue?: number;
}

export interface AnalyticsTimeline {
  period: {
    from: string;
    to: string;
  };
  timeline: AnalyticsTimelinePoint[];
}

export interface AnalyticsTimelinePoint {
  date: string;
  visitors: number;
  pageviews: number;
  conversions: number;
}

export interface AnalyticsSources {
  period: {
    from: string;
    to: string;
  };
  sources: AnalyticsBreakdownItem[];
  channels: AnalyticsBreakdownItem[];
}

export interface AnalyticsDevices {
  period: {
    from: string;
    to: string;
  };
  devices: AnalyticsBreakdownItem[];
  browsers: AnalyticsBreakdownItem[];
}

export interface AnalyticsBreakdownItem {
  dimension_value: string;
  total_count: number;
  total_value: number;
}

export interface AnalyticsFunnel {
  period: {
    from: string;
    to: string;
  };
  funnel: {
    stage: string;
    count: number;
    rate: number;
  }[];
}

export interface AnalyticsComparison {
  current_period: {
    from: string;
    to: string;
  };
  previous_period: {
    from: string;
    to: string;
  };
  comparisons: {
    visitors: AnalyticsComparisonMetric;
    pageviews: AnalyticsComparisonMetric;
    conversions: AnalyticsComparisonMetric;
  };
}

export interface AnalyticsComparisonMetric {
  current: number;
  previous: number;
  change: number;
  change_percent: number;
  trend: 'up' | 'down';
}

// Account & Team Types
export type AccountRole = 'owner' | 'admin' | 'member' | 'viewer';

export interface FeaturePermission {
  access: boolean;
}

export interface FeaturePermissions {
  utm?: FeaturePermission;
  links?: FeaturePermission;
  contacts?: FeaturePermission;
  webhooks?: FeaturePermission;
  visitors?: FeaturePermission;
  attribution?: FeaturePermission;
  analytics?: FeaturePermission;
  popups?: FeaturePermission;
  monitor?: FeaturePermission;
}

export interface Account {
  id: number;
  name: string;
  slug: string;
  status: 'active' | 'suspended';
  tier: 'free' | 'pro' | 'agency';
  max_users: number;
  owner_user_id: number;
  role: AccountRole;
  settings: Record<string, unknown>;
  created_at: string;
  updated_at: string;
}

export interface AccountMember {
  user_id: number;
  user_login: string;
  user_email: string;
  display_name: string;
  role: AccountRole;
  feature_permissions: FeaturePermissions | null;
  invited_at: string | null;
  accepted_at: string | null;
  created_at: string;
}

export interface AccountMemberFormData {
  email: string;
  role: AccountRole;
  feature_permissions?: FeaturePermissions;
}

export interface UTMAccess {
  id: number;
  utm_id: number;
  user_id: number;
  account_id: number;
  access_level: 'view' | 'edit' | 'full';
  assigned_by: number;
  assigned_at: string;
}

// Audit Log Types
export type AuditLogAction = 'create' | 'update' | 'delete' | 'login' | 'logout' | 'invite' | 'revoke' | 'export' | 'access_denied' | 'rate_limited';
export type AuditLogResource = 'account' | 'member' | 'api_key' | 'utm' | 'link' | 'contact' | 'popup' | 'settings' | 'audit_log';

export interface AuditLogEntry {
  id: number;
  account_id: number;
  user_id: number | null;
  user_name: string | null;
  user_email: string | null;
  api_key_id: number | null;
  action: AuditLogAction;
  resource_type: AuditLogResource;
  resource_id: number | null;
  details: Record<string, unknown> | null;
  ip_address: string | null;
  user_agent: string | null;
  created_at: string;
}

// =========================================
// Plesk Server Monitoring Types
// =========================================

export type ServerStatus = 'active' | 'disconnected' | 'error';
export type ServerHealthStatus = 'healthy' | 'warning' | 'critical' | 'offline';
export type HealthGrade = 'A' | 'B' | 'C' | 'D' | 'F';

export interface PleskServer {
  id: number;
  user_id: number;
  server_name: string;
  server_host: string;
  server_port: number;
  status: ServerStatus;
  last_check: string | null;
  last_health: ServerHealth | null;
  plesk_version: string | null;
  os_info: string | null;
  created_at: string;
  updated_at?: string;
}

export interface ServerHealthCheck {
  value: number;
  status: 'ok' | 'warning' | 'critical';
  message: string;
  total?: number;
  used?: number;
  free?: number;
}

export interface ServerServicesCheck {
  stopped: string[];
  stopped_count: number;
  status: 'ok' | 'critical';
  message: string;
}

export interface ServerSSLCheck {
  issues: string[];
  issue_count: number;
  status: 'ok' | 'warning';
  message: string;
}

export interface ServerHealth {
  score: number;
  grade: HealthGrade;
  status: ServerHealthStatus;
  checks: {
    cpu_usage?: ServerHealthCheck;
    ram_usage?: ServerHealthCheck;
    disk_usage?: ServerHealthCheck;
    load_average?: ServerHealthCheck;
    services?: ServerServicesCheck;
    ssl_certs?: ServerSSLCheck;
    plesk_updates?: {
      version: string | null;
      available: boolean;
      status: 'ok' | 'warning';
      message: string;
    };
  };
  domains?: PleskDomain[];
  uptime?: number;
}

export interface ServerHealthHistory {
  id: number;
  server_id: number;
  status: ServerHealthStatus;
  score: number;
  grade: HealthGrade;
  checks: ServerHealth['checks'];
  checked_at: string;
}

export interface PleskDomain {
  id?: number;
  name: string;
  status: string;
  hosting?: string;
  ssl: boolean;
  ssl_expiry?: string | null;
  created_at?: string | null;
}

export interface PleskService {
  id: string;
  name: string;
  running: boolean;
  status: string;
}

export interface ServerFormData {
  server_name?: string;
  server_host: string;
  server_port?: number;
  api_key: string;
}

export interface ServersOverview {
  total_servers: number;
  active_servers: number;
  servers_with_errors: number;
  servers_needing_attention: number;
}

// =========================================
// Health Reports Types
// =========================================

export type ReportFrequency = 'weekly' | 'monthly';
export type RecommendationPriority = 'critical' | 'high' | 'medium' | 'low';

export interface HealthReportSettings {
  id: number;
  user_id: number;
  enabled: boolean;
  frequency: ReportFrequency;
  day_of_week: number;
  send_time: string;
  recipients: string[];
  include_sites: boolean;
  include_servers: boolean;
  include_recommendations: boolean;
  selected_site_ids: number[];
  selected_server_ids: number[];
  created_at?: string;
  updated_at?: string;
}

export interface HealthReportSettingsFormData {
  enabled?: boolean;
  frequency?: ReportFrequency;
  day_of_week?: number;
  send_time?: string;
  recipients?: string[];
  include_sites?: boolean;
  include_servers?: boolean;
  include_recommendations?: boolean;
  selected_site_ids?: number[];
  selected_server_ids?: number[];
}

export interface HealthReportItem {
  id?: number;
  name: string;
  grade: HealthGrade;
  score: number;
  issues: string[];
  url?: string;
}

export interface HealthReportSection {
  summary: {
    total: number;
    healthy: number;
    warning: number;
    critical: number;
  };
  items: HealthReportItem[];
}

export interface HealthReportRecommendation {
  priority: RecommendationPriority;
  message: string;
  resource_type?: 'site' | 'server';
  resource_id?: number;
  resource_name?: string;
}

export interface HealthReportTrends {
  this_week: number;
  last_week: number;
  change: string;
}

export interface HealthReport {
  id: number;
  user_id: number;
  report_type: ReportFrequency;
  period_start: string;
  period_end: string;
  overall_grade: HealthGrade;
  overall_score: number;
  sites_data: HealthReportSection | null;
  servers_data: HealthReportSection | null;
  recommendations: HealthReportRecommendation[];
  trends?: HealthReportTrends;
  sent_at: string | null;
  created_at: string;
}

export interface HealthReportPreview {
  period: {
    start: string;
    end: string;
  };
  overall: {
    grade: HealthGrade;
    score: number;
  };
  sites: HealthReportSection | null;
  servers: HealthReportSection | null;
  recommendations: HealthReportRecommendation[];
  trends: HealthReportTrends;
}

// ============================================
// API Keys Types
// ============================================
export type ApiKeyScope =
  | 'links:read'
  | 'links:write'
  | 'utms:read'
  | 'utms:write'
  | 'contacts:read'
  | 'contacts:write'
  | 'analytics:read';

export interface ApiKey {
  id: number;
  account_id: number;
  key_id: string;
  key_preview: string;
  name: string;
  scopes: ApiKeyScope[];
  created_by: number;
  created_by_name: string | null;
  last_used_at: string | null;
  last_used_ip: string | null;
  expires_at: string | null;
  revoked_at: string | null;
  revoked_by: number | null;
  created_at: string;
}

export interface ApiKeyWithSecret extends ApiKey {
  key: string; // Full key, only shown once on creation
}

export interface ApiKeyFormData {
  name: string;
  scopes: ApiKeyScope[];
  expires_at?: string | null;
}

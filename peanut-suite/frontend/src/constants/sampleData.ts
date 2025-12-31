// Sample/placeholder data for empty states
// This data helps new users understand what each section looks like with real data
// Using type assertions since sample data doesn't need all required fields

import type { Link, Contact, Webhook, Visitor, Popup, MonitorSite, UTM } from '../types';

export const sampleLinks = [
  {
    id: 1,
    slug: 'summer24',
    short_url: 'https://pnut.link/summer24',
    original_url: 'https://example.com/promotions/summer-sale-2024',
    title: 'Summer Sale 2024',
    created_at: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
    click_count: 342,
    unique_clicks: 287,
    status: 'active',
  },
  {
    id: 2,
    slug: 'webinar',
    short_url: 'https://pnut.link/webinar',
    original_url: 'https://example.com/events/marketing-webinar-registration',
    title: 'Marketing Webinar Registration',
    created_at: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString(),
    click_count: 156,
    unique_clicks: 143,
    status: 'active',
  },
  {
    id: 3,
    slug: 'ebook',
    short_url: 'https://pnut.link/ebook',
    original_url: 'https://example.com/resources/ultimate-guide-to-utm.pdf',
    title: 'Ultimate Guide to UTM',
    created_at: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString(),
    click_count: 89,
    unique_clicks: 76,
    status: 'active',
  },
] as Link[];

export const sampleContacts = [
  {
    id: 1,
    email: 'sarah.johnson@company.com',
    first_name: 'Sarah',
    last_name: 'Johnson',
    phone: '+1 (555) 123-4567',
    company: 'TechStart Inc.',
    source: 'form',
    status: 'qualified',
    tags: ['qualified', 'enterprise'],
    created_at: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString(),
    updated_at: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 2,
    email: 'mike.chen@startup.io',
    first_name: 'Mike',
    last_name: 'Chen',
    company: 'Startup.io',
    source: 'webhook',
    status: 'lead',
    tags: ['trial', 'smb'],
    created_at: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString(),
    updated_at: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 3,
    email: 'emma.davis@agency.co',
    first_name: 'Emma',
    last_name: 'Davis',
    company: 'Digital Agency Co',
    source: 'import',
    status: 'customer',
    tags: ['agency', 'partner'],
    created_at: new Date(Date.now() - 10 * 24 * 60 * 60 * 1000).toISOString(),
    updated_at: new Date(Date.now() - 4 * 24 * 60 * 60 * 1000).toISOString(),
  },
] as Contact[];

export const sampleWebhooks = [
  {
    id: 1,
    source: 'Stripe',
    event: 'payment.succeeded',
    status: 'processed',
    payload: {
      customer: 'cus_abc123',
      amount: 9900,
      currency: 'usd',
    },
    signature: 'sig_abc123',
    retry_count: 0,
    created_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
    processed_at: new Date(Date.now() - 2 * 60 * 60 * 1000 + 1500).toISOString(),
    ip_address: '54.187.174.169',
    error_message: null,
  },
  {
    id: 2,
    source: 'HubSpot',
    event: 'contact.created',
    status: 'processed',
    payload: {
      email: 'new.lead@example.com',
      firstname: 'New',
      lastname: 'Lead',
    },
    signature: 'sig_def456',
    retry_count: 0,
    created_at: new Date(Date.now() - 5 * 60 * 60 * 1000).toISOString(),
    processed_at: new Date(Date.now() - 5 * 60 * 60 * 1000 + 800).toISOString(),
    ip_address: '104.16.249.5',
    error_message: null,
  },
  {
    id: 3,
    source: 'Typeform',
    event: 'form.submitted',
    status: 'pending',
    payload: {
      form_id: 'abc123',
      responses: { email: 'user@example.com', message: 'Interested in demo' },
    },
    signature: 'sig_ghi789',
    retry_count: 0,
    created_at: new Date(Date.now() - 15 * 60 * 1000).toISOString(),
    processed_at: null,
    ip_address: '52.42.108.131',
    error_message: null,
  },
] as unknown as Webhook[];

export const sampleVisitors = [
  {
    id: 1,
    visitor_id: 'v_a1b2c3d4e5f6g7h8',
    email: 'returning.visitor@example.com',
    device_type: 'desktop',
    browser: 'Chrome 120',
    os: 'macOS',
    total_visits: 8,
    total_pageviews: 32,
    first_seen: new Date(Date.now() - 14 * 24 * 60 * 60 * 1000).toISOString(),
    last_seen: new Date(Date.now() - 1 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 2,
    visitor_id: 'v_h8g7f6e5d4c3b2a1',
    device_type: 'mobile',
    browser: 'Safari 17',
    os: 'iOS',
    total_visits: 3,
    total_pageviews: 7,
    first_seen: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString(),
    last_seen: new Date(Date.now() - 12 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 3,
    visitor_id: 'v_x9y8z7w6v5u4t3s2',
    email: 'identified.user@company.com',
    device_type: 'tablet',
    browser: 'Firefox 121',
    os: 'Windows',
    total_visits: 5,
    total_pageviews: 18,
    first_seen: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString(),
    last_seen: new Date(Date.now() - 4 * 60 * 60 * 1000).toISOString(),
  },
] as Visitor[];

export const samplePopups = [
  {
    id: 1,
    name: 'Newsletter Signup',
    type: 'modal',
    status: 'active',
    triggers: { type: 'time_delay', delay: 5 },
    views: 1234,
    conversions: 156,
    created_at: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString(),
    updated_at: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 2,
    name: 'Exit Intent Offer',
    type: 'slide-in',
    status: 'active',
    triggers: { type: 'exit_intent' },
    views: 876,
    conversions: 98,
    created_at: new Date(Date.now() - 14 * 24 * 60 * 60 * 1000).toISOString(),
    updated_at: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 3,
    name: 'Black Friday Banner',
    type: 'bar',
    status: 'draft',
    triggers: { type: 'page_views', count: 2 },
    views: 0,
    conversions: 0,
    created_at: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
    updated_at: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
  },
] as Popup[];

export const sampleMonitorSites = [
  {
    id: 1,
    url: 'https://mainsite.example.com',
    name: 'Main Company Site',
    health_score: 95,
    uptime_percent: 99.98,
    wp_version: '6.4.2',
    php_version: '8.2.12',
    updates_available: 0,
    last_checked: new Date(Date.now() - 15 * 60 * 1000).toISOString(),
    status: 'connected',
  },
  {
    id: 2,
    url: 'https://blog.example.com',
    name: 'Company Blog',
    health_score: 78,
    uptime_percent: 99.85,
    wp_version: '6.4.1',
    php_version: '8.1.25',
    updates_available: 3,
    last_checked: new Date(Date.now() - 20 * 60 * 1000).toISOString(),
    status: 'connected',
  },
  {
    id: 3,
    url: 'https://store.example.com',
    name: 'E-commerce Store',
    health_score: 92,
    uptime_percent: 99.95,
    wp_version: '6.4.2',
    php_version: '8.2.12',
    updates_available: 1,
    last_checked: new Date(Date.now() - 10 * 60 * 1000).toISOString(),
    status: 'connected',
  },
] as MonitorSite[];

export const sampleUTMs = [
  {
    id: 1,
    base_url: 'https://example.com/landing-page',
    utm_source: 'google',
    utm_medium: 'cpc',
    utm_campaign: 'summer_sale_2024',
    utm_term: 'marketing software',
    utm_content: 'ad_variation_a',
    full_url: 'https://example.com/landing-page?utm_source=google&utm_medium=cpc&utm_campaign=summer_sale_2024&utm_term=marketing+software&utm_content=ad_variation_a',
    click_count: 456,
    created_at: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString(),
    updated_at: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString(),
    tags: ['paid', 'google'],
    is_archived: false,
  },
  {
    id: 2,
    base_url: 'https://example.com/webinar',
    utm_source: 'linkedin',
    utm_medium: 'social',
    utm_campaign: 'q4_webinar_series',
    full_url: 'https://example.com/webinar?utm_source=linkedin&utm_medium=social&utm_campaign=q4_webinar_series',
    click_count: 234,
    created_at: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString(),
    updated_at: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString(),
    tags: ['organic', 'linkedin'],
    is_archived: false,
  },
  {
    id: 3,
    base_url: 'https://example.com/ebook',
    utm_source: 'newsletter',
    utm_medium: 'email',
    utm_campaign: 'december_newsletter',
    utm_content: 'cta_button',
    full_url: 'https://example.com/ebook?utm_source=newsletter&utm_medium=email&utm_campaign=december_newsletter&utm_content=cta_button',
    click_count: 189,
    created_at: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
    updated_at: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
    tags: ['email', 'content'],
    is_archived: false,
  },
] as UTM[];

// Sample stats for various pages
export const sampleStats = {
  links: {
    total: 3,
    total_clicks: 587,
    clicks_today: 45,
    unique_clicks: 506,
    active: 3,
  },
  contacts: {
    total: 3,
    active: 3,
    new_today: 1,
    new_this_week: 2,
    by_source: {
      form: 1,
      webhook: 1,
      import: 1,
    },
  },
  webhooks: {
    total: 3,
    processed: 2,
    pending: 1,
    failed: 0,
    today: 3,
  },
  visitors: {
    total: 3,
    identified: 2,
    anonymous: 1,
    today: 2,
    this_week: 3,
    total_pageviews: 57,
    events_today: 12,
  },
  popups: {
    total: 3,
    active: 2,
    total_views: 2110,
    total_conversions: 254,
  },
  monitor: {
    total: 3,
    healthy: 2,
    warning: 1,
    critical: 0,
    updates_available: 4,
  },
};

// Sample Dashboard data
export const sampleDashboardStats = {
  utmTotal: 12,
  linksTotal: 8,
  contactsTotal: 156,
  popupsTotal: 5,
};

// Sample Attribution data
export const sampleAttributionStats = {
  total_conversions: 234,
  total_value: 45680,
  today_conversions: 12,
  month_conversions: 89,
  total_touches: 1456,
};

export const sampleAttributionModels = [
  { id: 'first_touch', name: 'First Touch', description: 'Gives 100% credit to the first touchpoint' },
  { id: 'last_touch', name: 'Last Touch', description: 'Gives 100% credit to the last touchpoint before conversion' },
  { id: 'linear', name: 'Linear', description: 'Distributes credit equally across all touchpoints' },
  { id: 'time_decay', name: 'Time Decay', description: 'Gives more credit to touchpoints closer to conversion' },
  { id: 'position_based', name: 'Position Based', description: 'Gives 40% to first and last, 20% distributed among middle' },
];

export const sampleAttributionChannels = [
  { channel: 'Organic Search', conversions: 78, attributed_credit: 0.32, attributed_value: 14600, touches: 456 },
  { channel: 'Paid Search', conversions: 56, attributed_credit: 0.24, attributed_value: 10900, touches: 312 },
  { channel: 'Social', conversions: 34, attributed_credit: 0.15, attributed_value: 6800, touches: 234 },
  { channel: 'Email', conversions: 42, attributed_credit: 0.18, attributed_value: 8500, touches: 289 },
  { channel: 'Direct', conversions: 24, attributed_credit: 0.11, attributed_value: 4880, touches: 165 },
];

export const sampleAttributionComparison = {
  comparison: {
    first_touch: [
      { channel: 'Organic Search', conversions: 78, attributed_credit: 0.38, attributed_value: 14600, touches: 456 },
      { channel: 'Paid Search', conversions: 56, attributed_credit: 0.28, attributed_value: 10900, touches: 312 },
      { channel: 'Social', conversions: 34, attributed_credit: 0.18, attributed_value: 6800, touches: 234 },
      { channel: 'Email', conversions: 42, attributed_credit: 0.10, attributed_value: 3800, touches: 189 },
      { channel: 'Direct', conversions: 24, attributed_credit: 0.06, attributed_value: 2300, touches: 95 },
    ],
    last_touch: [
      { channel: 'Organic Search', conversions: 78, attributed_credit: 0.32, attributed_value: 14600, touches: 456 },
      { channel: 'Paid Search', conversions: 56, attributed_credit: 0.24, attributed_value: 10900, touches: 312 },
      { channel: 'Social', conversions: 34, attributed_credit: 0.15, attributed_value: 6800, touches: 234 },
      { channel: 'Email', conversions: 42, attributed_credit: 0.18, attributed_value: 8500, touches: 289 },
      { channel: 'Direct', conversions: 24, attributed_credit: 0.11, attributed_value: 4880, touches: 165 },
    ],
    linear: [
      { channel: 'Organic Search', conversions: 78, attributed_credit: 0.28, attributed_value: 12800, touches: 456 },
      { channel: 'Paid Search', conversions: 56, attributed_credit: 0.22, attributed_value: 10000, touches: 312 },
      { channel: 'Social', conversions: 34, attributed_credit: 0.20, attributed_value: 9100, touches: 234 },
      { channel: 'Email', conversions: 42, attributed_credit: 0.18, attributed_value: 8200, touches: 289 },
      { channel: 'Direct', conversions: 24, attributed_credit: 0.12, attributed_value: 5500, touches: 165 },
    ],
  } as Record<string, { channel: string; conversions: number; attributed_credit: number; attributed_value: number; touches: number }[]>,
  models: {
    first_touch: 'First Touch',
    last_touch: 'Last Touch',
    linear: 'Linear',
  } as Record<string, string>,
};

// Sample Analytics data
export const sampleAnalyticsOverview = {
  period: {
    from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    to: new Date().toISOString().split('T')[0],
  },
  summary: {
    visitors: { total: 4521, change: 12.5, trend: 'up' as const },
    pageviews: { total: 12834, change: 8.3, trend: 'up' as const },
    conversions: { total: 234, value: 45680, change: 15.2, trend: 'up' as const },
  },
};

export const sampleAnalyticsRealtime = {
  active_visitors: 23,
  recent_events: 47,
  today_pageviews: 892,
  today_conversions: 12,
  today_revenue: 2340,
};

export const sampleAnalyticsTimeline = (() => {
  const days = 30;
  return Array.from({ length: days }, (_, i) => {
    const date = new Date();
    date.setDate(date.getDate() - (days - 1 - i));
    return {
      date: date.toISOString().split('T')[0],
      visitors: Math.floor(Math.random() * 150) + 100,
      pageviews: Math.floor(Math.random() * 400) + 300,
      conversions: Math.floor(Math.random() * 15) + 5,
    };
  });
})();

export const sampleAnalyticsSources = [
  { dimension_value: 'google', total_count: 1823, total_value: 12500 },
  { dimension_value: 'direct', total_count: 1245, total_value: 8900 },
  { dimension_value: 'facebook', total_count: 678, total_value: 4500 },
  { dimension_value: 'twitter', total_count: 423, total_value: 2800 },
  { dimension_value: 'linkedin', total_count: 234, total_value: 1600 },
  { dimension_value: 'email', total_count: 118, total_value: 980 },
];

export const sampleAnalyticsDevices = [
  { dimension_value: 'desktop', total_count: 2456, total_value: 18500 },
  { dimension_value: 'mobile', total_count: 1678, total_value: 12300 },
  { dimension_value: 'tablet', total_count: 387, total_value: 2900 },
];

export const sampleAnalyticsFunnel = [
  { stage: 'Visitors', count: 4521, rate: 100 },
  { stage: 'Engaged', count: 2890, rate: 64 },
  { stage: 'Converted', count: 234, rate: 5.2 },
];

// Sample Team Members data
export const sampleTeamMembers = [
  {
    user_id: 1,
    user_login: 'john.smith',
    user_email: 'john.smith@company.com',
    display_name: 'John Smith',
    role: 'owner' as const,
    feature_permissions: {},
  },
  {
    user_id: 2,
    user_login: 'sarah.johnson',
    user_email: 'sarah.johnson@company.com',
    display_name: 'Sarah Johnson',
    role: 'admin' as const,
    feature_permissions: {},
  },
  {
    user_id: 3,
    user_login: 'mike.chen',
    user_email: 'mike.chen@company.com',
    display_name: 'Mike Chen',
    role: 'member' as const,
    feature_permissions: {
      utm: { access: true },
      links: { access: true },
      contacts: { access: true },
      webhooks: { access: true },
    },
  },
  {
    user_id: 4,
    user_login: 'emma.davis',
    user_email: 'emma.davis@company.com',
    display_name: 'Emma Davis',
    role: 'viewer' as const,
    feature_permissions: {
      utm: { access: true },
      links: { access: true },
    },
  },
];

// Sample Keywords data
export const sampleKeywords = [
  {
    id: 1,
    keyword: 'wordpress marketing plugin',
    target_url: 'https://example.com/plugins/marketing',
    search_engine: 'google',
    location: 'us',
    current_position: 3,
    previous_position: 5,
    change: 2,
    last_checked: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
    created_at: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 2,
    keyword: 'utm builder tool',
    target_url: 'https://example.com/utm-builder',
    search_engine: 'google',
    location: 'us',
    current_position: 8,
    previous_position: 12,
    change: 4,
    last_checked: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
    created_at: new Date(Date.now() - 45 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 3,
    keyword: 'link tracking software',
    target_url: 'https://example.com/features/links',
    search_engine: 'google',
    location: 'us',
    current_position: 15,
    previous_position: 14,
    change: -1,
    last_checked: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
    created_at: new Date(Date.now() - 20 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 4,
    keyword: 'website visitor analytics',
    target_url: 'https://example.com/analytics',
    search_engine: 'google',
    location: 'us',
    current_position: 6,
    previous_position: 6,
    change: 0,
    last_checked: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
    created_at: new Date(Date.now() - 60 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 5,
    keyword: 'popup builder wordpress',
    target_url: 'https://example.com/popups',
    search_engine: 'google',
    location: 'us',
    current_position: null,
    previous_position: null,
    change: 0,
    last_checked: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
    created_at: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString(),
  },
];

// Sample Backlinks data
export const sampleBacklinks = [
  {
    id: 1,
    source_url: 'https://wpbeginner.com/plugins/best-marketing-plugins',
    source_domain: 'wpbeginner.com',
    target_url: 'https://example.com/',
    anchor_text: 'Peanut Suite',
    link_type: 'dofollow' as const,
    status: 'active' as const,
    first_seen: new Date(Date.now() - 60 * 24 * 60 * 60 * 1000).toISOString(),
    last_checked: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
    domain_authority: 78,
  },
  {
    id: 2,
    source_url: 'https://developer.wordpress.org/plugins/peanut-suite',
    source_domain: 'developer.wordpress.org',
    target_url: 'https://example.com/',
    anchor_text: 'official website',
    link_type: 'dofollow' as const,
    status: 'active' as const,
    first_seen: new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString(),
    last_checked: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
    domain_authority: 92,
  },
  {
    id: 3,
    source_url: 'https://techcrunch.com/article/best-wp-tools',
    source_domain: 'techcrunch.com',
    target_url: 'https://example.com/features',
    anchor_text: 'marketing automation',
    link_type: 'nofollow' as const,
    status: 'active' as const,
    first_seen: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString(),
    last_checked: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
    domain_authority: 94,
  },
  {
    id: 4,
    source_url: 'https://oldblog.example.net/review',
    source_domain: 'oldblog.example.net',
    target_url: 'https://example.com/',
    anchor_text: 'read more',
    link_type: 'dofollow' as const,
    status: 'lost' as const,
    first_seen: new Date(Date.now() - 120 * 24 * 60 * 60 * 1000).toISOString(),
    last_checked: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
    domain_authority: 35,
  },
];

export const sampleBacklinksStats = {
  total: 4,
  active: 3,
  lost: 1,
  broken: 0,
  dofollow: 3,
  nofollow: 1,
  unique_domains: 4,
  new_30_days: 1,
  lost_7_days: 0,
};

// Sample WooCommerce data
export const sampleWooCommerceStats = {
  total_revenue: 45680,
  total_orders: 234,
  attributed_orders: 189,
  attribution_rate: 0.81,
  by_source: [
    { source: 'google', revenue: 18500, orders: 78 },
    { source: 'facebook', revenue: 12300, orders: 56 },
    { source: 'email', revenue: 8900, orders: 34 },
    { source: 'direct', revenue: 5980, orders: 21 },
  ],
  by_campaign: [
    { campaign: 'summer_sale_2024', revenue: 15600, orders: 67 },
    { campaign: 'holiday_promo', revenue: 12400, orders: 54 },
    { campaign: 'newsletter_dec', revenue: 8200, orders: 38 },
    { campaign: 'retargeting_q4', revenue: 5800, orders: 22 },
    { campaign: '(no campaign)', revenue: 3680, orders: 8 },
  ],
  daily: [],
};

export const sampleWooCommerceReport = [
  { channel: 'Google Ads', orders: 78, customers: 72, revenue: 18500, avg_order_value: 237 },
  { channel: 'Facebook', orders: 56, customers: 51, revenue: 12300, avg_order_value: 220 },
  { channel: 'Email', orders: 34, customers: 28, revenue: 8900, avg_order_value: 262 },
  { channel: 'Direct', orders: 21, customers: 19, revenue: 5980, avg_order_value: 285 },
];

export const sampleWooCommerceOrders = [
  {
    id: 1,
    order_id: 1234,
    customer_email: 'customer1@example.com',
    order_total: 299.99,
    utm_source: 'google',
    utm_medium: 'cpc',
    utm_campaign: 'summer_sale_2024',
    created_at: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 2,
    order_id: 1233,
    customer_email: 'shopper@company.com',
    order_total: 149.50,
    utm_source: 'facebook',
    utm_medium: 'social',
    utm_campaign: 'holiday_promo',
    created_at: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 3,
    order_id: 1232,
    customer_email: 'buyer@mail.com',
    order_total: 89.99,
    utm_source: 'email',
    utm_medium: 'newsletter',
    utm_campaign: 'newsletter_dec',
    created_at: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
  },
];

// Sample Performance data
export const samplePerformanceScores = [
  {
    id: 1,
    url: 'https://example.com/',
    overall_score: 92,
    performance_score: 88,
    accessibility_score: 95,
    best_practices_score: 92,
    seo_score: 100,
    lcp_ms: 1850,
    fid_ms: 45,
    inp_ms: 120,
    cls: 0.05,
    fcp_ms: 1200,
    ttfb_ms: 420,
    tbt_ms: 180,
    checked_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
    opportunities: [
      { id: 'unused-css', title: 'Remove unused CSS', description: 'Remove unused rules from stylesheets', savings_ms: 450 },
      { id: 'render-blocking', title: 'Eliminate render-blocking resources', description: 'Resources are blocking first paint', savings_ms: 320 },
    ],
    diagnostics: [
      { id: 'dom-size', title: 'DOM Size', description: 'Document has 1,234 elements', score: 0.7 },
      { id: 'font-display', title: 'Font Display', description: 'All fonts loaded with font-display: swap', score: 1 },
    ],
  },
  {
    id: 2,
    url: 'https://example.com/blog/',
    overall_score: 78,
    performance_score: 72,
    accessibility_score: 88,
    best_practices_score: 83,
    seo_score: 91,
    lcp_ms: 2850,
    fid_ms: 85,
    inp_ms: 220,
    cls: 0.12,
    fcp_ms: 1800,
    ttfb_ms: 650,
    tbt_ms: 350,
    checked_at: new Date(Date.now() - 3 * 60 * 60 * 1000).toISOString(),
    opportunities: [
      { id: 'images', title: 'Properly size images', description: 'Serve images in correct dimensions', savings_ms: 1200 },
      { id: 'unused-js', title: 'Remove unused JavaScript', description: 'Reduce unused JavaScript', savings_ms: 680 },
    ],
    diagnostics: [
      { id: 'largest-paint', title: 'Largest Contentful Paint element', description: 'Hero image is the LCP element', score: 0.5 },
    ],
  },
];

export const samplePerformanceAverages = {
  overall: 85,
  performance: 80,
  accessibility: 92,
  seo: 96,
  lcp: 2350,
  fid: 65,
  cls: 0.085,
};

export const samplePerformanceSettings = {
  api_key_set: false,
  auto_check_enabled: false,
  check_frequency: 'weekly',
  alert_threshold: 50,
  urls: ['https://example.com/', 'https://example.com/blog/'],
};

// Sample Security data
export const sampleSecuritySettings = {
  hide_login_enabled: true,
  login_slug: 'secure-login',
  redirect_slug: 'not-found',
  limit_login_enabled: true,
  max_attempts: 5,
  lockout_duration: 30,
  lockout_increment: true,
  ip_whitelist: ['192.168.1.1'],
  ip_blacklist: [],
  '2fa_enabled': false,
  '2fa_method': 'email' as const,
  '2fa_roles': ['administrator'],
  notify_login_success: false,
  notify_login_failed: true,
  notify_lockout: true,
  notify_email: '',
};

export const sampleLoginAttempts = [
  {
    id: 1,
    ip_address: '192.168.1.100',
    username: 'admin',
    status: 'success' as const,
    attempt_time: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 2,
    ip_address: '45.33.32.156',
    username: 'administrator',
    status: 'failed' as const,
    attempt_time: new Date(Date.now() - 4 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 3,
    ip_address: '45.33.32.156',
    username: 'admin',
    status: 'failed' as const,
    attempt_time: new Date(Date.now() - 4 * 60 * 60 * 1000 + 30000).toISOString(),
  },
  {
    id: 4,
    ip_address: '192.168.1.50',
    username: 'john',
    status: 'success' as const,
    attempt_time: new Date(Date.now() - 8 * 60 * 60 * 1000).toISOString(),
  },
];

export const sampleLockouts = [
  {
    id: 1,
    ip_address: '45.33.32.156',
    attempts: 5,
    lockout_until: new Date(Date.now() + 25 * 60 * 1000).toISOString(),
  },
];

// Sample Sequences data
export const sampleSequences = [
  {
    id: 1,
    name: 'Welcome Series',
    description: 'Onboarding sequence for new subscribers',
    trigger_type: 'contact_created',
    trigger_value: '',
    status: 'active' as const,
    active_subscribers: 145,
    completed_subscribers: 892,
    created_at: new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 2,
    name: 'Re-engagement Campaign',
    description: 'Win back inactive subscribers',
    trigger_type: 'tag_added',
    trigger_value: 'inactive',
    status: 'active' as const,
    active_subscribers: 67,
    completed_subscribers: 234,
    created_at: new Date(Date.now() - 45 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 3,
    name: 'Product Launch',
    description: 'Promotional sequence for new product',
    trigger_type: 'manual',
    trigger_value: '',
    status: 'draft' as const,
    active_subscribers: 0,
    completed_subscribers: 0,
    created_at: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString(),
  },
];

export const sampleSequenceDetail = {
  id: 1,
  name: 'Welcome Series',
  description: 'Onboarding sequence for new subscribers',
  trigger_type: 'contact_created',
  trigger_value: '',
  status: 'active' as const,
  stats: {
    active: 145,
    completed: 892,
    total_emails_sent: 3245,
  },
  emails: [
    {
      id: 1,
      sequence_id: 1,
      subject: 'Welcome to Peanut Suite!',
      body: '<p>Thank you for signing up! Here\'s what you need to know to get started...</p>',
      delay_days: 0,
      delay_hours: 0,
      status: 'active',
      created_at: new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString(),
    },
    {
      id: 2,
      sequence_id: 1,
      subject: 'Getting the most out of UTM tracking',
      body: '<p>In this email, we\'ll show you how to set up effective UTM campaigns...</p>',
      delay_days: 2,
      delay_hours: 0,
      status: 'active',
      created_at: new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString(),
    },
    {
      id: 3,
      sequence_id: 1,
      subject: 'Have questions? We\'re here to help',
      body: '<p>We noticed you\'ve been exploring the platform. Need any assistance?</p>',
      delay_days: 5,
      delay_hours: 0,
      status: 'active',
      created_at: new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString(),
    },
  ],
};

export const sampleSequenceSubscribers = [
  {
    id: 1,
    email: 'subscriber1@example.com',
    status: 'active',
    emails_sent: 2,
    enrolled_at: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 2,
    email: 'subscriber2@example.com',
    status: 'completed',
    emails_sent: 3,
    enrolled_at: new Date(Date.now() - 10 * 24 * 60 * 60 * 1000).toISOString(),
  },
  {
    id: 3,
    email: 'subscriber3@example.com',
    status: 'active',
    emails_sent: 1,
    enrolled_at: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
  },
];

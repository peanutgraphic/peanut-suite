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

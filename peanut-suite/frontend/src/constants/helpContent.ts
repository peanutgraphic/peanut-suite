/**
 * Centralized help content for tooltips and contextual help throughout the app.
 * This keeps all help text in one place for easy maintenance and consistency.
 */

export const helpContent = {
  // ============================================
  // UTM Builder
  // ============================================
  utm: {
    baseUrl: 'The destination URL where you want to send traffic. This is the page users will land on after clicking your link.',

    source: 'Identifies WHERE your traffic comes from. Examples: google, facebook, newsletter, twitter. This appears in analytics as the referrer.',

    medium: 'Identifies HOW the traffic arrives. Examples: cpc (paid ads), organic (search), social (social media), email (email campaigns).',

    campaign: 'The specific campaign name. Use consistent naming like "summer_sale_2024" or "product_launch_q1". This groups all related links together.',

    term: 'Optional. Used mainly for paid search to identify the keywords you\'re bidding on. Example: "running+shoes" for a Google Ads campaign.',

    content: 'Optional. Differentiates similar content or links within the same campaign. Use to A/B test ads, like "banner_v1" vs "banner_v2".',

    program: 'Optional internal field. Use to track initiatives, teams, or programs that don\'t fit standard UTM parameters.',
  },

  // ============================================
  // Short Links
  // ============================================
  links: {
    shortUrl: 'A shorter, branded version of your long URL. Easier to share and looks more professional in social media and print.',

    slug: 'The custom part after your domain. Leave blank for auto-generated, or create memorable slugs like "summer-sale" or "webinar".',

    clickCount: 'Total number of times this link has been clicked. Each unique visitor click is counted.',

    status: 'Active links redirect normally. Inactive links show an error page. Use to temporarily disable links without deleting them.',

    qrCode: 'A scannable QR code for your short link. Perfect for print materials, presentations, or in-person events.',
  },

  // ============================================
  // Contacts
  // ============================================
  contacts: {
    overview: 'Contacts are people who have interacted with your site through forms, popups, or purchases. Track their entire journey.',

    status: 'Lead = new contact, Subscriber = opted into emails, Customer = made a purchase, Inactive = no recent activity.',

    source: 'How this contact first found you. Captured from UTM parameters, referrer, or form data.',

    score: 'Engagement score based on activity. Higher scores indicate more engaged contacts who may be ready to convert.',

    tags: 'Labels to organize contacts. Use for segmentation like "VIP", "Newsletter", or "Webinar Attendee".',

    lifecycle: 'Track where contacts are in your funnel: Awareness → Consideration → Decision → Customer → Advocate.',
  },

  // ============================================
  // Visitors
  // ============================================
  visitors: {
    overview: 'Anonymous and identified visitors to your tracked pages. See device info, location, and browsing behavior.',

    identified: 'Visitors who have provided their email through a form, popup, or purchase. You know who they are.',

    anonymous: 'Visitors tracked by a cookie but haven\'t identified themselves yet. Convert them with popups or forms.',

    visitorId: 'A unique identifier for each visitor. Persists across sessions until they clear cookies.',

    sessions: 'A session is a single visit. A new session starts after 30 minutes of inactivity.',

    pageviews: 'Total pages viewed across all sessions. High pageviews indicate engaged visitors.',

    trackingCode: 'JavaScript snippet to add to your site. Place before </body> on all pages you want to track.',
  },

  // ============================================
  // Attribution
  // ============================================
  attribution: {
    overview: 'See the complete customer journey from first touch to conversion. Understand which channels drive results.',

    firstTouch: 'The first interaction that brought a visitor to your site. Credits the channel that created awareness.',

    lastTouch: 'The final interaction before conversion. Credits the channel that closed the deal.',

    linear: 'Divides credit equally across all touchpoints. Useful when every interaction matters.',

    timeDecay: 'Gives more credit to recent touchpoints. Useful for short sales cycles.',

    touchpoints: 'Each interaction a contact has with your marketing: ad clicks, email opens, page visits, etc.',

    conversionWindow: 'How far back to look for touchpoints. Default 30 days. Longer windows for high-consideration purchases.',
  },

  // ============================================
  // Analytics
  // ============================================
  analytics: {
    overview: 'Deep dive into your marketing performance. See trends, compare periods, and identify what\'s working.',

    clicks: 'Total clicks on your tracked links. Includes UTM links and short links.',

    conversions: 'Completed goals: form submissions, purchases, or custom events you\'ve defined.',

    conversionRate: 'Percentage of clicks that result in conversions. Higher is better. Industry average varies by sector.',

    topSources: 'Your best-performing traffic sources. Focus budget and effort on what\'s working.',

    trends: 'Performance over time. Spot seasonal patterns, campaign impact, and growth trajectory.',
  },

  // ============================================
  // Popups
  // ============================================
  popups: {
    overview: 'Create engaging popups to capture leads, announce promotions, or guide visitors. Fully customizable.',

    trigger: 'When the popup appears: on page load, after time delay, on scroll, on exit intent, or on click.',

    exitIntent: 'Detects when a visitor is about to leave and shows the popup. Great for capturing abandoning visitors.',

    targeting: 'Show popups to specific audiences: new vs returning visitors, specific pages, referral sources, etc.',

    frequency: 'How often a visitor sees the popup. "Once per session", "Once per week", or "Always" for critical announcements.',

    views: 'How many times the popup has been displayed to visitors.',

    conversions: 'How many times visitors completed the popup action (submitted form, clicked button).',

    conversionRate: 'Percentage of popup views that resulted in conversions. Aim for 3-10% for email capture.',
  },

  // ============================================
  // Monitor (Agency)
  // ============================================
  monitor: {
    overview: 'Monitor multiple WordPress sites from one dashboard. Track health, updates, and uptime.',

    health: 'Overall site status based on PHP errors, plugin conflicts, and update status. Green = healthy.',

    uptime: 'Percentage of time the site is accessible. 99.9% uptime = about 8 hours downtime per year.',

    updates: 'Pending WordPress core, plugin, and theme updates. Keep sites updated for security.',

    connector: 'The Peanut Connect plugin installed on remote sites. Enables secure communication with your dashboard.',

    lastSync: 'When the site last reported its status. If stale, the connector may need attention.',
  },

  // ============================================
  // Settings
  // ============================================
  settings: {
    domain: 'Custom domain for short links. Requires DNS configuration to point to your WordPress site.',

    timezone: 'Used for analytics and scheduled features. Set to your primary audience\'s timezone.',

    license: 'Your Peanut Suite license key. Enter to unlock Pro or Agency features.',

    integrations: 'Connect third-party services like Google Analytics, Mailchimp, or ConvertKit.',

    dataExport: 'Download all your data (UTMs, links, contacts, popups) as CSV for backup or migration.',

    dataImport: 'Import data from CSV files. Useful for migrating from other tools.',

    cache: 'Cached data speeds up the dashboard. Clear if you see stale data after making changes.',
  },

  // ============================================
  // Dashboard
  // ============================================
  dashboard: {
    utmCodes: 'Total UTM-tagged URLs you\'ve created. Each tracks a specific campaign or traffic source.',

    shortLinks: 'Total short links created. Includes active and inactive links.',

    contacts: 'People in your contact database. Includes leads, subscribers, and customers.',

    popups: 'Total popups you\'ve created. Includes active and paused popups.',

    recentActivity: 'Latest actions across all your marketing tools. Monitor for issues or opportunities.',

    performance: 'Click and conversion trends over time. Compare different periods to spot growth.',
  },

  // ============================================
  // Webhooks
  // ============================================
  webhooks: {
    overview: 'Receive data from external services when events happen. Connect forms, payment processors, or CRMs.',

    endpoint: 'Your unique webhook URL. Give this to external services to send data to your site.',

    secret: 'A security key to verify webhook requests are authentic. Keep this private.',

    payload: 'The data received from the external service. Usually JSON format.',

    status: 'Whether the webhook was processed successfully. Check failed webhooks for issues.',
  },
};

export type HelpCategory = keyof typeof helpContent;

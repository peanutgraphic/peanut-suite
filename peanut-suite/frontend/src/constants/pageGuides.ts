/**
 * Page Guides - Step-by-step feature explainers for first-time users
 * Each guide provides:
 * - Welcome title and overview
 * - Numbered steps with icons
 * - Expected outcomes
 * - Troubleshooting tips
 */

export interface GuideStep {
  icon: string; // Lucide icon name
  title: string;
  description: string;
  expected?: string; // "What you'll see"
  troubleshoot?: string; // "If X happens, try Y"
}

export interface PageGuide {
  id: string;
  title: string;
  subtitle: string;
  steps: GuideStep[];
}

export const pageGuides: Record<string, PageGuide> = {
  // ============================================
  // DASHBOARD
  // ============================================
  dashboard: {
    id: 'dashboard',
    title: 'Welcome to Your Dashboard',
    subtitle: 'Your central hub for tracking all marketing activities at a glance.',
    steps: [
      {
        icon: 'BarChart2',
        title: 'View Your Stats',
        description: 'The top row shows your key metrics: UTM codes created, short links, contacts, and popup views. These update in real-time as you use the plugin.',
        expected: 'You\'ll see number cards with icons. If you\'re new, these will show zeros or sample data.',
        troubleshoot: 'If stats show "Loading...", refresh the page. If they stay at zero after adding data, clear your browser cache.',
      },
      {
        icon: 'Zap',
        title: 'Use Quick Actions',
        description: 'The Quick Actions panel on the right lets you jump straight to common tasks: create a UTM link, generate a short link, or build a popup.',
        expected: 'Clicking any quick action takes you to that feature\'s page with a form ready to fill out.',
      },
      {
        icon: 'TrendingUp',
        title: 'Monitor Recent Activity',
        description: 'Scroll down to see your recent links, latest contacts, and upcoming scheduled popups. This gives you a quick overview without navigating away.',
        expected: 'Recent items appear in cards. Click any item to view its full details.',
        troubleshoot: 'If you see "No recent activity", start by creating a UTM link or short link.',
      },
      {
        icon: 'BookOpen',
        title: 'Explore Features',
        description: 'Use the sidebar navigation to explore each feature. Pro tip: The sidebar shows which features are included in your license tier.',
        expected: 'Features you can access are highlighted. Locked features show a badge indicating the required tier.',
      },
    ],
  },

  // ============================================
  // UTM BUILDER
  // ============================================
  utm: {
    id: 'utm',
    title: 'Welcome to UTM Builder',
    subtitle: 'Create trackable URLs with UTM parameters to understand where your traffic comes from.',
    steps: [
      {
        icon: 'Link2',
        title: 'Enter Your Destination URL',
        description: 'Paste the URL you want to track (like your landing page or product page). This is where visitors will end up after clicking your campaign link.',
        expected: 'The URL field accepts any valid web address starting with http:// or https://.',
        troubleshoot: 'If you get an "invalid URL" error, make sure to include the full address with https://.',
      },
      {
        icon: 'Target',
        title: 'Fill in Required Fields',
        description: 'Source (where traffic comes from, e.g., "facebook"), Medium (marketing type, e.g., "social"), and Campaign (your campaign name, e.g., "summer-sale") are required.',
        expected: 'As you type, the preview URL at the bottom updates in real-time showing your UTM parameters.',
        troubleshoot: 'Use lowercase letters and hyphens instead of spaces. This keeps your analytics clean and consistent.',
      },
      {
        icon: 'Plus',
        title: 'Add Optional Details',
        description: 'Term (for paid keywords) and Content (to differentiate links in the same campaign) are optional but help with detailed tracking.',
        expected: 'These fields expand when clicked and add extra parameters to your URL.',
      },
      {
        icon: 'Copy',
        title: 'Copy and Use Your Link',
        description: 'Click "Generate UTM URL" then copy the result. Use this link in your ads, emails, or social posts. All clicks will be tracked with your campaign data.',
        expected: 'A success message confirms the URL was copied. The link is also saved to your UTM history.',
        troubleshoot: 'If the copy button doesn\'t work, manually select and copy the URL from the preview area.',
      },
    ],
  },

  // ============================================
  // SHORT LINKS
  // ============================================
  links: {
    id: 'links',
    title: 'Welcome to Short Links',
    subtitle: 'Create branded, trackable short links that are easy to share and remember.',
    steps: [
      {
        icon: 'Plus',
        title: 'Create Your First Link',
        description: 'Click "Add Link" and paste a long URL. Peanut Suite will generate a short, trackable version you can share anywhere.',
        expected: 'A modal opens with fields for your destination URL and optional custom slug.',
        troubleshoot: 'If you can\'t create links, check your license status in Settings.',
      },
      {
        icon: 'Edit2',
        title: 'Customize Your Slug',
        description: 'Instead of a random code, create memorable slugs like "summer-sale" or "podcast". Custom slugs help with brand recognition.',
        expected: 'The slug preview shows your full short URL (e.g., yoursite.com/go/summer-sale).',
        troubleshoot: 'If a slug is taken, you\'ll see an error. Try adding numbers or being more specific.',
      },
      {
        icon: 'BarChart2',
        title: 'Track Click Performance',
        description: 'Each link shows total clicks, unique visitors, and click-through trends. Click a link row to see detailed analytics.',
        expected: 'You\'ll see a graph of clicks over time plus geographic and device breakdowns.',
      },
      {
        icon: 'QrCode',
        title: 'Generate QR Codes',
        description: 'Every short link has a QR code button. Generate scannable codes for print materials, event signage, or business cards.',
        expected: 'A QR code modal appears with download options for PNG or SVG formats.',
      },
    ],
  },

  // ============================================
  // CONTACTS
  // ============================================
  contacts: {
    id: 'contacts',
    title: 'Welcome to Contacts',
    subtitle: 'Manage your leads and customers in one place. Track their journey from first click to conversion.',
    steps: [
      {
        icon: 'Users',
        title: 'View Your Contacts',
        description: 'All captured contacts appear here with their email, status, source, and tags. Use filters to find specific segments.',
        expected: 'Contact cards show key info at a glance. Click any contact for full details.',
        troubleshoot: 'If you don\'t see contacts, they\'re captured when visitors fill out forms or make purchases.',
      },
      {
        icon: 'Tag',
        title: 'Organize with Tags',
        description: 'Add tags to group contacts by interest, status, or campaign. Tags help you segment your audience for targeted follow-ups.',
        expected: 'Tags appear as colored badges. You can filter by tag using the dropdown.',
      },
      {
        icon: 'Filter',
        title: 'Use Advanced Filters',
        description: 'Filter by status (lead, customer, subscriber), source (where they came from), date range, or custom tags.',
        expected: 'The contact list updates instantly as you apply filters.',
        troubleshoot: 'If filters seem stuck, click "Clear Filters" to reset.',
      },
      {
        icon: 'Download',
        title: 'Export Your Data',
        description: 'Use the Export button to download contacts as CSV. You can export all contacts or just your filtered selection.',
        expected: 'A CSV file downloads with all contact fields, ready for email marketing tools.',
      },
    ],
  },

  // ============================================
  // WEBHOOKS
  // ============================================
  webhooks: {
    id: 'webhooks',
    title: 'Welcome to Webhooks',
    subtitle: 'Send data automatically to external services when events happen on your site.',
    steps: [
      {
        icon: 'Plus',
        title: 'Create a Webhook',
        description: 'Click "Add Webhook" to set up a new endpoint. You\'ll need the URL from your receiving service (like Zapier, Make, or your own server).',
        expected: 'A form asks for the webhook URL and which events should trigger it.',
        troubleshoot: 'Make sure your endpoint URL is accessible and returns a 200 status.',
      },
      {
        icon: 'Bell',
        title: 'Choose Event Types',
        description: 'Select which events trigger the webhook: new contact, form submission, popup conversion, link click, or purchase.',
        expected: 'Check the events you want. Each event sends different data to your endpoint.',
      },
      {
        icon: 'Play',
        title: 'Test Your Webhook',
        description: 'Use the "Test" button to send sample data to your endpoint. This verifies the connection without waiting for real events.',
        expected: 'A success message if your endpoint responds correctly. Error details if something fails.',
        troubleshoot: 'Common issues: wrong URL, endpoint not accepting POST requests, authentication required.',
      },
      {
        icon: 'Activity',
        title: 'Monitor Deliveries',
        description: 'The delivery log shows every webhook call with status, timestamp, and response. Failed deliveries are highlighted for retry.',
        expected: 'Green checkmarks for successful deliveries. Red X for failures with error details.',
      },
    ],
  },

  // ============================================
  // VISITORS
  // ============================================
  visitors: {
    id: 'visitors',
    title: 'Welcome to Visitors',
    subtitle: 'See who visits your site and track their journey from anonymous visitor to known contact.',
    steps: [
      {
        icon: 'Eye',
        title: 'View Visitor Sessions',
        description: 'Every visitor is tracked with a unique ID. See their pages viewed, time on site, and how they found you.',
        expected: 'Visitor cards show IP location, device type, and session history.',
        troubleshoot: 'Visitors only appear after the tracking script is active on your site.',
      },
      {
        icon: 'UserCheck',
        title: 'Identify Visitors',
        description: 'When a visitor fills out a form or makes a purchase, they become "identified" and linked to their contact record.',
        expected: 'Identified visitors show their name/email instead of just "Anonymous Visitor".',
      },
      {
        icon: 'Route',
        title: 'Track Conversion Paths',
        description: 'See the exact pages a visitor viewed before converting. This helps you understand which content drives action.',
        expected: 'A visual path shows entry page → pages visited → conversion point.',
      },
      {
        icon: 'Clock',
        title: 'Monitor Real-Time Activity',
        description: 'The "Active Now" section shows visitors currently on your site. See what pages they\'re viewing live.',
        expected: 'Active sessions update every few seconds. Page changes appear in real-time.',
        troubleshoot: 'Real-time tracking requires the Pro tier or higher.',
      },
    ],
  },

  // ============================================
  // ATTRIBUTION
  // ============================================
  attribution: {
    id: 'attribution',
    title: 'Welcome to Attribution',
    subtitle: 'Understand which touchpoints lead to conversions with multi-touch attribution modeling.',
    steps: [
      {
        icon: 'GitBranch',
        title: 'Understand Attribution Models',
        description: 'First-touch credits the first interaction. Last-touch credits the final one. Linear spreads credit equally. Choose based on your goals.',
        expected: 'A model selector lets you switch views. Results change to show each model\'s perspective.',
      },
      {
        icon: 'Layers',
        title: 'View Multi-Touch Journeys',
        description: 'Most customers interact multiple times before converting. See all touchpoints that contributed to each conversion.',
        expected: 'Journey visualizations show each channel (email, social, paid) with relative contribution.',
        troubleshoot: 'Attribution requires sufficient conversion data. New accounts show sample data until real conversions occur.',
      },
      {
        icon: 'PieChart',
        title: 'Compare Channels',
        description: 'See how different marketing channels perform side-by-side. Compare by conversions, revenue, or assist rate.',
        expected: 'Bar and pie charts break down performance by source, medium, or campaign.',
      },
      {
        icon: 'TrendingUp',
        title: 'Optimize Your Mix',
        description: 'Use attribution insights to adjust your marketing budget. Invest more in channels with higher conversion impact.',
        expected: 'Recommendations appear based on your attribution data patterns.',
      },
    ],
  },

  // ============================================
  // ANALYTICS
  // ============================================
  analytics: {
    id: 'analytics',
    title: 'Welcome to Analytics',
    subtitle: 'Deep dive into your marketing performance with comprehensive reports and insights.',
    steps: [
      {
        icon: 'Calendar',
        title: 'Set Your Date Range',
        description: 'Use the date picker to analyze specific time periods. Compare to previous periods to spot trends.',
        expected: 'Charts and metrics update to show data for your selected timeframe.',
        troubleshoot: 'If data seems incomplete, you may be looking at a period before tracking was active.',
      },
      {
        icon: 'BarChart2',
        title: 'Explore Traffic Sources',
        description: 'See where your visitors come from: organic search, social media, email, paid ads, or direct traffic.',
        expected: 'A breakdown chart shows percentage and count from each source.',
      },
      {
        icon: 'Target',
        title: 'Track Conversions',
        description: 'Monitor conversion events like form submissions, purchases, or signup completions. See conversion rates by source.',
        expected: 'Conversion metrics show total events, rates, and trends over time.',
      },
      {
        icon: 'Download',
        title: 'Export Reports',
        description: 'Download any report as CSV or PDF. Schedule automated reports to your email for regular updates.',
        expected: 'Export options appear in the top-right of each report section.',
      },
    ],
  },

  // ============================================
  // POPUPS
  // ============================================
  popups: {
    id: 'popups',
    title: 'Welcome to Popups',
    subtitle: 'Create engaging popups to capture leads, announce promotions, or guide visitors.',
    steps: [
      {
        icon: 'Plus',
        title: 'Create a Popup',
        description: 'Click "New Popup" to open the builder. Choose from templates or start with a blank canvas.',
        expected: 'The popup editor opens with design controls, content fields, and preview.',
      },
      {
        icon: 'Settings',
        title: 'Configure Triggers',
        description: 'Set when the popup appears: on page load, after delay, on scroll, on exit intent, or on click.',
        expected: 'Trigger options let you fine-tune timing and conditions.',
        troubleshoot: 'Exit intent only works on desktop browsers. For mobile, use scroll or time-based triggers.',
      },
      {
        icon: 'Target',
        title: 'Set Targeting Rules',
        description: 'Choose who sees the popup: all visitors, specific pages, returning visitors, or UTM-tagged traffic.',
        expected: 'Targeting rules stack together. A visitor must match all rules to see the popup.',
      },
      {
        icon: 'BarChart2',
        title: 'Track Performance',
        description: 'View impressions, conversions, and conversion rate for each popup. A/B test different versions.',
        expected: 'Stats update in real-time. Compare popup variants to find winners.',
        troubleshoot: 'If impressions are low, check your targeting rules - they may be too restrictive.',
      },
    ],
  },

  // ============================================
  // MONITOR
  // ============================================
  monitor: {
    id: 'monitor',
    title: 'Welcome to Site Monitor',
    subtitle: 'Keep track of all your WordPress sites, plugin updates, and health status from one dashboard.',
    steps: [
      {
        icon: 'Plus',
        title: 'Add a Site',
        description: 'Click "Add Site" and enter the URL of any WordPress site running Peanut Connect. The plugin links the site automatically.',
        expected: 'The site appears in your list with connection status. Green = connected.',
        troubleshoot: 'Make sure Peanut Connect is installed and activated on the remote site.',
      },
      {
        icon: 'Shield',
        title: 'Check Site Health',
        description: 'Each site shows health indicators: WordPress version, PHP version, SSL status, and any security concerns.',
        expected: 'Green checkmarks for healthy items. Yellow warnings for outdated components.',
      },
      {
        icon: 'RefreshCw',
        title: 'Manage Updates',
        description: 'See available plugin and theme updates across all sites. Update individually or in bulk from here.',
        expected: 'Update counts show in badges. Click to see details and trigger updates.',
        troubleshoot: 'Remote updates require the site owner to enable auto-updates in Peanut Connect.',
      },
      {
        icon: 'Bell',
        title: 'Set Up Notifications',
        description: 'Get email alerts when sites go down, have security issues, or need critical updates.',
        expected: 'Notification settings are per-site. Choose what triggers an alert.',
      },
    ],
  },

  // ============================================
  // SECURITY
  // ============================================
  security: {
    id: 'security',
    title: 'Welcome to Security',
    subtitle: 'Protect your WordPress login with brute force protection and monitor security events.',
    steps: [
      {
        icon: 'Shield',
        title: 'Enable Login Protection',
        description: 'Turn on brute force protection to limit failed login attempts. Attackers get locked out after too many failures.',
        expected: 'A toggle enables protection. Configure max attempts and lockout duration below.',
        troubleshoot: 'If you lock yourself out, wait for the lockout period or access via FTP to disable.',
      },
      {
        icon: 'Lock',
        title: 'View Lockouts',
        description: 'See IPs that have been locked out, when, and why. Manually unlock trusted IPs if needed.',
        expected: 'A table shows locked IPs with unlock buttons for each.',
      },
      {
        icon: 'FileText',
        title: 'Review Audit Log',
        description: 'Every login attempt, settings change, and security event is logged. Use this for troubleshooting or compliance.',
        expected: 'Searchable log with timestamps, usernames, IPs, and action descriptions.',
      },
      {
        icon: 'UserX',
        title: 'Manage Blocklists',
        description: 'Permanently block specific IPs or IP ranges from accessing your login page.',
        expected: 'Add IPs to the blocklist. They\'ll be denied before even reaching the login form.',
        troubleshoot: 'Be careful not to block your own IP. Use the whitelist to ensure you\'re never locked out.',
      },
    ],
  },

  // ============================================
  // TEAM
  // ============================================
  team: {
    id: 'team',
    title: 'Welcome to Team Management',
    subtitle: 'Invite team members and control what features they can access.',
    steps: [
      {
        icon: 'UserPlus',
        title: 'Invite a Team Member',
        description: 'Click "Add Member" and enter their email. They\'ll receive an invitation to join your Peanut Suite account.',
        expected: 'The invitation is sent immediately. They\'ll appear as "pending" until they accept.',
        troubleshoot: 'If they don\'t receive the email, check spam folders or resend the invitation.',
      },
      {
        icon: 'Shield',
        title: 'Assign Roles',
        description: 'Choose a role: Owner (full access), Admin (manage features), Member (use features), or Viewer (read-only).',
        expected: 'Role badges appear next to each team member\'s name.',
      },
      {
        icon: 'Key',
        title: 'Set Feature Permissions',
        description: 'For Members and Viewers, toggle which specific features they can access. Restrict sensitive areas like Settings.',
        expected: 'A checklist of features appears. Toggle each one on or off.',
      },
      {
        icon: 'LogOut',
        title: 'Remove Team Members',
        description: 'Use the menu to remove someone\'s access. They lose access immediately but their previous actions remain logged.',
        expected: 'A confirmation dialog appears. Confirm to remove their access.',
      },
    ],
  },

  // ============================================
  // SETTINGS
  // ============================================
  settings: {
    id: 'settings',
    title: 'Welcome to Settings',
    subtitle: 'Configure your Peanut Suite installation, license, and integrations.',
    steps: [
      {
        icon: 'Settings',
        title: 'General Settings',
        description: 'Set your site URL, timezone, and default tracking options. These affect how data is collected and displayed.',
        expected: 'Form fields for each setting. Changes save automatically or via Save button.',
      },
      {
        icon: 'Key',
        title: 'License Management',
        description: 'View your license status, tier (Free, Pro, Agency), and expiration. Upgrade or renew from here.',
        expected: 'License details show with upgrade/renew buttons based on your current tier.',
        troubleshoot: 'If your license shows as invalid, try re-entering the key or contact support.',
      },
      {
        icon: 'Plug',
        title: 'Integrations',
        description: 'Connect third-party services: email marketing, CRM, analytics platforms, and more.',
        expected: 'Integration cards show connected status. Click to configure credentials.',
      },
      {
        icon: 'Code',
        title: 'API Access',
        description: 'Generate API keys for custom integrations. Each key has scopes controlling what it can access.',
        expected: 'API keys appear in a table. Create new keys with the button above.',
        troubleshoot: 'API keys are shown once on creation. Store them securely - they cannot be retrieved later.',
      },
    ],
  },

  // ============================================
  // KEYWORDS (SEO)
  // ============================================
  keywords: {
    id: 'keywords',
    title: 'Welcome to Keyword Rankings',
    subtitle: 'Track your search engine positions for important keywords over time.',
    steps: [
      {
        icon: 'Plus',
        title: 'Add Keywords to Track',
        description: 'Click "Add Keyword" and enter the search term you want to rank for. Optionally specify a target URL.',
        expected: 'Keywords appear in the list. Initial rank check is queued automatically.',
        troubleshoot: 'Rank checking requires API configuration in Settings.',
      },
      {
        icon: 'TrendingUp',
        title: 'Monitor Position Changes',
        description: 'See current position, previous position, and change. Green arrows mean improvement; red means decline.',
        expected: 'Position history builds over time. Click a keyword for detailed history.',
      },
      {
        icon: 'Globe',
        title: 'Choose Search Engine & Location',
        description: 'Track rankings in Google, Bing, or Yahoo. Set geographic location for localized results.',
        expected: 'Dropdown selections for engine and country/region.',
      },
      {
        icon: 'RefreshCw',
        title: 'Refresh Rankings',
        description: 'Click "Check Rankings" to update all keyword positions. This uses your API quota.',
        expected: 'Rankings update one by one. Progress shows during the check.',
        troubleshoot: 'If rankings don\'t update, verify your API key is valid and has quota remaining.',
      },
    ],
  },

  // ============================================
  // BACKLINKS (SEO)
  // ============================================
  backlinks: {
    id: 'backlinks',
    title: 'Welcome to Backlinks',
    subtitle: 'Discover and monitor websites linking to your content for SEO insights.',
    steps: [
      {
        icon: 'Search',
        title: 'Discover Backlinks',
        description: 'Click "Discover New" to scan for sites linking to yours. This uses external APIs to find links.',
        expected: 'New backlinks appear in the list with source domain, anchor text, and link type.',
      },
      {
        icon: 'CheckCircle',
        title: 'Verify Active Links',
        description: 'Click "Verify All" to check if existing backlinks are still active. Lost or broken links are flagged.',
        expected: 'Status badges show Active (green), Lost (red), or Broken (yellow).',
        troubleshoot: 'Verification can take time for large link profiles. Results are cached.',
      },
      {
        icon: 'Award',
        title: 'Check Domain Authority',
        description: 'Each backlink shows the source domain\'s authority score. Higher DA links are more valuable for SEO.',
        expected: 'DA scores appear in a column. Sort by DA to find your most valuable links.',
      },
      {
        icon: 'Filter',
        title: 'Filter by Type',
        description: 'Filter by dofollow (SEO value), nofollow, or other types. Focus on dofollow for link building.',
        expected: 'Type badges are color-coded. Use the dropdown to filter the list.',
      },
    ],
  },

  // ============================================
  // SEQUENCES
  // ============================================
  sequences: {
    id: 'sequences',
    title: 'Welcome to Email Sequences',
    subtitle: 'Create automated email drip campaigns that nurture leads over time.',
    steps: [
      {
        icon: 'Plus',
        title: 'Create a Sequence',
        description: 'Click "New" to create a sequence. Name it descriptively like "Welcome Series" or "Abandoned Cart".',
        expected: 'A new sequence appears in the sidebar list. Click it to add emails.',
      },
      {
        icon: 'Mail',
        title: 'Add Emails',
        description: 'Click "Add Email" within a sequence. Write your subject line and body content. Set delay time from previous email.',
        expected: 'Emails appear numbered in order. The first email typically sends immediately.',
        troubleshoot: 'Emails won\'t send until the sequence is activated and has enrolled contacts.',
      },
      {
        icon: 'Clock',
        title: 'Set Timing',
        description: 'Configure delays between emails: 1 day, 3 days, 1 week, etc. This creates your drip cadence.',
        expected: 'Each email card shows "Immediately" or "X days after previous".',
      },
      {
        icon: 'UserPlus',
        title: 'Enroll Contacts',
        description: 'Manually enroll contacts or set triggers (form submission, tag added) to auto-enroll new leads.',
        expected: 'Subscribers appear in the sequence with progress through the email series.',
      },
    ],
  },

  // ============================================
  // WOOCOMMERCE
  // ============================================
  woocommerce: {
    id: 'woocommerce',
    title: 'Welcome to Revenue Attribution',
    subtitle: 'Track which marketing campaigns drive WooCommerce sales and revenue.',
    steps: [
      {
        icon: 'ShoppingCart',
        title: 'View Order Attribution',
        description: 'Every WooCommerce order is automatically tracked with its marketing source. See which campaigns drive sales.',
        expected: 'Order list shows UTM source, medium, and campaign alongside order value.',
        troubleshoot: 'Orders only show attribution if customers clicked a tracked link before purchasing.',
      },
      {
        icon: 'DollarSign',
        title: 'Track Revenue by Source',
        description: 'See total revenue broken down by marketing channel: social, email, paid ads, organic, etc.',
        expected: 'Revenue charts show contribution from each source. Compare performance easily.',
      },
      {
        icon: 'TrendingUp',
        title: 'Calculate ROI',
        description: 'Compare ad spend to revenue generated. See which campaigns deliver positive return on investment.',
        expected: 'ROI metrics appear when you connect ad spend data.',
      },
      {
        icon: 'Download',
        title: 'Export Reports',
        description: 'Download revenue attribution reports for accounting, stakeholder updates, or deeper analysis.',
        expected: 'Export button generates CSV with all order and attribution data.',
      },
    ],
  },

  // ============================================
  // PERFORMANCE
  // ============================================
  performance: {
    id: 'performance',
    title: 'Welcome to Performance',
    subtitle: 'Monitor Core Web Vitals and PageSpeed Insights scores for your site.',
    steps: [
      {
        icon: 'Gauge',
        title: 'Check Overall Scores',
        description: 'See your site\'s Performance, Accessibility, Best Practices, and SEO scores from Google PageSpeed.',
        expected: 'Circular score indicators show 0-100 ratings. Green (90+) is great, yellow (50-89) needs work, red (<50) is poor.',
      },
      {
        icon: 'Smartphone',
        title: 'Toggle Mobile vs Desktop',
        description: 'Switch between mobile and desktop views. Google primarily uses mobile scores for ranking.',
        expected: 'Scores often differ between views. Mobile is typically lower due to bandwidth simulation.',
        troubleshoot: 'If mobile scores are very low, focus on image optimization and reduced JavaScript.',
      },
      {
        icon: 'Activity',
        title: 'Understand Core Web Vitals',
        description: 'LCP (loading), FID (interactivity), and CLS (visual stability) are Google\'s key metrics. All three should be green.',
        expected: 'Metric cards show values with color coding. Hover for explanations.',
      },
      {
        icon: 'RefreshCw',
        title: 'Schedule Checks',
        description: 'Set up automatic daily or weekly performance checks. Get notified when scores drop.',
        expected: 'Configure in Settings. Historical data builds over time to show trends.',
      },
    ],
  },
};

// Helper to get a guide by page ID
export function getPageGuide(pageId: string): PageGuide | undefined {
  return pageGuides[pageId];
}

// List of all available guide IDs
export const pageGuideIds = Object.keys(pageGuides);

/**
 * Rich page descriptions with titles, subtitles, and how-to content
 */

export interface PageDescription {
  title: string;
  subtitle: string;
  description: string;
  howTo: {
    title: string;
    steps: string[];
  };
  tips?: string[];
  useCases?: {
    title: string;
    examples: string[];
  };
}

export const pageDescriptions: Record<string, PageDescription> = {
  dashboard: {
    title: 'Dashboard',
    subtitle: 'Your marketing command center',
    description: 'Get a bird\'s eye view of your marketing performance. Track UTM campaigns, link clicks, contact growth, and popup conversions all in one place.',
    howTo: {
      title: 'Getting Started',
      steps: [
        'Create your first UTM link to start tracking campaign performance',
        'Set up a popup to capture leads on your site',
        'Import existing contacts or connect a form to build your database',
        'Check back here daily to monitor your marketing health',
      ],
    },
    tips: [
      'Click any stat card to dive deeper into that feature',
      'Recent activity shows the last 24 hours of actions',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Monitor daily marketing performance at a glance',
        'Quickly spot which campaigns are driving the most traffic',
        'Track lead capture trends across all your popups',
        'Get early warning when something needs attention',
      ],
    },
  },

  utm: {
    title: 'UTM Builder',
    subtitle: 'Create trackable campaign links in seconds',
    description: 'Build UTM-tagged URLs to track exactly where your traffic comes from. Know which campaigns, ads, and channels drive real results.',
    howTo: {
      title: 'How to Create a UTM Link',
      steps: [
        'Enter your destination URL (the page visitors will land on)',
        'Fill in the Source (e.g., facebook, google, newsletter)',
        'Add the Medium (e.g., cpc, social, email)',
        'Name your Campaign for easy tracking',
        'Click Generate to create your trackable link',
      ],
    },
    tips: [
      'Use consistent naming conventions across all campaigns',
      'Save templates for campaigns you run regularly',
      'The generated link works immediately—no setup needed',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Track which Facebook ads bring the most conversions',
        'Compare email newsletter performance across sends',
        'Measure ROI on Google Ads campaigns',
        'See which social posts drive the most traffic',
        'A/B test landing pages with different UTM content tags',
      ],
    },
  },

  links: {
    title: 'Short Links',
    subtitle: 'Branded, trackable links that look professional',
    description: 'Create clean, memorable short links that track every click. Perfect for social media, print materials, and anywhere character count matters.',
    howTo: {
      title: 'How to Create a Short Link',
      steps: [
        'Paste your long URL in the destination field',
        'Optionally customize the slug (e.g., /summer-sale)',
        'Click Create to generate your short link',
        'Copy and share—clicks are tracked automatically',
      ],
    },
    tips: [
      'Custom slugs are easier to remember and share verbally',
      'Download QR codes for print materials',
      'Combine with UTM parameters for complete tracking',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Share clean links on Twitter/X without ugly parameters',
        'Create memorable links for podcast mentions',
        'Generate QR codes for business cards and flyers',
        'Track clicks from print ads and offline marketing',
        'Use branded short domains for professional appearance',
      ],
    },
  },

  contacts: {
    title: 'Contacts',
    subtitle: 'Your leads and customers in one place',
    description: 'Manage everyone who interacts with your business. Track where they came from, what they\'re interested in, and move them through your funnel.',
    howTo: {
      title: 'How to Manage Contacts',
      steps: [
        'Contacts are added automatically from popups and forms',
        'Use tags to organize contacts (e.g., "VIP", "Newsletter")',
        'Click a contact to see their full journey and activity',
        'Export contacts anytime for email marketing or CRM import',
      ],
    },
    tips: [
      'Set up webhooks to sync contacts with your email provider',
      'Filter by source to see which campaigns bring the best leads',
      'Engagement scores help identify your hottest prospects',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Build targeted email lists from popup subscribers',
        'Track which campaigns generate the highest-value leads',
        'Segment contacts by source for personalized follow-up',
        'Export leads to Mailchimp, ConvertKit, or your CRM',
        'Identify your most engaged prospects for sales outreach',
      ],
    },
  },

  webhooks: {
    title: 'Webhooks',
    subtitle: 'Connect external services to your marketing stack',
    description: 'Receive real-time data from forms, payment processors, and other services. Automatically create contacts and trigger actions when events happen.',
    howTo: {
      title: 'How to Set Up a Webhook',
      steps: [
        'Click "Create Webhook" to generate a unique endpoint URL',
        'Copy the URL and paste it into your external service',
        'Configure which events should trigger the webhook',
        'Test by submitting data—it will appear here instantly',
      ],
    },
    tips: [
      'Use the webhook secret to verify requests are authentic',
      'Map incoming fields to contact properties automatically',
      'Check the log to troubleshoot failed webhooks',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Auto-create contacts when someone fills out a Typeform',
        'Log purchases from Stripe or WooCommerce as conversions',
        'Connect Zapier to trigger actions in other apps',
        'Receive leads from Facebook Lead Ads automatically',
        'Sync FormFlow submissions with your contact database',
      ],
    },
  },

  visitors: {
    title: 'Visitors',
    subtitle: 'See who\'s browsing your site right now',
    description: 'Track anonymous and identified visitors across your site. Understand browsing behavior, see which pages they visit, and convert them into contacts.',
    howTo: {
      title: 'How Visitor Tracking Works',
      steps: [
        'Add the tracking code to your site (Settings → Tracking)',
        'Visitors are tracked automatically by a cookie',
        'When they submit a form, they become "identified"',
        'View their complete browsing history and session data',
      ],
    },
    tips: [
      'Anonymous visitors can still be targeted with popups',
      'High pageview counts indicate engaged prospects',
      'Session data shows exactly how visitors navigate your site',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'See which pages visitors view before converting',
        'Identify high-intent visitors who return multiple times',
        'Understand how people navigate your site',
        'Find visitors who spent the most time on pricing pages',
        'Connect anonymous browsing to identified contacts',
      ],
    },
  },

  attribution: {
    title: 'Attribution',
    subtitle: 'Understand the complete customer journey',
    description: 'See every touchpoint that leads to a conversion. Know which channels create awareness, which nurture interest, and which close the deal.',
    howTo: {
      title: 'How to Use Attribution',
      steps: [
        'Conversions are tracked automatically from forms and purchases',
        'Each conversion shows all touchpoints in the journey',
        'Compare attribution models to understand channel value',
        'Use insights to optimize your marketing spend',
      ],
    },
    tips: [
      'First-touch shows what creates awareness',
      'Last-touch shows what closes deals',
      'Linear attribution values every interaction equally',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Justify ad spend by showing full conversion paths',
        'Discover that blog posts drive more conversions than you thought',
        'See how email nurturing contributes to sales',
        'Compare channel value using different attribution models',
        'Optimize budget allocation based on real data',
      ],
    },
  },

  analytics: {
    title: 'Analytics',
    subtitle: 'Deep insights into marketing performance',
    description: 'Analyze your marketing data with powerful reports. See trends over time, compare periods, identify top performers, and make data-driven decisions.',
    howTo: {
      title: 'How to Read Your Analytics',
      steps: [
        'Select a date range to focus on a specific period',
        'Compare with previous periods to spot trends',
        'Drill down into specific sources or campaigns',
        'Export reports for presentations or deeper analysis',
      ],
    },
    tips: [
      'Weekly comparisons reveal day-of-week patterns',
      'Filter by medium to compare channel performance',
      'Set up email reports for automated updates',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Create monthly marketing reports for stakeholders',
        'Spot seasonal trends to plan campaigns',
        'Compare this month vs last month at a glance',
        'Identify your best-performing traffic sources',
        'Track conversion rate improvements over time',
      ],
    },
  },

  popups: {
    title: 'Popups',
    subtitle: 'Capture leads and boost conversions',
    description: 'Create beautiful popups that convert visitors into subscribers and customers. Customize triggers, targeting, and design to maximize engagement.',
    howTo: {
      title: 'How to Create an Effective Popup',
      steps: [
        'Click "Create Popup" and choose a template',
        'Customize the headline, message, and call-to-action',
        'Set the trigger (time delay, scroll, exit intent)',
        'Target specific pages or visitor segments',
        'Publish and watch the conversions roll in',
      ],
    },
    tips: [
      'Exit-intent popups capture abandoning visitors',
      '5-10 second delay performs better than immediate',
      'Offer something valuable in exchange for emails',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Capture emails with a 10% discount offer popup',
        'Show exit-intent popups to reduce cart abandonment',
        'Announce sales and promotions site-wide',
        'Collect feedback with a quick survey popup',
        'Offer lead magnets (ebooks, guides) in exchange for emails',
      ],
    },
  },

  monitor: {
    title: 'Site Monitor',
    subtitle: 'Manage all your WordPress sites from one dashboard',
    description: 'Monitor health, track updates, and ensure uptime across your entire WordPress network. Perfect for agencies and multi-site managers.',
    howTo: {
      title: 'How to Add a Site to Monitor',
      steps: [
        'Install Peanut Connect on the remote WordPress site',
        'Copy the Site Key from the plugin settings',
        'Click "Add Site" and paste the Site Key',
        'The site will sync automatically within minutes',
      ],
    },
    tips: [
      'Health scores below 80% need attention',
      'Keep WordPress and plugins updated for security',
      'Set up alerts for downtime notifications',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Monitor all your client sites from one dashboard',
        'Get alerts when a site goes down',
        'Track plugin updates across your entire network',
        'Spot security vulnerabilities before they\'re exploited',
        'Generate health reports for client meetings',
      ],
    },
  },

  settings: {
    title: 'Settings',
    subtitle: 'Configure Marketing Suite to fit your workflow',
    description: 'Customize domains, integrations, tracking, and more. Set up everything once and let the automation do the rest.',
    howTo: {
      title: 'Essential Settings to Configure',
      steps: [
        'Set your timezone for accurate analytics',
        'Configure your custom short link domain',
        'Connect integrations (email, analytics, CRM)',
        'Enter your license key to unlock Pro features',
      ],
    },
    tips: [
      'Export your data regularly for backups',
      'Clear cache if you see stale data',
      'Check integrations status monthly',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Set up a custom branded domain for short links',
        'Connect your email provider for lead syncing',
        'Configure tracking preferences and data retention',
        'Upgrade to Pro or Agency for more features',
        'Export all your data for backup or migration',
      ],
    },
  },

  security: {
    title: 'Security',
    subtitle: 'Protect your WordPress login and admin area',
    description: 'Shield your site from brute force attacks with custom login URLs, attempt limiting, IP management, and two-factor authentication.',
    howTo: {
      title: 'How to Secure Your Login',
      steps: [
        'Enable "Hide Login URL" to replace wp-login.php with a custom slug',
        'Bookmark your new login URL before enabling',
        'Turn on "Limit Login Attempts" to block repeated failures',
        'Add your IP to the whitelist to prevent self-lockout',
        'Enable 2FA for administrator accounts',
      ],
    },
    tips: [
      'Use a unique login slug that bots won\'t guess',
      'Keep your own IP address whitelisted',
      'Enable login notifications to monitor suspicious activity',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Hide wp-login.php from bot attacks',
        'Block IPs after too many failed login attempts',
        'Require 2FA for all admin users',
        'Get notified when someone logs in or is locked out',
        'Permanently block malicious IP addresses',
      ],
    },
  },

  backlinks: {
    title: 'Backlinks',
    subtitle: 'Discover and monitor sites linking to you',
    description: 'Track who\'s linking to your content, get alerts when links are lost, and build a complete picture of your backlink profile.',
    howTo: {
      title: 'How to Track Backlinks',
      steps: [
        'Click "Discover New" to find sites linking to you',
        'Use "Verify All" to check if existing links are still active',
        'Monitor the Lost section for valuable links that disappeared',
        'Export your backlink profile for SEO analysis',
      ],
    },
    tips: [
      'Focus on dofollow links for SEO value',
      'Set up alerts to know immediately when links are lost',
      'High Domain Authority links are most valuable',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Monitor your link building progress over time',
        'Get alerts when valuable backlinks disappear',
        'Identify which content attracts the most links',
        'Compare your backlink profile to competitors',
        'Track guest post and PR campaign results',
      ],
    },
  },

  sequences: {
    title: 'Email Sequences',
    subtitle: 'Automated email drip campaigns for leads',
    description: 'Create automated email sequences that nurture leads, onboard customers, and drive engagement without manual effort.',
    howTo: {
      title: 'How to Create an Email Sequence',
      steps: [
        'Click "New" to create a sequence',
        'Set a trigger (manual, contact created, tag added)',
        'Add emails with specific delays between them',
        'Activate the sequence to start enrolling contacts',
        'Monitor subscribers and completion rates',
      ],
    },
    tips: [
      '2-3 day delays between emails usually work best',
      'Keep subject lines short and compelling',
      'Test with a small group before full rollout',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Welcome new subscribers with an automated series',
        'Onboard new customers with helpful tips',
        'Re-engage inactive contacts with a win-back sequence',
        'Nurture leads toward a purchase decision',
        'Follow up after webinars or events automatically',
      ],
    },
  },

  keywords: {
    title: 'Keyword Rankings',
    subtitle: 'Track your search engine positions',
    description: 'Monitor where your pages rank for important keywords. Track position changes, spot opportunities, and measure SEO progress.',
    howTo: {
      title: 'How to Track Keywords',
      steps: [
        'Click "Add Keyword" to start tracking a term',
        'Enter the keyword and target URL (or homepage)',
        'Select the search engine and location',
        'Rankings are checked daily automatically',
        'Click any keyword to see position history',
      ],
    },
    tips: [
      'Focus on keywords you can realistically rank for',
      'Track both branded and non-branded terms',
      'Configure DataForSEO API for accurate data',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Monitor your top 10 most important keywords',
        'Track progress after publishing new content',
        'Get alerts when rankings drop significantly',
        'Measure the impact of SEO changes',
        'Compare rankings across different locations',
      ],
    },
  },

  woocommerce: {
    title: 'Revenue Attribution',
    subtitle: 'Track WooCommerce revenue by campaign',
    description: 'See exactly which marketing campaigns drive sales. Connect UTM data to orders and measure true marketing ROI.',
    howTo: {
      title: 'How Revenue Attribution Works',
      steps: [
        'UTM parameters are captured when visitors arrive',
        'When they purchase, the order is attributed to that campaign',
        'Revenue is grouped by source, medium, and campaign',
        'View attributed orders in the table below',
      ],
    },
    tips: [
      'Use consistent UTM naming across all campaigns',
      'Compare revenue by channel to optimize spend',
      'Check attribution rate to ensure tracking is working',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'See which Facebook ads generate the most revenue',
        'Calculate ROI on Google Ads campaigns',
        'Compare email vs social media sales',
        'Identify your highest-value traffic sources',
        'Prove marketing value with revenue data',
      ],
    },
  },

  performance: {
    title: 'Performance',
    subtitle: 'Monitor Core Web Vitals and PageSpeed scores',
    description: 'Track your site\'s performance with Google PageSpeed Insights. Monitor LCP, FID, CLS, and other Core Web Vitals to ensure a fast, smooth user experience.',
    howTo: {
      title: 'How to Track Performance',
      steps: [
        'Add URLs you want to monitor in Settings',
        'Run a check to get PageSpeed Insights data',
        'View scores for Performance, Accessibility, Best Practices, and SEO',
        'Review Core Web Vitals: LCP, FID, and CLS',
        'Check opportunities and diagnostics for improvement tips',
      ],
    },
    tips: [
      'Aim for a Performance score of 90+ for best results',
      'LCP should be under 2.5 seconds for good UX',
      'CLS should be under 0.1 to avoid layout shifts',
      'Add a Google API key for higher rate limits',
    ],
    useCases: {
      title: 'What You Can Do Here',
      examples: [
        'Monitor Core Web Vitals across your key pages',
        'Track performance improvements over time',
        'Identify slow-loading pages before they hurt SEO',
        'Get actionable recommendations to speed up your site',
        'Compare mobile vs desktop performance',
      ],
    },
  },
};

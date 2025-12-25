# Peanut Monitor - Architecture Document

## Overview

Peanut Monitor is a centralized WordPress site management tool that allows users to monitor, maintain, and manage multiple WordPress sites from a single dashboard.

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         MANAGER SITE                                     │
│                    (peanutgraphic.com or client's hub)                  │
│                                                                          │
│   ┌─────────────────────────────────────────────────────────────────┐   │
│   │                    PEANUT SUITE + MONITOR                        │   │
│   │                                                                  │   │
│   │  ┌──────────────────────────────────────────────────────────┐   │   │
│   │  │                 MONITOR DASHBOARD                         │   │   │
│   │  │                                                           │   │   │
│   │  │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐     │   │   │
│   │  │  │ Site 1  │  │ Site 2  │  │ Site 3  │  │ Site N  │     │   │   │
│   │  │  │ ● Online│  │ ● Online│  │ ○ Down  │  │ ● Online│     │   │   │
│   │  │  └─────────┘  └─────────┘  └─────────┘  └─────────┘     │   │   │
│   │  │                                                           │   │   │
│   │  │  [Updates Available: 12]  [Health Issues: 2]             │   │   │
│   │  │                                                           │   │   │
│   │  │  Aggregated Analytics:                                    │   │   │
│   │  │  - Total Contacts: 1,234 across all sites                │   │   │
│   │  │  - UTM Clicks: 5,678 this month                          │   │   │
│   │  │  - Link Clicks: 9,012 this month                         │   │   │
│   │  └──────────────────────────────────────────────────────────┘   │   │
│   │                                                                  │   │
│   └─────────────────────────────────────────────────────────────────┘   │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
            │                    │                    │
            │ REST API           │ REST API           │ REST API
            │ (Authenticated)    │                    │
            ▼                    ▼                    ▼
┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐
│    CHILD SITE 1   │  │    CHILD SITE 2   │  │    CHILD SITE N   │
│                   │  │                   │  │                   │
│ ┌───────────────┐ │  │ ┌───────────────┐ │  │ ┌───────────────┐ │
│ │Peanut Connect │ │  │ │Peanut Connect │ │  │ │Peanut Connect │ │
│ │  (Connector)  │ │  │ │  (Connector)  │ │  │ │  (Connector)  │ │
│ └───────────────┘ │  │ └───────────────┘ │  │ └───────────────┘ │
│                   │  │                   │  │                   │
│ Peanut Suite      │  │ Peanut Suite      │  │ (Any WP site)     │
│ (optional)        │  │ (optional)        │  │                   │
└───────────────────┘  └───────────────────┘  └───────────────────┘
```

## Components

### 1. Peanut Monitor (Manager Plugin/Module)

Lives on the "hub" site. Can be:
- A module within Peanut Suite (for Pro/Agency users)
- Standalone plugin for non-Peanut users

**Features:**
- Site registration and management
- Centralized dashboard
- Bulk update management
- Health monitoring
- Uptime monitoring
- Aggregated Peanut Suite analytics

### 2. Peanut Connect (Child Plugin)

Lightweight plugin installed on managed sites.

**Features:**
- Secure API endpoint for manager communication
- Reports site health, updates, stats
- Executes commands from manager (updates, etc.)
- Syncs Peanut Suite data if installed

---

## Data Flow

### Site Health Check
```
Manager                              Child Site
   │                                      │
   │──── GET /peanut-connect/v1/health ──►│
   │                                      │
   │◄─── { wp_version, php_version,  ─────│
   │       plugins, themes, disk_space,   │
   │       last_backup, ssl_status }      │
   │                                      │
```

### Plugin Updates
```
Manager                              Child Site
   │                                      │
   │──── GET /peanut-connect/v1/updates ─►│
   │                                      │
   │◄─── { plugins: [...], themes: [...] }│
   │                                      │
   │──── POST /peanut-connect/v1/update ─►│
   │      { type: 'plugin',               │
   │        slug: 'akismet' }             │
   │                                      │
   │◄─── { success: true, new_version }───│
```

### Peanut Suite Data Sync
```
Manager                              Child Site (with Peanut Suite)
   │                                      │
   │── GET /peanut-connect/v1/analytics ─►│
   │                                      │
   │◄── { contacts_count, utm_clicks,  ───│
   │      links_clicks, recent_leads }    │
   │                                      │
```

---

## Security Model

### Authentication

Each child site has a unique **Site Key** generated on connection:

```php
// On child site
$site_key = wp_generate_password(64, false);
update_option('peanut_connect_site_key', $site_key);

// All requests must include
Authorization: Bearer {site_key}
X-Peanut-Manager: {manager_site_url}
```

### Request Verification

```php
// Child site verifies request
class Peanut_Connect_Auth {
    public function verify_request(WP_REST_Request $request): bool {
        $provided_key = $request->get_header('Authorization');
        $stored_key = get_option('peanut_connect_site_key');

        // Timing-safe comparison
        return hash_equals('Bearer ' . $stored_key, $provided_key);
    }
}
```

### Permissions

Manager can only perform actions the child site allows:

```php
// Child site settings
$allowed_actions = [
    'health_check' => true,      // Always allowed
    'list_updates' => true,      // Always allowed
    'perform_updates' => true,   // Can disable
    'access_analytics' => true,  // Can disable
    'manage_users' => false,     // Disabled by default
];
```

---

## Database Schema

### Manager Site Tables

```sql
-- Connected sites
CREATE TABLE {prefix}peanut_monitor_sites (
    id bigint(20) UNSIGNED AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED NOT NULL,
    site_url varchar(255) NOT NULL,
    site_name varchar(255),
    site_key_hash varchar(64) NOT NULL,  -- Hashed for security
    status enum('active', 'disconnected', 'error') DEFAULT 'active',
    last_check datetime,
    last_health longtext,  -- JSON: health data
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY site_url (user_id, site_url)
);

-- Site health history
CREATE TABLE {prefix}peanut_monitor_health_log (
    id bigint(20) UNSIGNED AUTO_INCREMENT,
    site_id bigint(20) UNSIGNED NOT NULL,
    status enum('healthy', 'warning', 'critical', 'offline'),
    checks longtext,  -- JSON: individual check results
    checked_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY site_id (site_id),
    KEY checked_at (checked_at)
);

-- Uptime monitoring
CREATE TABLE {prefix}peanut_monitor_uptime (
    id bigint(20) UNSIGNED AUTO_INCREMENT,
    site_id bigint(20) UNSIGNED NOT NULL,
    status enum('up', 'down'),
    response_time int,  -- milliseconds
    status_code int,
    checked_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY site_id (site_id),
    KEY checked_at (checked_at)
);

-- Aggregated analytics (cached)
CREATE TABLE {prefix}peanut_monitor_analytics (
    id bigint(20) UNSIGNED AUTO_INCREMENT,
    site_id bigint(20) UNSIGNED NOT NULL,
    period varchar(10) NOT NULL,  -- 'day', 'week', 'month'
    period_start date NOT NULL,
    metrics longtext NOT NULL,  -- JSON: { contacts, utm_clicks, etc. }
    synced_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY site_period (site_id, period, period_start)
);
```

### Child Site Tables

Minimal - just stores connection info:

```sql
-- Stored in options table
peanut_connect_site_key      -- Authentication key
peanut_connect_manager_url   -- Manager site URL
peanut_connect_permissions   -- What manager can do
peanut_connect_last_sync     -- Last successful sync
```

---

## Features Detail

### 1. Site Management

**Add Site Flow:**
1. User enters child site URL in Monitor dashboard
2. Monitor displays connection instructions
3. User installs Peanut Connect on child site
4. Child site generates site key
5. User enters site key in Monitor
6. Connection established and verified

**Site Card Display:**
```
┌─────────────────────────────────────────────────────────┐
│  clientsite.com                              ● Online   │
├─────────────────────────────────────────────────────────┤
│  WP 6.4.2  │  PHP 8.2  │  SSL ✓  │  Last backup: 2d    │
├─────────────────────────────────────────────────────────┤
│  ⚠ 3 plugin updates   ⚠ 1 theme update                 │
├─────────────────────────────────────────────────────────┤
│  Peanut Suite: ✓ Active                                │
│  - 45 contacts this month                              │
│  - 230 link clicks                                     │
│  - 12 UTM campaigns active                             │
├─────────────────────────────────────────────────────────┤
│  [Update All]  [View Details]  [Disconnect]            │
└─────────────────────────────────────────────────────────┘
```

### 2. Health Monitoring

**Checks performed:**
- WordPress version (is it current?)
- PHP version (meets requirements?)
- Plugin/theme updates available
- Disk space usage
- Database size
- SSL certificate status & expiry
- Debug mode status
- File permissions
- Last backup date (if backup plugin detected)

**Health Score:**
```
Score: 85/100 (Good)

✓ WordPress up to date
✓ PHP 8.2 (recommended)
✓ SSL valid (expires in 45 days)
⚠ 3 plugins need updates
⚠ Debug mode enabled
✗ No recent backup detected
```

### 3. Uptime Monitoring

**Cron-based checks:**
- Check every 5 minutes (configurable)
- Alert on downtime (email, Slack webhook)
- Track response times
- 30-day uptime history

**Display:**
```
Uptime: 99.95% (last 30 days)
Avg Response: 245ms

[████████████████████░]
 ↑ 2 incidents this month
```

### 4. Bulk Updates

**Features:**
- See all updates across all sites
- Update individual plugins/themes
- Bulk update same plugin across sites
- Update queue with progress
- Rollback support (if backup available)

**Update Flow:**
```
1. User selects updates to perform
2. Monitor queues updates
3. For each site:
   a. Create restore point (if enabled)
   b. Send update command
   c. Verify update success
   d. Report result
4. Summary displayed
```

### 5. Aggregated Analytics (Peanut Suite Integration)

**When child site has Peanut Suite:**
- Pull contact counts
- Pull UTM click data
- Pull link click data
- Aggregate across all sites

**Dashboard widgets:**
```
┌─────────────────────────────────────────────────────────┐
│  PORTFOLIO OVERVIEW (5 sites)                           │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Total Contacts     UTM Clicks      Link Clicks        │
│  ┌─────────┐       ┌─────────┐     ┌─────────┐        │
│  │  1,234  │       │  5,678  │     │  9,012  │        │
│  │  +12%   │       │  +8%    │     │  +15%   │        │
│  └─────────┘       └─────────┘     └─────────┘        │
│                                                         │
│  Top Performing Site: clientA.com (456 contacts)       │
│  Most Active Campaign: spring-sale (1,200 clicks)      │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## API Endpoints

### Manager Site (Monitor Module)

```
GET  /peanut/v1/monitor/sites              # List connected sites
POST /peanut/v1/monitor/sites              # Add new site
GET  /peanut/v1/monitor/sites/{id}         # Get site details
DEL  /peanut/v1/monitor/sites/{id}         # Disconnect site
POST /peanut/v1/monitor/sites/{id}/check   # Force health check
POST /peanut/v1/monitor/sites/{id}/update  # Trigger update

GET  /peanut/v1/monitor/updates            # All pending updates
POST /peanut/v1/monitor/updates/bulk       # Bulk update

GET  /peanut/v1/monitor/analytics          # Aggregated analytics
GET  /peanut/v1/monitor/uptime             # Uptime overview
```

### Child Site (Peanut Connect)

```
GET  /peanut-connect/v1/health             # Site health data
GET  /peanut-connect/v1/updates            # Available updates
POST /peanut-connect/v1/update             # Perform update
GET  /peanut-connect/v1/analytics          # Peanut Suite data
POST /peanut-connect/v1/verify             # Verify connection
```

---

## Peanut Connect (Child Plugin)

### Minimal Footprint

The child plugin should be lightweight:

```
peanut-connect/
├── peanut-connect.php       # Main plugin file (~200 lines)
├── includes/
│   ├── class-api.php        # REST endpoints
│   ├── class-health.php     # Health checks
│   └── class-updates.php    # Update handler
└── readme.txt
```

**No admin UI needed** - just installs and works. Configuration via:
1. WP-CLI commands
2. Constants in wp-config.php
3. Simple settings page (optional)

### Auto-Discovery

If Peanut Suite is installed, Connect automatically:
- Detects it
- Exposes analytics endpoints
- Reports Peanut data to manager

```php
class Peanut_Connect {
    public function get_peanut_suite_data(): ?array {
        if (!function_exists('peanut_is_module_active')) {
            return null;  // Peanut Suite not installed
        }

        // Return analytics data
        return [
            'installed' => true,
            'version' => PEANUT_VERSION,
            'modules' => peanut_get_active_modules(),
            'stats' => $this->get_peanut_stats(),
        ];
    }
}
```

---

## Integration with Peanut Suite

### As a Module (Pro/Agency)

Monitor can be a module within Peanut Suite for Pro/Agency users:

```php
// In peanut-suite core
$this->module_manager->register('monitor', [
    'name' => __('Monitor', 'peanut-suite'),
    'description' => __('Manage multiple WordPress sites from one dashboard.', 'peanut-suite'),
    'icon' => 'monitor',
    'file' => PEANUT_PLUGIN_DIR . 'modules/monitor/class-monitor-module.php',
    'class' => 'Monitor_Module',
    'default' => false,
    'pro' => true,  // Requires Pro license
]);
```

### As Standalone Add-on

For users who just want site management without other Peanut features:

```
peanut-monitor/           # Standalone plugin
├── peanut-monitor.php
├── includes/
│   ├── class-monitor.php
│   ├── class-sites.php
│   ├── class-health.php
│   ├── class-updates.php
│   └── api/
└── admin/
```

---

## Pricing Position

| User Type | Solution | Price |
|-----------|----------|-------|
| Single site owner | Peanut Suite Pro | $99/year |
| Small agency (5-10 sites) | Peanut Suite Agency | $299/year (includes Monitor) |
| Large agency (25+ sites) | Peanut Monitor Standalone | $199/year |
| Enterprise | Custom | Contact |

---

## Competitive Landscape

| Product | Price | Sites | Notes |
|---------|-------|-------|-------|
| MainWP | Free + $29-199/ext | Unlimited | Most full-featured |
| ManageWP | Free-$150/month | Per site | GoDaddy owned |
| InfiniteWP | $147-397 one-time | Unlimited | Self-hosted |
| Jetstpack | $5-50/month | 1 site | Limited management |
| **Peanut Monitor** | $299/year (Agency) | 25 sites | + Peanut Suite features |

**Differentiation:**
- Integrated with Peanut Suite analytics
- Aggregated marketing data across sites
- Simpler than MainWP
- More affordable than ManageWP at scale

---

## Development Phases

### Phase 1: Core Monitor
- Site registration
- Basic health checks
- Plugin/theme update listing
- Manual update execution

### Phase 2: Automation
- Scheduled health checks
- Uptime monitoring
- Email alerts
- Update scheduling

### Phase 3: Peanut Integration
- Analytics aggregation
- Cross-site campaign tracking
- Unified contact view

### Phase 4: Advanced
- Backup integration
- Performance monitoring
- Security scanning
- White-label reports

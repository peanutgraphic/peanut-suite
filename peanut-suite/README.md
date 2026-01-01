# Peanut Suite

A modular WordPress marketing toolkit plugin with a React-powered dashboard. Peanut Suite provides UTM tracking, link shortening, contact management, popup builders, and multi-site monitoring—all from a unified interface.

## Features

### Core Modules

| Module | Tier | Description |
|--------|------|-------------|
| **UTM Builder** | Free | Create and manage UTM-tagged URLs with analytics tracking |
| **Short Links** | Free | Branded short links with click tracking and QR codes |
| **Contacts** | Free | Contact management with tagging, status tracking, and import/export |
| **Popups** | Pro | Conversion popups with multiple trigger types and analytics |
| **Monitor** | Agency | Multi-site management with health checks and uptime monitoring |

### Key Capabilities

- **License-based Feature Gating**: Free, Pro, and Agency tiers with feature unlocking
- **Modern React Dashboard**: Fast, responsive SPA built with React 18 and TypeScript
- **REST API**: Full CRUD operations for all modules via WordPress REST API
- **Google Analytics Integration**: Connect GA4 for UTM performance tracking
- **Export/Import**: CSV and PDF exports for all data types

## Requirements

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Node.js 18+ (for frontend development)

## Installation

### From WordPress Admin

1. Download the latest release ZIP
2. Go to **Plugins > Add New > Upload Plugin**
3. Upload and activate the plugin
4. Navigate to **Peanut Suite** in the admin menu

### From Source

```bash
# Clone the repository
git clone https://github.com/your-org/peanut-suite.git

# Install PHP dependencies
cd peanut-suite
composer install

# Build the frontend
cd frontend
npm install
npm run build

# The plugin is now ready - copy to wp-content/plugins/
```

## Directory Structure

```
peanut-suite/
├── peanut-suite.php              # Main plugin bootstrap
├── composer.json                 # PHP dependencies
├── uninstall.php                 # Cleanup on uninstall
│
├── core/                         # Core plugin functionality
│   ├── class-peanut-core.php     # Main plugin class
│   ├── class-peanut-activator.php
│   ├── class-peanut-deactivator.php
│   ├── class-peanut-loader.php
│   │
│   ├── admin/                    # Admin functionality
│   │   ├── class-peanut-admin.php
│   │   └── class-peanut-module-manager.php
│   │
│   ├── api/                      # REST API base
│   │   └── class-peanut-rest-controller.php
│   │
│   ├── database/                 # Database management
│   │   └── class-peanut-database.php
│   │
│   └── services/                 # Shared services
│       ├── class-peanut-encryption.php
│       └── class-peanut-license.php
│
├── modules/                      # Feature modules
│   ├── utm/                      # UTM Builder module
│   │   ├── class-utm-module.php
│   │   ├── class-utm-database.php
│   │   └── api/
│   │       └── class-utm-controller.php
│   │
│   ├── links/                    # Short Links module
│   │   ├── class-links-module.php
│   │   ├── class-links-database.php
│   │   ├── class-links-redirect.php
│   │   └── api/
│   │       └── class-links-controller.php
│   │
│   ├── contacts/                 # Contacts module
│   │   ├── class-contacts-module.php
│   │   ├── class-contacts-database.php
│   │   └── api/
│   │       └── class-contacts-controller.php
│   │
│   ├── popups/                   # Popups module (Pro)
│   │   ├── class-popups-module.php
│   │   ├── class-popups-database.php
│   │   ├── class-popups-triggers.php
│   │   ├── class-popups-renderer.php
│   │   ├── api/
│   │   │   └── class-popups-controller.php
│   │   └── assets/
│   │       ├── popups.js
│   │       └── popups.css
│   │
│   └── monitor/                  # Monitor module (Agency)
│       ├── class-monitor-module.php
│       ├── class-monitor-database.php
│       ├── class-monitor-sites.php
│       ├── class-monitor-health.php
│       └── api/
│           └── class-monitor-controller.php
│
├── frontend/                     # React SPA
│   ├── package.json
│   ├── vite.config.ts
│   ├── tsconfig.json
│   └── src/
│       ├── main.tsx              # Entry point
│       ├── App.tsx               # Router setup
│       ├── index.css             # Tailwind imports
│       │
│       ├── api/                  # API client
│       │   ├── client.ts         # Axios instance
│       │   └── endpoints.ts      # API functions
│       │
│       ├── components/
│       │   ├── common/           # Reusable UI components
│       │   │   ├── Button.tsx
│       │   │   ├── Input.tsx
│       │   │   ├── Card.tsx
│       │   │   ├── Select.tsx
│       │   │   ├── Modal.tsx
│       │   │   ├── Badge.tsx
│       │   │   ├── Table.tsx
│       │   │   ├── Pagination.tsx
│       │   │   └── EmptyState.tsx
│       │   │
│       │   └── layout/           # Layout components
│       │       ├── Layout.tsx
│       │       ├── Sidebar.tsx
│       │       └── Header.tsx
│       │
│       ├── pages/                # Page components
│       │   ├── Dashboard.tsx
│       │   ├── UTMBuilder.tsx
│       │   ├── UTMLibrary.tsx
│       │   ├── Links.tsx
│       │   ├── Contacts.tsx
│       │   ├── Popups.tsx
│       │   └── Settings.tsx
│       │
│       ├── store/                # Zustand stores
│       │   ├── useAppStore.ts
│       │   ├── useUTMStore.ts
│       │   └── useFilterStore.ts
│       │
│       └── types/                # TypeScript definitions
│           └── index.ts
│
└── assets/
    └── dist/                     # Built frontend (generated)
```

## Development

### Quick Start

```bash
# Install frontend dependencies
npm run install:frontend

# Start development server
npm run dev
```

### NPM Scripts

#### Development
| Script | Description |
|--------|-------------|
| `npm run dev` | Start Vite development server |
| `npm run build` | Build frontend for production |
| `npm run install:frontend` | Install frontend dependencies |

#### Version Management
| Script | Description |
|--------|-------------|
| `npm run bump` | Bump patch version (default) |
| `npm run bump:patch` | Bump patch version (x.y.z → x.y.z+1) |
| `npm run bump:minor` | Bump minor version (x.y.z → x.y+1.0) |
| `npm run bump:major` | Bump major version (x.y.z → x+1.0.0) |

Version is updated across all files:
- `peanut-suite.php` (header, PEANUT_VERSION, PEANUT_SUITE_VERSION)
- `package.json`
- `frontend/package.json`
- `docs/openapi.yaml`

#### Deployment
| Script | Description |
|--------|-------------|
| `npm run deploy` | Deploy to peanutgraphic server via rsync |

#### Release Workflow
| Script | Description |
|--------|-------------|
| `npm run release:patch` | Full release with patch bump |
| `npm run release:minor` | Full release with minor bump |
| `npm run release:major` | Full release with major bump |

Release workflow automates:
1. Bump version across all files
2. Commit and push to GitHub
3. Create distributable zip package
4. Create GitHub release with zip asset
5. Deploy to peanutgraphic server

#### Packaging
| Script | Description |
|--------|-------------|
| `npm run clean` | Remove dist folder and zip files |
| `npm run package` | Build and create zip package |
| `npm run release` | Clean, build, and package |

### Frontend Development

```bash
cd frontend

# Install dependencies
npm install

# Start development server with hot reload
npm run dev

# Type checking
npm run typecheck

# Production build
npm run build
```

The dev server runs at `http://localhost:5173`. For WordPress integration, ensure your WordPress installation has the plugin activated and CORS configured for development.

### Backend Development

The plugin follows WordPress coding standards and uses PSR-4 autoloading via Composer.

```bash
# Install dependencies
composer install

# Run PHP CodeSniffer (if configured)
composer run phpcs
```

### Creating a New Module

1. Create module directory in `modules/your-module/`
2. Create main class following the module pattern:

```php
<?php
namespace PeanutSuite\Modules\YourModule;

class YourModule_Module {
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    public function get_config(): array {
        return [
            'name' => 'Your Module',
            'slug' => 'your-module',
            'description' => 'Module description',
            'version' => '1.0.0',
            'tier' => 'free', // free, pro, or agency
        ];
    }
}
```

3. Register in `core/class-peanut-core.php`:

```php
$this->modules['your-module'] = [
    'name' => 'Your Module',
    'class' => 'PeanutSuite\\Modules\\YourModule\\YourModule_Module',
    'file' => PEANUT_SUITE_PATH . 'modules/your-module/class-your-module.php',
    'tier' => 'free',
];
```

## REST API

### Authentication

All API endpoints require WordPress nonce authentication:

```javascript
// Nonce is passed via X-WP-Nonce header
axios.defaults.headers.common['X-WP-Nonce'] = window.peanutSuite.nonce;
```

### Base URL

`/wp-json/peanut-suite/v1`

### UTM Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/utms` | List UTMs (paginated) |
| POST | `/utms` | Create UTM |
| GET | `/utms/{id}` | Get single UTM |
| PUT | `/utms/{id}` | Update UTM |
| DELETE | `/utms/{id}` | Delete UTM |
| POST | `/utms/bulk-delete` | Bulk delete UTMs |
| GET | `/utms/export` | Export as CSV |

### Links Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/links` | List links |
| POST | `/links` | Create short link |
| GET | `/links/{id}` | Get link details |
| PUT | `/links/{id}` | Update link |
| DELETE | `/links/{id}` | Delete link |
| GET | `/links/{id}/stats` | Get click statistics |

### Contacts Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/contacts` | List contacts |
| POST | `/contacts` | Create contact |
| GET | `/contacts/{id}` | Get contact |
| PUT | `/contacts/{id}` | Update contact |
| DELETE | `/contacts/{id}` | Delete contact |
| POST | `/contacts/import` | Bulk import |
| GET | `/contacts/export` | Export as CSV |
| GET | `/contacts/{id}/activities` | Get activity timeline |

### Popups Endpoints (Pro)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/popups` | List popups |
| POST | `/popups` | Create popup |
| GET | `/popups/{id}` | Get popup |
| PUT | `/popups/{id}` | Update popup |
| DELETE | `/popups/{id}` | Delete popup |
| GET | `/popups/{id}/stats` | Get performance stats |
| POST | `/popups/duplicate/{id}` | Duplicate popup |

### Monitor Endpoints (Agency)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/monitor/sites` | List connected sites |
| POST | `/monitor/sites` | Add site |
| DELETE | `/monitor/sites/{id}` | Remove site |
| GET | `/monitor/sites/{id}/health` | Get health status |
| POST | `/monitor/sites/{id}/refresh` | Refresh health data |
| GET | `/monitor/sites/{id}/uptime` | Get uptime history |
| POST | `/monitor/sites/{id}/updates` | Trigger plugin/theme updates |

### Query Parameters

Most list endpoints support:

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | int | Page number (default: 1) |
| `per_page` | int | Items per page (default: 20, max: 100) |
| `search` | string | Search term |
| `sort_by` | string | Field to sort by |
| `sort_order` | string | `asc` or `desc` |
| `status` | string | Filter by status |

## Frontend Tech Stack

| Package | Version | Purpose |
|---------|---------|---------|
| React | 18.3 | UI framework |
| TypeScript | 5.5 | Type safety |
| Vite | 5.4 | Build tool |
| Tailwind CSS | 4.0 | Styling |
| TanStack Query | 5.51 | Data fetching & caching |
| TanStack Table | 8.19 | Data tables |
| Zustand | 4.5 | State management |
| React Router | 6.26 | Client-side routing |
| Axios | 1.7 | HTTP client |
| Lucide React | 0.424 | Icons |
| Chart.js | 4.4 | Charts & analytics |
| date-fns | 3.6 | Date formatting |

## License Tiers

### Free
- UTM Builder with unlimited codes
- Short Links (up to 100)
- Contact Management (up to 500)
- Basic dashboard analytics

### Pro ($99/year)
- Everything in Free
- Unlimited short links and contacts
- Popup Builder with 7 trigger types:
  - Time delay
  - Scroll percentage
  - Scroll to element
  - Exit intent
  - Click trigger
  - Page view count
  - User inactivity
- Advanced analytics dashboard
- Email service integrations
- Priority support

### Agency ($299/year)
- Everything in Pro
- Multi-site Monitor (up to 25 sites)
  - Health scoring
  - Uptime monitoring
  - Update management
  - Analytics sync
- Peanut Connect child plugin
- White-label options
- API access
- Dedicated support

## Database Schema

The plugin creates these tables (prefixed with `{wp_prefix}_peanut_`):

### Core Tables
- `utms` - UTM codes and tracking data
- `utm_tags` - UTM tagging system
- `links` - Short link records
- `link_clicks` - Click tracking with device/geo data
- `contacts` - Contact records
- `contact_tags` - Contact tagging
- `contact_activities` - Activity timeline

### Popups Tables (Pro)
- `popups` - Popup configurations
- `popup_interactions` - View/conversion/dismiss tracking

### Monitor Tables (Agency)
- `monitor_sites` - Connected site records
- `monitor_health_log` - Health check history
- `monitor_uptime` - Uptime monitoring
- `monitor_analytics` - Synced analytics data

## Peanut Connect (Child Plugin)

For the Monitor module, install the **Peanut Connect** plugin on child sites:

```
peanut-connect/
├── peanut-connect.php           # Main plugin file
├── includes/
│   ├── class-connect-auth.php   # Site key authentication
│   ├── class-connect-health.php # Health data collection
│   ├── class-connect-updates.php # Remote update handling
│   └── class-connect-api.php    # REST API endpoints
└── readme.txt                   # WordPress.org readme
```

Peanut Connect exposes endpoints that allow the main Peanut Suite installation to:
- Check site health (WordPress version, PHP version, SSL, disk space)
- List available plugin/theme updates
- Trigger remote updates
- Sync analytics data

## Add-on Plugins (Planned)

| Plugin | Description | Status |
|--------|-------------|--------|
| FormFlow | Advanced form builder with multi-touch attribution | Existing |
| Peanut Landing | Landing page builder | Planned |
| Peanut Nurture | Email sequences & automation | Planned |

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed version history.

## License

This project is licensed under the GPL v2 or later.

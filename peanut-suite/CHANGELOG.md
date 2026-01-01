# Changelog

All notable changes to Peanut Suite will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.2.38] - 2026-01-01

### Fixed
- Reconnect button in error banner now has visible red styling
- Reconnect modal z-index increased to appear above WordPress navigation
- Reconnect modal buttons now properly visible with explicit styling
- Modal has max-height constraint with scroll for smaller screens

## [4.2.36] - 2026-01-01

### Added
- Site reconnect feature for Monitor module
- Reconnect button appears when site connection is lost
- Modal dialog to enter new site key from Peanut Connect
- Backend endpoint `POST /monitor/sites/{id}/reconnect`

## [4.2.35] - 2026-01-01

### Fixed
- Monitor site detail page not displaying health data correctly
- Frontend now reads from correct nested `site.health.checks.*` structure
- Plugin updates field names corrected to match Peanut Connect format

### Changed
- Added `MonitorSiteHealthChecks` TypeScript interface for proper typing
- Removed unused `getSiteHealth` query from SiteDetail page

## [4.2.34] - 2025-01-01

### Fixed
- PageGuide popup navigation buttons not clickable
- React "removeChild" DOM errors suppressed in ErrorBoundary

### Changed
- Portal root now attaches to `#peanut-app` instead of `document.body`
- Deploy script fixed to include `assets/dist` folder

## [4.2.33] - 2025-01-01

### Fixed
- Code-split chunks loading from wrong URL path (404 errors)
- React error #321 caused by missing chunk files

### Changed
- Added Vite `base` path configuration for WordPress plugin structure

## [4.2.32] - 2025-01-01

### Fixed
- React portal "removeChild" error in WordPress admin caused by DOM conflicts with external scripts

### Added
- Dedicated portal container (`#peanut-portal-root`) for all React portals
- `portalRoot.ts` utility for safe portal rendering

### Changed
- Updated PageGuide, FeatureTour, WelcomeModal, and Team dropdown to use isolated portal container

## [4.2.31] - 2024-12-31

### Added
- CHANGELOG.md with version history
- Changelog link in README

## [4.2.30] - 2024-12-31

### Added
- Full release workflow script (`npm run release:patch/minor/major`)
- NPM scripts documentation in README

### Changed
- Release workflow now automates: version bump, commit, push, package, GitHub release, and deploy

## [4.2.29] - 2024-12-31

### Added
- `npm run deploy` script for peanutgraphic deployment
- `npm run bump:patch/minor/major` scripts for version management

## [4.2.28] - 2024-12-31

### Added
- Version bump script (`scripts/bump-version.sh`)
- Updates version across all 6 locations automatically

### Changed
- Added `dist/` and `.phpunit.result.cache` to .gitignore
- Fixed package script to read version from peanut-suite.php

## [4.2.27] - 2024-12-31

### Added
- OpenAPI 3.0 REST API documentation (`docs/openapi.yaml`)
- Public endpoint `GET /wp-json/peanut/v1/openapi` to serve API docs
- Comprehensive documentation for all 25+ API endpoint groups

## [4.2.26] - 2024-12-31

### Added
- Code splitting for React frontend (reduced initial bundle size)
- Vitest testing infrastructure with React Testing Library
- Badge and Button component tests

### Changed
- Improved API client error handling
- Enhanced TypeScript types

## [4.2.25] - 2024-12-31

### Security
- Fixed SQL injection vulnerabilities across 9 PHP files
- Added proper `$wpdb->prepare()` and `esc_sql()` usage

## [4.2.24] - 2024-12-31

### Added
- Team Member Profile page with password management
- Feature access display based on user role

## [4.2.23] - 2024-12-31

### Added
- Team Member Profile page
- Dropdown overflow fixes

## [4.2.22] - 2024-12-31

### Fixed
- Audit Log export returning empty data

## [4.2.21] - 2024-12-31

### Added
- Audit Log feature for tracking account activity
- Filtering and export capabilities

### Fixed
- API response handling improvements

## [4.2.14] - 2024-12-31

### Fixed
- Health Reports site selection
- API response handling

## [4.2.13] - 2024-12-31

### Fixed
- Monitor "Site Not Found" bug

## [4.2.10] - 2024-12-31

### Fixed
- Monitor/Health Reports REST API routes not registering

## [4.2.9] - 2024-12-31

### Fixed
- Comprehensive audit fixes: autoloader, activator, database tables
- Popups 500 error - added missing check_permission method

## [4.2.0] - 2024-12-30

### Added
- Multi-tenancy support with account management
- Team management with roles (owner, admin, member, viewer)
- API Keys management with scopes
- Audit logging for security tracking

## [4.1.0] - 2024-12-29

### Added
- Health Reports module
- Server monitoring with Plesk integration
- Web Vitals tracking

## [4.0.0] - 2024-12-28

### Added
- Complete React SPA rewrite
- Modern dashboard with Tailwind CSS
- TanStack Query for data fetching
- Zustand for state management

### Changed
- Migrated from jQuery to React 18
- New REST API architecture

# Event Landing Pages — WordPress Plugin

## Project Overview
Generic, white-label WordPress plugin for creating event landing pages with HubSpot integration (time slot picker or form embed).

## Tech Stack
- WordPress plugin (PHP 7.4+, requires ACF PRO)
- PSR-4 autoloading with manual fallback
- HubSpot `meetings-public/v3` API (time slot picker)
- HubSpot Forms embed (form booking method)
- GitHub update checker (PUC) with `enableReleaseAssets()` for monorepo compatibility
- JSON-LD Event schema output (automatic, via `wp_head`)

## Plugin Structure
- CPT: `elp_event`, prefix: `elp_`, namespace: `EventLandingPages`
- Source: `event-landing-pages/src/` (PSR-4)
- Template: `event-landing-pages/templates/single-elp_event.php` (standalone HTML, no theme header/footer)
- Assets: `event-landing-pages/assets/` (CSS + conditional JS per booking method)

## Key Architecture
- `BrandResolver` — per-event brand → global brand → WP custom logo fallback chain
- `EventSchema` — automatic JSON-LD Event structured data from ACF fields via `wp_head`
- `HubSpotProxy` — REST proxy for availability (public) and booking (nonce-protected)
- `CustomPathRouter` — per-event custom URL slugs
- `Encryption` — sodium-encrypted HubSpot API key storage
- Per-event color overrides via CSS custom properties (`--elp-*`)

## Conventions
- PSR-4 structure under `event-landing-pages/src/`
- All ACF field keys prefixed `elp_`
- Mobile-responsive design with media queries
- No build tools, no bundler, no package manager
- **Releasing:** See [`RELEASING.md`](RELEASING.md) for version bump, zip build, and GitHub release workflow

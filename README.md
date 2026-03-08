# Event Landing Pages

A generic, white-label WordPress plugin for creating event landing pages with HubSpot time slot picker or form embed integration.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- [Advanced Custom Fields PRO](https://www.advancedcustomfields.com/pro/) (ACF PRO 6.x+) — required for the global settings page and all custom fields
- A [HubSpot](https://www.hubspot.com/) account with one of the following configured:
  - **Meetings tool** — for the time slot picker booking method. Create a meeting link in HubSpot (Settings > Meetings) and note the slug (e.g. `username/meeting-name`).
  - **Forms** — for the form embed booking method. You'll need your Portal ID and the Form ID from HubSpot.

## Setup

1. Install and activate ACF PRO.
2. Install and activate the Event Landing Pages plugin.
3. Go to **Events > Settings** in wp-admin:
   - **HubSpot tab** — Enter your Portal ID (found in HubSpot under Settings > Account Management). Optionally add a Private App Access Token for server-side API calls.
   - **Default Brand tab** — Set your brand name, logo, and website. These are used as defaults for all events (or the WordPress site logo is used as a fallback).
   - **Typography tab** — Optionally set Google Font families for headings and body text.
4. Create a new Event under **Events > Add New** and configure the booking method, event details, and URL.

## Features

- **Two booking methods** — HubSpot time slot picker (custom calendar widget) or HubSpot form embed, configurable per event
- **Per-event branding** — brand name, logo, website, and partner info with global defaults fallback
- **Custom URL paths** — set any URL slug per event (e.g. `locations/city/event-name`)
- **Color theming** — per-event color overrides via CSS custom properties
- **Event Schema** — automatic JSON-LD structured data for Google Event rich results
- **Standalone template** — full-bleed landing page with no theme header/footer (theme-overridable)
- **Auto-updates** — GitHub release-based updates via the WordPress admin

## Releasing a New Version

See [RELEASING.md](RELEASING.md) for the full version bump, zip build, and GitHub release workflow.

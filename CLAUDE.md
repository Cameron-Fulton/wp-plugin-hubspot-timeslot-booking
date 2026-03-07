# Castle Rock Hormone Health - Event Landing Page

## Project Overview
Static HTML landing page for a $99 Comprehensive Hormone Panel event promotion, a collaboration between Castle Rock Hormone Health and Valor Elite Training.

## Tech Stack
- Single-file static HTML (`index.html`)
- Vanilla CSS (CSS custom properties for theming)
- Vanilla JavaScript (minimal)
- Google Fonts: Oswald + Source Sans 3
- HubSpot Forms embed (`hsforms.net/forms/embed/v2.js`)
- HubSpot time slot picker via `/hubspot-timeslot-picker` skill (custom calendar widget using the `meetings-public/v3` API)

## Design System
- Dark theme with CSS custom properties defined in `:root`
- `--dark: #0f1114` (background)
- `--accent: #c8102e` (red CTA)
- `--gold: #d4a843` (highlights)
- `--text: #e8e6e1` (body text)
- Fonts: Oswald (headings/uppercase), Source Sans 3 (body)

## Key Sections
- Top bar with partner branding
- Logo block (CRHH + Valor)
- Event badge + headline
- Biomarker/panel details
- HubSpot form embed for sign-ups
- HubSpot time slot picker (custom calendar widget replacing default HubSpot calendar UI)

## Conventions
- Everything lives in a single `index.html` file (inline styles and scripts)
- Mobile-responsive design with media queries
- No build tools, no bundler, no package manager

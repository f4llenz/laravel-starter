---
layout: home

hero:
  name: Project Docs
  text: Internal Documentation
  tagline: Your project description here
  actions:
    - theme: brand
      text: Getting Started
      link: /getting-started/
    - theme: alt
      text: Architecture
      link: /architecture/

features:
  - title: Filament Admin
    details: Full-featured admin panel with resources, tables, and forms.
  - title: Modern Stack
    details: Laravel 12, Inertia v2, Vue 3, Tailwind v4, TypeScript.
  - title: Quality Tooling
    details: Pest testing, PHPStan analysis, Pint formatting, CI/CD.
---

## Quick Start

```bash
# Start development
composer run dev

# Run tests
php artisan test

# Static analysis
composer analyse

# Format code
vendor/bin/pint
```

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12, PHP 8.4 |
| Admin UI | Filament 4 |
| Frontend | Inertia.js v2 + Vue 3 |
| Styling | Tailwind CSS 4 |
| Queue | Laravel Horizon (Redis) |
| Monitoring | Pulse, Telescope, Sentry |
| Testing | Pest |

## Dashboards

| URL | Purpose |
|-----|---------|
| `/admin` | Filament admin panel |
| `/telescope` | Debug dashboard |
| `/horizon` | Queue dashboard |
| `/pulse` | Performance dashboard |

# Laravel Starter Pack

A post-install package that enhances the official Laravel Vue starter kit with production-ready tooling and architectural patterns.

## What's Included

### Packages

**Production:**
- [Filament](https://filamentphp.com/) - Admin panel
- [Laravel Horizon](https://laravel.com/docs/horizon) - Queue dashboard
- [Laravel Pulse](https://laravel.com/docs/pulse) - Performance monitoring
- [Laravel Telescope](https://laravel.com/docs/telescope) - Debug dashboard
- [Spatie Laravel Data](https://spatie.be/docs/laravel-data) - DTOs
- [Spatie Laravel Backup](https://spatie.be/docs/laravel-backup) - Automated backups
- [Sentry](https://sentry.io/) - Error tracking
- [Predis](https://github.com/predis/predis) - Redis client

**Development:**
- [Laravel Boost](https://github.com/laravel/boost) - AI-powered development
- [Pest](https://pestphp.com/) - Testing framework
- [Larastan](https://github.com/larastan/larastan) - Static analysis
- [IDE Helper](https://github.com/barryvdh/laravel-ide-helper) - IDE autocompletion
- [VitePress](https://vitepress.dev/) - Documentation site

### Directory Structure

```
app/
├── Actions/           # Business logic
├── DataObjects/       # Type-safe DTOs
├── Enums/             # Type-safe constants
├── Services/          # External integrations
└── Support/           # Utilities
```

### Configuration

- `phpstan.neon` - PHPStan level 5 analysis
- `.github/workflows/ci.yml` - CI pipeline (Pint, PHPStan, Tests, Frontend)
- `docs-site/` - VitePress internal documentation

### CLAUDE.md Enhancements

The installer automatically adds to your `CLAUDE.md`:
- VitePress documentation section
- Context7 rule for Filament docs
- Git commit rules (no Claude attribution)

## Installation

### 1. Create a new Laravel app with the Vue starter kit

```bash
laravel new my-app
# Select: Vue with Inertia
cd my-app
```

### 2. Require this package

```bash
composer config repositories.starter vcs https://github.com/f4llenz/laravel-starter.git
composer require f4llenz/laravel-starter:dev-main --dev
```

### 3. Run the installer

```bash
php artisan starter:install
```

### 4. Run post-install commands

```bash
# Required: Set up AI coding assistant guidelines
php artisan boost:install

# Optional: Set up change proposal system
openspec init
```

### Options

```bash
# Skip package installation (if you want to install manually)
php artisan starter:install --skip-packages

# Skip Pest migration
php artisan starter:install --skip-pest

# Skip VitePress documentation setup
php artisan starter:install --skip-docs
```

## After Installation

### Dashboards

| URL | Purpose |
|-----|---------|
| `/admin` | Filament admin panel |
| `/telescope` | Debug dashboard |
| `/horizon` | Queue dashboard |
| `/pulse` | Performance monitoring |

### Quality Checks

```bash
# Format code
vendor/bin/pint

# Static analysis
composer analyse

# Run tests
php artisan test
```

### Documentation

```bash
npm run docs:dev
# Open http://localhost:5173
```

## Composer Scripts

- `composer test` - Clear config and run tests
- `composer analyse` - Run PHPStan

## Development

### Testing Locally

```bash
# In another Laravel project
composer config repositories.starter path ../laravel-starter
composer require f4llenz/laravel-starter:dev-main --dev
```

### Publishing to Packagist

1. Update `composer.json` with your vendor name
2. Push to GitHub
3. Register on [Packagist](https://packagist.org/)

## License

MIT

# Getting Started

## Prerequisites

- PHP 8.4+
- Composer
- Node.js 22+
- Redis (for queues)

## Installation

```bash
# Clone the repository
git clone <repo-url>
cd <project>

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --seed

# Build assets
npm run build
```

## Development

```bash
# Start all services (server, queue, logs, vite)
composer run dev

# Or start individually
php artisan serve
php artisan queue:listen
npm run dev
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter="test name"

# With coverage
php artisan test --coverage
```

## Code Quality

```bash
# Format code
vendor/bin/pint

# Static analysis
composer analyse

# Frontend linting
npm run lint
npm run format:check
```

## Useful Commands

```bash
# Generate IDE helpers
php artisan ide-helper:generate
php artisan ide-helper:models

# Clear caches
php artisan optimize:clear

# Create admin user
php artisan make:filament-user
```

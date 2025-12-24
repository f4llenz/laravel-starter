<?php

namespace F4llenz\LaravelStarter\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    protected $signature = 'starter:install
                            {--skip-packages : Skip installing Composer/NPM packages}
                            {--skip-pest : Skip migrating to Pest}
                            {--skip-docs : Skip VitePress documentation setup}';

    protected $description = 'Install the Laravel starter pack';

    protected Filesystem $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem;
    }

    public function handle(): int
    {
        info('Installing Laravel Starter Pack...');

        $this->createDirectories();
        $this->copyConfigFiles();
        $this->addComposerScripts();

        if (! $this->option('skip-packages')) {
            $this->installComposerPackages();
            $this->installNpmPackages();
        }

        if (! $this->option('skip-pest')) {
            $this->migrateToPest();
        }

        if (! $this->option('skip-docs')) {
            $this->setupVitePressDocs();
        }

        $this->publishAssets();
        $this->generateIdeHelpers();
        $this->updateClaudeMd();

        info('');
        info('Laravel Starter Pack installed successfully!');
        info('');
        info('Next steps:');
        info('  1. Run: php artisan boost:install');
        info('  2. Run: openspec init (optional, for change proposals)');
        info('  3. Run: composer analyse (to verify PHPStan setup)');
        info('  4. Run: php artisan test (to verify Pest setup)');
        info('  5. Run: npm run docs:dev (to view documentation)');
        info('');
        info('Dashboards:');
        info('  - /admin (Filament admin panel)');
        info('  - /telescope (debugging dashboard)');
        info('  - /horizon (queue dashboard)');
        info('  - /pulse (performance dashboard)');

        return self::SUCCESS;
    }

    protected function createDirectories(): void
    {
        info('Creating directory structure...');

        $directories = [
            app_path('Actions'),
            app_path('DataObjects'),
            app_path('Enums'),
            app_path('Services'),
            app_path('Support'),
        ];

        foreach ($directories as $directory) {
            if (! $this->files->isDirectory($directory)) {
                $this->files->makeDirectory($directory, 0755, true);
                $this->files->put($directory.'/.gitkeep', '');
            }
        }
    }

    protected function copyConfigFiles(): void
    {
        info('Copying configuration files...');

        $stubPath = dirname(__DIR__, 2).'/stubs';

        // PHPStan config
        if (! $this->files->exists(base_path('phpstan.neon'))) {
            $this->files->copy($stubPath.'/phpstan.neon', base_path('phpstan.neon'));
        }

        // GitHub CI workflow
        $workflowDir = base_path('.github/workflows');
        if (! $this->files->isDirectory($workflowDir)) {
            $this->files->makeDirectory($workflowDir, 0755, true);
        }
        if (! $this->files->exists($workflowDir.'/ci.yml')) {
            $this->files->copy($stubPath.'/.github/workflows/ci.yml', $workflowDir.'/ci.yml');
        }
    }

    protected function addComposerScripts(): void
    {
        info('Adding Composer scripts...');

        $composerPath = base_path('composer.json');
        $composer = json_decode($this->files->get($composerPath), true);

        $scripts = [
            'test' => [
                '@php artisan config:clear --ansi',
                '@php artisan test',
            ],
            'analyse' => [
                'vendor/bin/phpstan analyse --memory-limit=1G',
            ],
        ];

        foreach ($scripts as $name => $commands) {
            if (! isset($composer['scripts'][$name])) {
                $composer['scripts'][$name] = $commands;
            }
        }

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );
    }

    protected function installComposerPackages(): void
    {
        info('Installing Composer packages...');

        $requirePackages = [
            'filament/filament',
            'laravel/horizon',
            'laravel/pulse',
            'laravel/telescope',
            'spatie/laravel-data',
            'spatie/laravel-backup',
            'predis/predis',
            'sentry/sentry-laravel',
        ];

        $requireDevPackages = [
            'laravel/boost',
            'pestphp/pest',
            'larastan/larastan',
            'barryvdh/laravel-ide-helper',
        ];

        spin(
            fn () => Process::run('composer require -W '.implode(' ', $requirePackages))->throw(),
            'Installing production packages...'
        );

        spin(
            fn () => Process::run('composer require -W --dev '.implode(' ', $requireDevPackages))->throw(),
            'Installing development packages...'
        );
    }

    protected function installNpmPackages(): void
    {
        info('Installing NPM packages...');

        spin(
            fn () => Process::run('npm install -D vitepress')->throw(),
            'Installing VitePress...'
        );
    }

    protected function migrateToPest(): void
    {
        if (! $this->files->exists(base_path('vendor/bin/pest'))) {
            warning('Pest not found. Skipping migration.');

            return;
        }

        info('Configuring Pest...');

        $stubPath = dirname(__DIR__, 2).'/stubs';

        // Copy Pest.php if it doesn't exist or is default
        $pestPath = base_path('tests/Pest.php');
        if (! $this->files->exists($pestPath)) {
            $this->files->copy($stubPath.'/tests/Pest.php', $pestPath);
        }

        // Initialize Pest if not already initialized
        if (! $this->files->exists(base_path('tests/Pest.php'))) {
            Process::run('vendor/bin/pest --init');
        }
    }

    protected function setupVitePressDocs(): void
    {
        if (! confirm('Set up VitePress documentation site?', true)) {
            return;
        }

        info('Setting up VitePress documentation...');

        $stubPath = dirname(__DIR__, 2).'/stubs/docs-site';
        $docsPath = base_path('docs-site');

        if (! $this->files->isDirectory($docsPath)) {
            $this->files->copyDirectory($stubPath, $docsPath);
        }

        // Add docs scripts to package.json
        $packagePath = base_path('package.json');
        if ($this->files->exists($packagePath)) {
            $package = json_decode($this->files->get($packagePath), true);

            $package['scripts']['docs:dev'] = 'vitepress dev docs-site';
            $package['scripts']['docs:build'] = 'vitepress build docs-site';
            $package['scripts']['docs:preview'] = 'vitepress preview docs-site';

            $this->files->put(
                $packagePath,
                json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
            );
        }
    }

    protected function publishAssets(): void
    {
        info('Publishing package assets...');

        // Filament
        if (class_exists(\Filament\FilamentServiceProvider::class)) {
            spin(
                fn () => $this->callSilently('filament:install', ['--panels' => true, '--no-interaction' => true]),
                'Installing Filament...'
            );
        }

        // Horizon
        if (class_exists(\Laravel\Horizon\HorizonServiceProvider::class)) {
            spin(
                fn () => $this->callSilently('horizon:install'),
                'Installing Horizon...'
            );
        }

        // Telescope
        if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            spin(
                fn () => $this->callSilently('telescope:install'),
                'Installing Telescope...'
            );
        }

        // Pulse
        if (class_exists(\Laravel\Pulse\PulseServiceProvider::class)) {
            spin(
                fn () => $this->callSilently('vendor:publish', ['--provider' => 'Laravel\Pulse\PulseServiceProvider']),
                'Publishing Pulse config...'
            );
        }

        // Sentry
        if (class_exists(\Sentry\Laravel\ServiceProvider::class)) {
            spin(
                fn () => $this->callSilently('sentry:publish', ['--dsn' => '']),
                'Publishing Sentry config...'
            );
        }

        // Run migrations
        spin(
            fn () => $this->callSilently('migrate'),
            'Running migrations...'
        );
    }

    protected function generateIdeHelpers(): void
    {
        if (! class_exists(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class)) {
            return;
        }

        info('Generating IDE helpers...');

        spin(
            fn () => $this->callSilently('ide-helper:generate'),
            'Generating IDE helper files...'
        );

        spin(
            fn () => $this->callSilently('ide-helper:meta'),
            'Generating IDE meta file...'
        );
    }

    protected function updateClaudeMd(): void
    {
        $claudePath = base_path('CLAUDE.md');

        if (! $this->files->exists($claudePath)) {
            return;
        }

        info('Updating CLAUDE.md with custom rules...');

        $content = $this->files->get($claudePath);
        $modified = false;

        // 1. Add VitePress docs section before boost block
        if (str_contains($content, '<laravel-boost-guidelines>') && ! str_contains($content, '## Internal Documentation')) {
            $vitepressSection = $this->getVitepressSection();
            $content = str_replace(
                '<laravel-boost-guidelines>',
                $vitepressSection."\n<laravel-boost-guidelines>",
                $content
            );
            $modified = true;
        }

        // 2. Add Context7 rule to Laravel 12 section
        if (str_contains($content, '=== laravel/v12 rules ===') && ! str_contains($content, 'context7')) {
            $context7Rule = "- Always use context7 when I need library/API documentation. This means you should automatically use the Context7 MCP\n  tools to resolve library id and get library docs without me having to explicitly ask. When dealing with Filament, always use Context7 first.";

            // Insert after "Since Laravel 11, Laravel has a new streamlined file structure which this project uses."
            $content = preg_replace(
                '/(- Since Laravel 11, Laravel has a new streamlined file structure which this project uses\.)/',
                "$1\n".$context7Rule,
                $content
            );
            $modified = true;
        }

        // 3. Add Git rules before closing </laravel-boost-guidelines>
        if (str_contains($content, '</laravel-boost-guidelines>') && ! str_contains($content, '=== git rules ===')) {
            $gitRules = $this->getGitRulesSection();
            $content = str_replace(
                '</laravel-boost-guidelines>',
                $gitRules."\n</laravel-boost-guidelines>",
                $content
            );
            $modified = true;
        }

        if ($modified) {
            $this->files->put($claudePath, $content);
        }
    }

    protected function getVitepressSection(): string
    {
        return <<<'MD'
## Internal Documentation

This project has a VitePress documentation site for internal reference.

### Viewing Documentation

```bash
npm run docs:dev
```

Opens at http://localhost:5173

### Updating Documentation

When implementing features that change:
- Architecture or data flow
- Domain models or relationships
- Code patterns (DTOs, Actions, Services)
- Database schema
- Integration behavior

Update the relevant pages in `docs-site/`:

| Topic | File |
|-------|------|
| Getting Started | `docs-site/getting-started/index.md` |
| Architecture | `docs-site/architecture/*.md` |
| Domain Models | `docs-site/domain/*.md` |
| Features | `docs-site/features/*.md` |
| Code Patterns | `docs-site/patterns/*.md` |
| Database | `docs-site/database/index.md` |
| Integrations | `docs-site/integrations/*.md` |

### Documentation Guidelines

- Keep pages focused and concise
- Include code examples where helpful
- Link to related pages

MD;
    }

    protected function getGitRulesSection(): string
    {
        return <<<'MD'

=== git rules ===

## Git Commits

- DO NOT add "Generated with [Claude Code]" or "Co-Authored-By: Claude" to commit messages.
- Write concise, descriptive commit messages that explain what changed and why.
- Follow conventional commit format when appropriate (feat:, fix:, refactor:, etc.).
MD;
    }
}

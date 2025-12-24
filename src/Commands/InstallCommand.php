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

        info('');
        info('Laravel Starter Pack installed successfully!');
        info('');
        info('Next steps:');
        info('  1. Run: composer analyse (to verify PHPStan setup)');
        info('  2. Run: php artisan test (to verify Pest setup)');
        info('  3. Run: npm run docs:dev (to view documentation)');
        info('  4. Visit: /admin (Filament admin panel)');
        info('  5. Visit: /telescope (debugging dashboard)');
        info('  6. Visit: /horizon (queue dashboard)');
        info('  7. Visit: /pulse (performance dashboard)');

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
            fn () => Process::run('composer require '.implode(' ', $requirePackages))->throw(),
            'Installing production packages...'
        );

        spin(
            fn () => Process::run('composer require --dev '.implode(' ', $requireDevPackages))->throw(),
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
}

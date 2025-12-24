<?php

namespace F4llenz\LaravelStarter;

use F4llenz\LaravelStarter\Commands\InstallCommand;
use Illuminate\Support\ServiceProvider;

class StarterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}

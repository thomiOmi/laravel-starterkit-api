<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * Automatically discovers and registers module service providers.
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerModules();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register modules and their providers.
     */
    protected function registerModules(): void
    {
        $modulePath = base_path('modules');

        if (! File::exists($modulePath)) {
            return;
        }

        $modules = File::directories($modulePath);

        foreach ($modules as $module) {
            $moduleName = basename($module);

            // Register Service Provider
            $provider = "Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";
            if (class_exists($provider)) {
                $this->app->register($provider);
            }

            // Load Migrations
            $migrationPath = "{$module}/Database/Migrations";
            if (File::exists($migrationPath)) {
                $this->loadMigrationsFrom($migrationPath);
            }

            // Discovery Events/Listeners (Simple implementation)
            $this->registerModularEvents($module);
        }
    }

    /**
     * Register modular events and listeners.
     */
    protected function registerModularEvents(string $modulePath): void
    {
        // For now, we rely on manual registration in module's own ServiceProvider if needed,
        // or we can implement a specific event discovery here.
    }
}

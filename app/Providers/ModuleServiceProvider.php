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
            $modulePath = is_string($module) ? $module : '';
            if ($modulePath === '') {
                continue;
            }
            $moduleName = basename($modulePath);

            // Register Service Provider
            $provider = "Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";
            if (class_exists($provider)) {
                $this->app->register($provider);
            }

            // Load Migrations
            $migrationPath = "{$modulePath}/Database/Migrations";
            if (File::exists($migrationPath)) {
                $this->loadMigrationsFrom($migrationPath);
            }

            // Discovery Events/Listeners (Simple implementation)
            $this->registerModularEvents($modulePath);
        }
    }

    /**
     * Register modular events and listeners.
     *
     * Reserved for future event discovery. Modules currently register
     * their own events in their respective service providers.
     *
     * @param  string  $modulePath  The absolute path to the module directory.
     */
    protected function registerModularEvents(string $modulePath): void
    {
        //
    }
}

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
     * Register modules and their providers.
     */
    protected function registerModules(): void
    {
        $modulesRootPath = base_path('modules');

        if (! File::exists($modulesRootPath)) {
            return;
        }

        $modules = File::directories($modulesRootPath);

        foreach ($modules as $moduleDirectory) {
            $modulePath = is_string($moduleDirectory) ? $moduleDirectory : '';
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
     * @param  string  $modulePath  The absolute path to the module directory.
     */
    protected function registerModularEvents(string $modulePath): void
    {
        // For now, we rely on manual registration in module's own ServiceProvider if needed,
        // or we can implement a specific event discovery here.
    }
}

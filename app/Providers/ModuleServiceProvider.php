<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the service providers and migrations of enabled modules.
 *
 * Modules are loaded from the allow-list in config/modules.php. Directories
 * under modules/ that are not listed are silently ignored, which also makes
 * private modules possible: keep the directory on disk without registering it.
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
     * Register enabled modules and their providers.
     */
    protected function registerModules(): void
    {
        $modulePath = base_path('modules');

        if (! File::exists($modulePath)) {
            return;
        }

        $enabledModules = config()->array('modules.enabled', []);

        $modules = File::directories($modulePath);

        foreach ($modules as $module) {
            $resolvedPath = is_string($module) ? $module : '';
            if ($resolvedPath === '') {
                continue;
            }
            $moduleName = basename($resolvedPath);

            if (! in_array(strtolower($moduleName), $enabledModules, true)) {
                continue;
            }

            // Register Service Provider
            $provider = "Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";
            if (class_exists($provider)) {
                $this->app->register($provider);
            }

            // Load Migrations
            $migrationPath = "{$resolvedPath}/Database/Migrations";
            if (File::exists($migrationPath)) {
                $this->loadMigrationsFrom($migrationPath);
            }
        }
    }
}

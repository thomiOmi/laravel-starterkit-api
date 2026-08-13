<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the service providers of ACTIVE modules from the central registry.
 *
 * Reads config/modules.php and, for every entry with active => true, resolves
 * the module provider via convention (Modules\{Folder}\Providers\{Folder}ServiceProvider)
 * guarded by class_exists. Boot order follows registry declaration order.
 * Unregistered modules are fully inert: their provider is never registered.
 */
class ModuleLoaderServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerActiveModules();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register the providers of modules marked active in the registry.
     */
    protected function registerActiveModules(): void
    {
        $modulePath = base_path('modules');

        if (! File::isDirectory($modulePath)) {
            return;
        }

        $registry = config()->array('modules.modules', []);

        foreach ($registry as $alias => $config) {
            if (! is_array($config) || ($config['active'] ?? false) !== true) {
                continue;
            }

            $folder = $this->resolveModuleFolder($modulePath, $alias);

            if ($folder === null) {
                continue;
            }

            $provider = "Modules\\{$folder}\\Providers\\{$folder}ServiceProvider";

            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * Find the TitleCase module folder matching the registry alias.
     */
    protected function resolveModuleFolder(string $modulePath, string $alias): ?string
    {
        foreach (File::directories($modulePath) as $directory) {
            $resolvedPath = is_string($directory) ? $directory : '';

            if ($resolvedPath === '' || strtolower(basename($resolvedPath)) !== strtolower($alias)) {
                continue;
            }

            return basename($resolvedPath);
        }

        return null;
    }
}

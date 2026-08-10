<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Discover and register module API routes.
 *
 * Iterates the enabled modules listed in config/modules.php and loads their
 * versioned route files (`Routes/{VERSION}.php`) with the correct prefix and
 * middleware. Every module, including IAM, is discovered the same way.
 * Modules that are not in the allow-list are skipped.
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->routes(function () {
            $this->mapModuleApiRoutes();

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Load versioned API route files for every module.
     *
     * Each module can provide routes for one or more API versions (e.g. V1, V2).
     */
    protected function mapModuleApiRoutes(): void
    {
        $moduleBase = base_path('modules');

        if (! File::exists($moduleBase)) {
            return;
        }

        /** @var array<int, string> $supportedVersions */
        $supportedVersions = config()->array('apiroute.supported_versions', ['V1']);

        $enabledModules = config()->array('modules.enabled', []);

        $moduleDirs = File::directories($moduleBase);

        foreach ($moduleDirs as $moduleDir) {
            $resolvedPath = is_string($moduleDir) ? $moduleDir : '';
            if ($resolvedPath === '') {
                continue;
            }

            $moduleName = basename($resolvedPath);

            if (! in_array(strtolower($moduleName), $enabledModules, true)) {
                continue;
            }

            foreach ($supportedVersions as $version) {
                $routeFile = "{$resolvedPath}/Routes/{$version}.php";

                if (File::exists($routeFile)) {
                    Route::prefix('api/'.strtolower($version))
                        ->middleware(['api'])
                        ->name(strtolower($version).'.'.strtolower($moduleName).'.')
                        ->group($routeFile);
                }
            }
        }
    }
}

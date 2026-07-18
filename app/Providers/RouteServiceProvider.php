<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Discover and register module API routes.
 *
 * Iterates all directories under `modules/` and loads versioned route
 * files (`Routes/{VERSION}.php`) with the correct prefix and middleware.
 * The IAM module is skipped because it registers its own routes via
 * IAMServiceProvider.
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
     * Load versioned API route files for every non-IAM module.
     *
     * Each module can provide routes for one or more API versions (e.g. V1, V2).
     * A legacy `Routes/api.php` fallback is supported for backward compatibility.
     */
    protected function mapModuleApiRoutes(): void
    {
        $moduleBase = base_path('modules');
        $supportedVersions = config()->array('apiroute.supported_versions', ['V1']);

        if (! File::exists($moduleBase)) {
            return;
        }

        $moduleDirs = File::directories($moduleBase);

        foreach ($moduleDirs as $moduleDir) {
            $resolvedPath = is_string($moduleDir) ? $moduleDir : '';
            if ($resolvedPath === '') {
                continue;
            }

            $moduleName = basename($resolvedPath);

            // IAM registers its own routes via IAMServiceProvider
            if (strtolower($moduleName) === 'iam') {
                continue;
            }

            /** @var array<int, string> $supportedVersions */
            foreach ($supportedVersions as $version) {
                $routeFile = "{$resolvedPath}/Routes/{$version}.php";

                if (File::exists($routeFile)) {
                    Route::prefix('api/'.strtolower($version))
                        ->middleware(['api'])
                        ->name(strtolower($version).'.'.strtolower($moduleName).'.')
                        ->group($routeFile);
                }
            }

            $legacyRouteFile = $resolvedPath.'/Routes/api.php';
            if (File::exists($legacyRouteFile)) {
                Route::prefix('api')
                    ->middleware('api')
                    ->name(strtolower($moduleName).'.')
                    ->group($legacyRouteFile);
            }
        }
    }
}

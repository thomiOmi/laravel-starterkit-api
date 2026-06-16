<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

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
     * Map the module API routes.
     */
    protected function mapModuleApiRoutes(): void
    {
        $modulePath = base_path('modules');
        $supportedVersions = config('apiroute.supported_versions', ['V1']);

        if (! File::exists($modulePath)) {
            return;
        }

        $modules = File::directories($modulePath);

        foreach ($modules as $module) {
            $modulePathString = is_string($module) ? $module : '';
            if ($modulePathString === '') {
                continue;
            }
            $moduleName = basename($modulePathString);

            /** @var array<int, string> $supportedVersions */
            foreach ($supportedVersions as $version) {
                $routeFile = "{$modulePathString}/Routes/{$version}.php";

                if (File::exists($routeFile)) {
                    Route::prefix('api/'.strtolower((string) $version))
                        ->middleware(['api'])
                        ->name('api.'.strtolower((string) $version).'.'.strtolower($moduleName).'.')
                        ->group($routeFile);
                }
            }

            // Fallback for non-versioned routes (optional, for backward compatibility)
            $legacyRouteFile = $modulePathString.'/Routes/api.php';
            if (File::exists($legacyRouteFile)) {
                Route::prefix('api')
                    ->middleware('api')
                    ->name('api.'.strtolower($moduleName).'.')
                    ->group($legacyRouteFile);
            }
        }
    }
}

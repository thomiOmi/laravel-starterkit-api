<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

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

        if (! File::exists($modulePath)) {
            return;
        }

        $modules = File::directories($modulePath);
        $versions = config('apiroute.versions', ['v1']);

        foreach ($modules as $module) {
            $moduleName = basename($module);

            foreach ($versions as $version) {
                $routeFile = $module."/Routes/{$version}.php";

                if (File::exists($routeFile)) {
                    Route::prefix("api/{$version}")
                        ->middleware('api')
                        ->name("api.{$version}.".strtolower($moduleName).'.')
                        ->group($routeFile);
                }
            }
        }
    }
}

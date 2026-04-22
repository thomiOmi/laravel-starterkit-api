<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();

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
        $versions = ['v1']; // Add more versions as needed

        foreach ($modules as $module) {
            foreach ($versions as $version) {
                $routeFile = $module.'/Routes/api.php';

                if (File::exists($routeFile)) {
                    Route::prefix("api/{$version}")
                        ->middleware('api')
                        ->group($routeFile);
                }
            }
        }
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}

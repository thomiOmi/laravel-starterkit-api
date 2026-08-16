<?php

namespace Modules\IAM\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'IAM';

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
    }

    /**
     * Define the versioned API routes for the module.
     *
     * The route files live under routes/ and are versioned by filename
     * (V1.php, V2.php). Each file is mounted on the api/v1/{alias} prefix
     * with the v1.{alias}. route naming contract.
     */
    protected function mapApiRoutes(): void
    {
        $versions = config()->array('apiroute.supported_versions', ['V1']);

        foreach ($versions as $version) {
            $routeFile = module_path($this->name, "routes/{$version}.php");

            if (! file_exists($routeFile)) {
                continue;
            }

            Route::prefix('api/'.strtolower($version).'/iam')
                ->middleware(['api'])
                ->name(strtolower($version).'.iam.')
                ->group($routeFile);
        }
    }
}

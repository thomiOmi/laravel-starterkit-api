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
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'iam';

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
     * (V1.php, V2.php). Each file is mounted on the api/{version} prefix;
     * the name prefix follows the api.{version}.{alias}. contract (for
     * example api.v1.iam.users.index), matching the kit's URL scheme
     * (api/v1/users, not api/v1/iam/users).
     */
    protected function mapApiRoutes(): void
    {
        /** @var array<int, string> $versions */
        $versions = config()->array('apiroute.supported_versions', ['V1']);

        foreach ($versions as $version) {
            $routeFile = module_path($this->name, "routes/$version.php");

            if (! file_exists($routeFile)) {
                continue;
            }

            Route::prefix('api/'.strtolower($version))
                ->middleware(['api'])
                ->name('api.'.strtolower($version).'.'.$this->nameLower.'.')
                ->group($routeFile);
        }
    }
}

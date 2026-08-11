<?php

declare(strict_types=1);

namespace Modules\Organization\Providers;

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\TenancyServiceProvider;

/**
 * Registers the tenancy infrastructure for the Organization module.
 *
 * Extends the stancl/tenancy provider so the package stays fully contained
 * in this module: config, central migrations, and tenant routes are only
 * loaded when the module is enabled in config/modules.php. When the module
 * is disabled, tenancy is completely inert.
 */
class OrganizationServiceProvider extends TenancyServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/tenancy.php', 'tenancy');

        parent::register();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/tenant.php');

        $this->registerTenantRoutes();
    }

    /**
     * Mount the tenant-scoped route file behind tenancy identification.
     *
     * Tenant routes are only reachable on non-central domains; the
     * PreventAccessFromCentralDomains middleware rejects central requests.
     */
    protected function registerTenantRoutes(): void
    {
        Route::middleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ])->group(__DIR__.'/../Routes/tenant.php');
    }
}

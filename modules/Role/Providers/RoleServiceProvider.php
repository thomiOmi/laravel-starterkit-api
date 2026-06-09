<?php

declare(strict_types=1);

namespace Modules\Role\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Role module.
 */
class RoleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}

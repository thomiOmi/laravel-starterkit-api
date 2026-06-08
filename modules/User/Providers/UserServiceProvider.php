<?php

declare(strict_types=1);

namespace Modules\User\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the User module.
 */
class UserServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}

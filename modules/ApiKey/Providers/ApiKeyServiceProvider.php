<?php

declare(strict_types=1);

namespace Modules\ApiKey\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\ApiKey\Guards\ApiKeyGuard;

class ApiKeyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->app['auth']->extend('api_key', function ($app, $name, array $config) {
            return new ApiKeyGuard($app['request']);
        });
    }

    public function register(): void
    {
        //
    }
}

<?php

declare(strict_types=1);

namespace Modules\User\Providers;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Modules\User\Events\UserCreated;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Queue::route(UserCreated::class, 'high-priority');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}

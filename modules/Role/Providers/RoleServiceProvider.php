<?php

declare(strict_types=1);

namespace Modules\Role\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Role\Listeners\AssignDefaultRole;
use Modules\User\Events\UserCreated;

class RoleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            UserCreated::class,
            AssignDefaultRole::class
        );
    }
}

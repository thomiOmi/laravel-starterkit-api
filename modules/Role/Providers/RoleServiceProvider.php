<?php

declare(strict_types=1);

namespace Modules\Role\Providers;

use Illuminate\Support\ServiceProvider;

class RoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}

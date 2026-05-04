<?php

declare(strict_types=1);

namespace Modules\AuditLog\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\AuditLog\Listeners\AuditAuthActivity;

class AuditLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Event::subscribe(AuditAuthActivity::class);
    }
}

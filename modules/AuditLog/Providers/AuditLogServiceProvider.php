<?php

declare(strict_types=1);

namespace Modules\AuditLog\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\AuditLog\Listeners\AuditAuthActivity;

/**
 * Class AuditLogServiceProvider
 *
 * Service provider for AuditLog module.
 */
class AuditLogServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Event::subscribe(AuditAuthActivity::class);
    }
}

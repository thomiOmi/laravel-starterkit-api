<?php

declare(strict_types=1);

namespace Modules\IAM\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Keep the framework's email verification listener.
     *
     * Laravel registers the global Registered ->
     * SendEmailVerificationNotification listener from the auto-registered
     * Illuminate\Foundation\Support\Providers\EventServiceProvider, so
     * overriding this hook on a module provider does not remove it
     * (confirmed with `php artisan event:list`).
     */
    #[\Override]
    protected function configureEmailVerification(): void {}
}

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
     * Suppress the framework's global Registered ->
     * SendEmailVerificationNotification listener: this module sends
     * verification mails explicitly through its own actions, keeping
     * delivery deterministic.
     */
    #[\Override]
    protected function configureEmailVerification(): void {}
}

<?php

declare(strict_types=1);

namespace Modules\AuditLog\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

/**
 * Class AuditAuthActivity
 *
 * Listener for Auth events to log login and logout activities.
 */
class AuditAuthActivity
{
    /**
     * Handle login event.
     */
    public function handleLogin(Login $event): void
    {
        /** @var \Illuminate\Database\Eloquent\Model $user */
        $user = $event->user;

        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->event('login')
            ->log('User logged in');
    }

    /**
     * Handle logout event.
     */
    public function handleLogout(Logout $event): void
    {
        /** @var \Illuminate\Database\Eloquent\Model|null $user */
        $user = $event->user;

        if ($user) {
            activity('auth')
                ->causedBy($user)
                ->performedOn($user)
                ->event('logout')
                ->log('User logged out');
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Dispatcher  $events
     */
    public function subscribe($events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
        ];
    }
}

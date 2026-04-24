<?php

declare(strict_types=1);

namespace Modules\Role\Listeners;

use Modules\User\Events\UserCreated;

class AssignDefaultRole
{
    /**
     * Handle the event.
     */
    public function handle(UserCreated $event): void
    {
        // We assign the 'user' role by default.
        // Since we are using Spatie, we can just use assignRole
        $event->user->assignRole('user');
    }
}

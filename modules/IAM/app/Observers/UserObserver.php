<?php

declare(strict_types=1);

namespace Modules\IAM\Observers;

use App\Enums\UserStatusEnum;
use Modules\IAM\Models\User;

final readonly class UserObserver
{
    /**
     * Handle the User "saved" event.
     */
    public function saved(User $user): void
    {
        if (! $user->wasChanged('email_verified_at') || $user->email_verified_at === null) {
            return;
        }

        // Only pending accounts may be promoted; blocked accounts keep their status.
        if ($user->status !== UserStatusEnum::Pending) {
            return;
        }

        $user->updateQuietly(['status' => UserStatusEnum::Active]);
    }
}

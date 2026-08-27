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
        if ($user->wasChanged('email_verified_at') && $user->email_verified_at !== null) {
            $user->updateQuietly(['status' => UserStatusEnum::Active]);
        }
    }
}

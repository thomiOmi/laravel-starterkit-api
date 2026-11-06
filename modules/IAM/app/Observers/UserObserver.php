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
        if ($user->wasChanged('email_verified_at') && $user->email_verified_at !== null
            && $user->status === UserStatusEnum::Pending) {
            // Only pending accounts may be promoted; blocked accounts keep their status.
            $user->updateQuietly(['status' => UserStatusEnum::Active]);
        }

        if ($user->wasChanged('status') && ! $user->status->allowsAuthentication()) {
            // An account that no longer allows authentication loses all of its
            // access tokens, so reactivating it cannot resurrect leaked ones.
            $user->tokens()->delete();
        }
    }
}

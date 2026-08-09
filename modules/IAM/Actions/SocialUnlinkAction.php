<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use InvalidArgumentException;
use Modules\IAM\Models\User;

final readonly class SocialUnlinkAction
{
    public function handle(User $user, string $provider): void
    {
        $account = $user->socialAccounts()->where('provider', $provider)->first();

        if ($account === null) {
            throw new InvalidArgumentException(__('validation.social_not_linked'));
        }

        if (! $user->hasPassword() && $user->socialAccounts()->count() <= 1) {
            throw new InvalidArgumentException(__('auth.social_unlink_blocked'));
        }

        $account->delete();
    }
}

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

        // Check if user has other social accounts via EXISTS query instead of full count aggregation.
        if (! $user->hasPassword() && ! $user->socialAccounts()->where('id', '!=', $account->id)->exists()) {
            throw new InvalidArgumentException(__('auth.social_unlink_blocked'));
        }

        $account->delete();
    }
}

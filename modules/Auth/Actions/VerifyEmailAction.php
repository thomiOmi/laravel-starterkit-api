<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Auth\Events\Verified;
use Modules\User\Models\User;

final readonly class VerifyEmailAction
{
    public function handle(string $id, string $hash): ?User
    {
        $user = User::find($id);

        if (! $user instanceof User) {
            return null;
        }

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return null;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            event(new Verified($user));
        }

        return $user;
    }
}

<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\DeleteAccountPayload;
use Throwable;

final readonly class DeleteAccountAction
{
    /**
     * Soft-delete the authenticated user's account after verifying the password.
     *
     * @throws ValidationException When the password does not match.
     * @throws Throwable When the account deletion transaction fails.
     */
    public function handle(User $user, DeleteAccountPayload $payload): void
    {
        if (! is_string($user->password) || ! Hash::check($payload->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('auth.password_invalid')],
            ]);
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();

            $user->delete();
        });
    }
}

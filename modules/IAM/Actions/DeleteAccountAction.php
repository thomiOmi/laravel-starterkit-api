<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\DeleteAccountPayload;

final readonly class DeleteAccountAction
{
    /**
     * Soft-delete the authenticated user's account after verifying the password.
     */
    public function handle(User $user, DeleteAccountPayload $payload): void
    {
        if (! is_string($user->password) || ! Hash::check($payload->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('auth.password_invalid')],
            ]);
        }

        $user->tokens()->delete();

        $user->delete();
    }
}

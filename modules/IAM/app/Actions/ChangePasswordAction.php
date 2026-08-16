<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\ChangePasswordPayload;

final readonly class ChangePasswordAction
{
    /**
     * Update the user password after verifying the current password.
     */
    public function handle(User $user, ChangePasswordPayload $payload): void
    {
        if (! is_string($user->password) || ! Hash::check($payload->currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('auth.password_invalid')],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($payload->password),
        ])->save();

        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        $user->tokens()
            ->where('id', '!=', $currentToken->getKey())
            ->delete();
    }
}

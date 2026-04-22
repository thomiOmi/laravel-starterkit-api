<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Auth\Events\Verified;
use Illuminate\Validation\ValidationException;
use Modules\Auth\DTOs\VerifyEmailDTO;
use Modules\User\Models\User;

class VerifyEmailAction
{
    /**
     * Execute the verify email action.
     *
     * @param  VerifyEmailDTO  $dto  The verify email data transfer object.
     *
     * @throws ValidationException
     */
    public function execute(VerifyEmailDTO $dto): string
    {
        $user = User::findOrFail($dto->id);

        if (! hash_equals((string) $dto->hash, sha1($user->getEmailForVerification()))) {
            throw ValidationException::withMessages([
                'hash' => ['Invalid verification link.'],
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return 'Email already verified.';
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return 'Email verified successfully';
    }
}

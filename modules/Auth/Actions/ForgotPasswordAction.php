<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Modules\Auth\DTOs\ForgotPasswordDTO;

/**
 * Action for handling forgot password requests.
 */
class ForgotPasswordAction
{
    /**
     * Execute the forgot password action.
     *
     * @param  ForgotPasswordDTO  $dto  The forgot password data transfer object.
     *
     * @throws ValidationException
     */
    public function execute(ForgotPasswordDTO $dto): string
    {
        $status = Password::broker()->sendResetLink(['email' => $dto->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }
}

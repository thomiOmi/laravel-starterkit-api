<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Password;

final readonly class ForgotPasswordAction
{
    public function handle(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }
}

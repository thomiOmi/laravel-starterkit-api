<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Password;

final readonly class ForgotPasswordAction
{
    public function handle(string $email): void
    {
        // Intentionally ignoring the status to prevent user enumeration.
        // Returning different messages for valid vs invalid emails (e.g.
        // Password::ResetLinkSent vs Password::InvalidUser) would allow
        // attackers to discover registered email addresses.
        Password::sendResetLink(['email' => $email]);
    }
}

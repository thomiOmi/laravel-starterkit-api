<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Contracts\Auth\PasswordBroker;

final readonly class ForgotPasswordAction
{
    public function __construct(
        private PasswordBroker $broker,
    ) {}

    public function handle(string $email): void
    {
        $this->broker->sendResetLink(['email' => $email]);
    }
}

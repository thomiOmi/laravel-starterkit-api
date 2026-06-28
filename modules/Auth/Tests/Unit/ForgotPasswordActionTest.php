<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Password;
use Modules\Auth\Actions\ForgotPasswordAction;
use Modules\User\Models\User;

it('sends password reset link', function () {
    $user = User::factory()->create();

    Password::shouldReceive('sendResetLink')
        ->once()
        ->with(['email' => $user->email]);

    $action = app(ForgotPasswordAction::class);
    $action->handle($user->email);
});

<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Actions\ResetPasswordAction;
use Modules\User\Models\User;

it('resets password with valid token', function () {
    Event::fake([PasswordReset::class]);

    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $action = app(ResetPasswordAction::class);
    $action->handle([
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
    Event::assertDispatched(PasswordReset::class, fn ($event) => $event->user->is($user));
});

it('throws validation exception for invalid token', function () {
    $user = User::factory()->create();

    $action = app(ResetPasswordAction::class);
    $action->handle([
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);
})->throws(ValidationException::class);

it('throws validation exception for non-existent email', function () {
    $action = app(ResetPasswordAction::class);
    $action->handle([
        'token' => 'some-token',
        'email' => 'nonexistent@example.com',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);
})->throws(ValidationException::class);

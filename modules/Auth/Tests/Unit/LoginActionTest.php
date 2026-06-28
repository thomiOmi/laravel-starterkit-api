<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\Payloads\V1\LoginPayload;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $password = config('auth.default_password');
    $this->user = User::factory()->create([
        'password' => $password,
    ]);
});

it('authenticates user with valid credentials', function () {
    $password = config('auth.default_password');
    $action = app(LoginAction::class);
    $payload = new LoginPayload(
        email: $this->user->email,
        password: $password,
        deviceName: 'test-device',
    );

    $result = $action->handle($payload, '127.0.0.1', 'Mozilla/5.0');

    expect($result)
        ->toHaveKeys(['user', 'access_token', 'token_type'])
        ->and($result['token_type'])->toBe('Bearer');
});

it('throws validation exception for invalid credentials', function () {
    $action = app(LoginAction::class);
    $payload = new LoginPayload(
        email: $this->user->email,
        password: 'wrong-password',
    );

    $action->handle($payload);
})->throws(ValidationException::class);

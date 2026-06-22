<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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

it('authenticates and updates token with ip and user agent', function () {
    $password = config('auth.default_password');
    Auth::login($this->user);

    $action = app(LoginAction::class);
    $payload = new LoginPayload(
        email: $this->user->email,
        password: $password,
    );

    $result = $action->handle($payload, '192.168.1.1', 'curl/7.68');

    expect($result['access_token'])->toBeString();
});

it('throws validation exception for invalid credentials', function () {
    $action = app(LoginAction::class);
    $payload = new LoginPayload(
        email: $this->user->email,
        password: 'wrong-password',
    );

    $action->handle($payload);
})->throws(ValidationException::class);

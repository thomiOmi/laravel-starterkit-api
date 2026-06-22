<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Payloads\V1\RegisterPayload;

uses(RefreshDatabase::class);

it('creates a new user', function () {
    $action = app(RegisterAction::class);
    $payload = new RegisterPayload(
        name: 'John Doe',
        email: 'john@example.com',
        password: config('auth.default_password'),
    );

    $result = $action->handle($payload);

    expect($result['user'])
        ->name->toBe('John Doe')
        ->email->toBe('john@example.com')
        ->and($result)->toHaveKeys(['access_token', 'token_type']);
});

it('creates user with hashed password', function () {
    $password = config('auth.default_password');
    $action = app(RegisterAction::class);
    $payload = new RegisterPayload(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: $password,
    );

    $result = $action->handle($payload);

    expect(Hash::check($password, $result['user']->password))->toBeTrue();
});

it('persists user in database', function () {
    $action = app(RegisterAction::class);
    $payload = new RegisterPayload(
        name: 'Persist Test',
        email: 'persist@example.com',
        password: config('auth.default_password'),
    );

    $action->handle($payload);

    $this->assertDatabaseHas('users', [
        'email' => 'persist@example.com',
        'name' => 'Persist Test',
    ]);
});

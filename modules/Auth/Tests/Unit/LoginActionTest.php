<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Illuminate\Validation\ValidationException;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\Payloads\V1\LoginPayload;
use Modules\Role\Models\Role;
use Modules\User\Models\User;

/**
 * Unit test for LoginAction focus on Abilities and Eager Loading.
 */
describe('LoginAction', function () {
    it('authenticates user with valid credentials and eager loads relations', function () {
        $password = config('auth.default_password');
        $user = User::factory()->create(['password' => $password]);

        $action = app(LoginAction::class);
        $payload = new LoginPayload(
            email: $user->email,
            password: $password,
            deviceName: 'test-device',
        );

        $result = $action->handle($payload, '127.0.0.1', 'Mozilla/5.0');

        expect($result)
            ->toHaveKeys(['user', 'access_token', 'token_type'])
            ->and($result['token_type'])->toBe('Bearer')
            ->and($result['user']->relationLoaded('roles'))->toBeTrue()
            ->and($result['user']->relationLoaded('permissions'))->toBeTrue();
    });

    it('assigns wildcard abilities to admins', function () {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $payload = new LoginPayload(
            email: $user->email,
            password: config('auth.default_password'),
            deviceName: 'test'
        );

        $action = app(LoginAction::class);
        $action->handle($payload);

        expect($user->tokens()->first()->abilities)->toBe(['*']);
    });

    it('assigns restricted abilities to regular users', function () {
        Role::create(['name' => 'user', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('user');

        $payload = new LoginPayload(
            email: $user->email,
            password: config('auth.default_password'),
            deviceName: 'test'
        );

        $action = app(LoginAction::class);
        $action->handle($payload);

        expect($user->tokens()->first()->abilities)->toContain('users:read');
    });

    it('throws validation exception for invalid credentials', function () {
        $user = User::factory()->create();
        $action = app(LoginAction::class);
        $payload = new LoginPayload(
            email: $user->email,
            password: 'wrong-password',
        );

        $action->handle($payload);
    })->throws(ValidationException::class);
});

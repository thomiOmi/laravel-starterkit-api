<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Actions\LoginAction;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\LoginPayload;

/**
 * Unit test for LoginAction focus on Abilities and Eager Loading.
 */
describe('LoginAction', function () {
    $password = 'test-password';

    it('authenticates user with valid credentials and eager loads relations', function () use ($password) {
        $user = User::factory()->create(['password' => Hash::make($password)]);

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

    it('assigns wildcard abilities to admins', function () use ($password) {
        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        $user = User::factory()->create(['password' => Hash::make($password)]);
        $user->assignRole('admin');

        $payload = new LoginPayload(
            email: $user->email,
            password: $password,
            deviceName: 'test'
        );

        $action = app(LoginAction::class);
        $action->handle($payload);

        expect($user->tokens()->first()->abilities)->toBe(['*']);
    });

    it('assigns restricted abilities to regular users', function () use ($password) {
        Role::create(['name' => 'user', 'guard_name' => 'sanctum']);
        $user = User::factory()->create(['password' => Hash::make($password)]);
        $user->assignRole('user');

        $payload = new LoginPayload(
            email: $user->email,
            password: $password,
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

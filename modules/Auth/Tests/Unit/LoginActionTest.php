<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Actions\LoginAction;
use Modules\Auth\Payloads\V1\LoginPayload;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Unit test for LoginAction focus on Abilities logic.
 */
describe('LoginAction', function () {
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
        $result = $action->handle($payload);

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
        $result = $action->handle($payload);

        expect($user->tokens()->first()->abilities)->toContain('users:read');
    });
});

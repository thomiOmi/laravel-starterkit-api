<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Models\Role;
use Modules\IAM\Services\UserAuthorizationService;

covers(UserAuthorizationService::class);

describe('UserAuthorizationService', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => RoleEnum::Admin->value, 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
    });

    it('grants wildcard abilities to admins and super-admins', function (RoleEnum $role) {
        $user = UserFactory::new()->{$role === RoleEnum::Admin ? 'admin' : 'superAdmin'}()->createOne();

        expect((new UserAuthorizationService)->determineTokenAbilities($user))->toBe(['*']);
    })->with([
        'admin' => RoleEnum::Admin,
        'super-admin' => RoleEnum::SuperAdmin,
    ]);

    it('grants scoped abilities to regular users', function () {
        $user = UserFactory::new()->createOne();
        $user->assignRole(RoleEnum::User->value);

        expect((new UserAuthorizationService)->determineTokenAbilities($user))
            ->toBe(['users:read', 'users:write', 'auth:manage']);
    });

    it('creates a token with request metadata and expiry metadata', function () {
        config()->set('sanctum.expiration', 60);
        $user = UserFactory::new()->createOne();

        $result = (new UserAuthorizationService)->createAccessToken($user, 'iPhone', '127.0.0.1', 'TestAgent');

        expect($result['access_token'])->not->toBeEmpty()
            ->and($result['token_type'])->toBe('Bearer')
            ->and($result['expires_in'])->toBe(3600)
            ->and($result['expires_at'])->not->toBeNull();

        $tokenRow = $user->tokens()->where('name', 'iPhone')->first();
        expect($tokenRow?->ip_address)->toBe('127.0.0.1')
            ->and($tokenRow?->user_agent)->toBe('TestAgent');
    });
});

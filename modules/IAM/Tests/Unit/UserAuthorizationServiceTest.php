<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Modules\IAM\Services\UserAuthorizationService;

uses(RefreshDatabase::class);

describe('UserAuthorizationService', function () {
    $service = app(UserAuthorizationService::class);

    describe('determineTokenAbilities', function () use ($service) {
        it('returns wildcard for super admin', function () use ($service) {
            Role::create(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => 'sanctum']);
            $user = User::factory()->create();
            $user->assignRole(RoleEnum::SuperAdmin);

            $abilities = $service->determineTokenAbilities($user);

            expect($abilities)->toBe(['*']);
        });

        it('returns wildcard for admin', function () use ($service) {
            Role::create(['name' => RoleEnum::Admin->value, 'guard_name' => 'sanctum']);
            $user = User::factory()->create();
            $user->assignRole(RoleEnum::Admin);

            $abilities = $service->determineTokenAbilities($user);

            expect($abilities)->toBe(['*']);
        });

        it('returns restricted abilities for regular user', function () use ($service) {
            Role::create(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
            $user = User::factory()->create();
            $user->assignRole(RoleEnum::User);

            $abilities = $service->determineTokenAbilities($user);

            expect($abilities)->toBe(['users:read', 'users:write', 'auth:manage']);
        });

        it('returns restricted abilities for user without any role', function () use ($service) {
            $user = User::factory()->create();

            $abilities = $service->determineTokenAbilities($user);

            expect($abilities)->toBe(['users:read', 'users:write', 'auth:manage']);
        });
    });

    describe('createAccessToken', function () use ($service) {
        it('creates token with restricted abilities for regular user', function () use ($service) {
            Role::create(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
            $user = User::factory()->create();
            $user->assignRole(RoleEnum::User);

            $result = $service->createAccessToken($user, 'test-device');

            expect($result)->toHaveKeys(['access_token', 'token_type', 'expires_at', 'expires_in'])
                ->and($result['token_type'])->toBe('Bearer')
                ->and($result['access_token'])->toBeString()
                ->and($result['expires_in'])->toBeInt()
                ->and($result['expires_at'])->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
        });

        it('creates token with wildcard abilities for admin', function () use ($service) {
            Role::create(['name' => RoleEnum::Admin->value, 'guard_name' => 'sanctum']);
            $user = User::factory()->create();
            $user->assignRole(RoleEnum::Admin);

            $result = $service->createAccessToken($user, 'test-device');

            expect($result['token_type'])->toBe('Bearer');
            expect($user->tokens()->first()->abilities)->toBe(['*']);
        });

        it('attaches ip address and user agent to the token', function () use ($service) {
            Role::create(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
            $user = User::factory()->create();
            $user->assignRole(RoleEnum::User);

            $service->createAccessToken($user, 'test-device', '192.168.1.1', 'Mozilla/5.0');

            $tokenRecord = $user->tokens()->first();
            expect($tokenRecord->ip_address)->toBe('192.168.1.1')
                ->and($tokenRecord->user_agent)->toBe('Mozilla/5.0');
        });

        it('returns null expires_at when expiration is disabled', function () use ($service) {
            config()->set('sanctum.expiration', 0);
            Role::create(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
            $user = User::factory()->create();
            $user->assignRole(RoleEnum::User);

            $result = $service->createAccessToken($user, 'test-device');

            expect($result['expires_at'])->toBeNull()
                ->and($result['expires_in'])->toBeNull();
        });
    });
});

<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

beforeEach(function () {
    Event::fake();
    Notification::fake();

    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'user', 'guard_name' => 'web']);
});

describe('Middleware Guarding (Verified Email)', function () {
    it('denies access to user listing if email is not verified', function () {
        /** @var TestCase $this */
        /** @var TestCase $this */
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->assignRole('admin'); // Even an admin must be verified

        $this->actingAs($user)
            ->getJson('/api/v1/users')
            ->toBeProblemResponse(status: 403);
    })->group('v1');

    it('denies access to create user if email is not verified', function () {
        /** @var TestCase $this */
        /** @var TestCase $this */
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($user)
            ->postJson('/api/v1/users', ['name' => 'Test'])
            ->toBeProblemResponse(status: 403);
    })->group('v1');
});

describe('User Registration & Initial State', function () {
    it('assigns default role and unverified state on registration', function () {
        /** @var TestCase $this */
        /** @var TestCase $this */
        $password = config('auth.default_password');
        $payload = [
            'name' => 'New Customer',
            'email' => 'customer@test.com',
            'password' => $password,
            'password_confirmation' => $password,
        ];

        $this->postJson('/api/v1/auth/register', $payload)->toBeSuccessResponse();

        $user = User::where('email', 'customer@test.com')->first();
        expect($user->email_verified_at)->toBeNull()
            ->and($user->hasRole('user'))->toBeTrue();
    })->group('v1');
});

describe('User CRUD & IDOR Protection', function () {
    it('denies access if User A updates User B profile', function () {
        /** @var TestCase $this */
        /** @var TestCase $this */
        $userA = loginAsUser(); // Logged in and verified by helper
        $userB = User::factory()->create(['name' => 'Safe']);

        $this->putJson("/api/v1/users/{$userB->id}", ['name' => 'Hacked'])
            ->toBeProblemResponse(status: 403);
    })->group('v1');

    it('prevents self-deletion', function () {
        /** @var TestCase $this */
        /** @var TestCase $this */
        $admin = loginAsUser();
        Permission::create(['name' => 'user.delete', 'guard_name' => 'web']);
        $admin->givePermissionTo('user.delete');

        $this->deleteJson("/api/v1/users/{$admin->id}")
            ->toBeProblemResponse(status: 403);
    })->group('v1');
});

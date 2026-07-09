<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Event::fake();
    Notification::fake();

    foreach (['web', 'sanctum'] as $guard) {
        Role::create(['name' => RoleEnum::Admin->value, 'guard_name' => $guard]);
        Role::create(['name' => RoleEnum::User->value, 'guard_name' => $guard]);
    }
});

describe('Middleware Guarding (Verified Email)', function () {
    it('denies access to user listing if email is not verified', function () {
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->assignRole(RoleEnum::Admin); // Even an admin must be verified

        expect($this->actingAs($user)
            ->getJson('/api/v1/users'))
            ->toBeProblemResponse(status: 403);
    })->group('v1');

    it('denies access to create user if email is not verified', function () {
        $user = User::factory()->create(['email_verified_at' => null]);

        expect($this->actingAs($user)
            ->postJson('/api/v1/users', ['name' => 'Test']))
            ->toBeProblemResponse(status: 403);
    })->group('v1');
});

describe('User Registration & Initial State', function () {
    $password = 'test-password';

    it('assigns default role and unverified state on registration', function () use ($password) {
        $payload = [
            'name' => 'New Customer',
            'email' => 'customer@test.com',
            'password' => $password,
            'password_confirmation' => $password,
        ];

        expect($this->postJson('/api/v1/auth/register', $payload))->toBeSuccessResponse(status: 201);

        $user = User::where('email', 'customer@test.com')->first();
        expect($user->email_verified_at)->toBeNull()
            ->and($user->hasRole(RoleEnum::User))->toBeTrue();
    })->group('v1');
});

describe('User CRUD & IDOR Protection', function () {
    it('denies access if User A updates User B profile', function () {
        $userA = loginAsUser(); // Logged in and verified by helper
        $userB = User::factory()->create(['name' => 'Safe']);

        expect($this->putJson("/api/v1/users/{$userB->id}", ['name' => 'Hacked']))
            ->toBeProblemResponse(status: 403);
    })->group('v1');

    it('prevents self-deletion', function () {
        $admin = loginAsUser();
        Permission::firstOrCreate(['name' => PermissionEnum::UserDelete->value, 'guard_name' => 'sanctum']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin->givePermissionTo(PermissionEnum::UserDelete);

        expect($this->deleteJson("/api/v1/users/{$admin->id}"))
            ->toBeProblemResponse(status: 403);
    })->group('v1');
});

describe('User Listing', function () {
    it('lists all users with pagination when authorized', function () {
        $admin = loginAsUser();
        Permission::firstOrCreate(['name' => PermissionEnum::UserView->value, 'guard_name' => 'sanctum']);
        $admin->givePermissionTo(PermissionEnum::UserView);
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/users');

        expect($response)->toBeSuccessResponse()->toBePaginated();
    })->group('v1');

    it('denies listing without user.view permission', function () {
        loginAsUser();

        expect($this->getJson('/api/v1/users'))
            ->toBeProblemResponse(status: 403);
    })->group('v1');

    it('can filter users by search term', function () {
        $admin = loginAsUser();
        Permission::firstOrCreate(['name' => PermissionEnum::UserView->value, 'guard_name' => 'sanctum']);
        $admin->givePermissionTo(PermissionEnum::UserView);
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);

        $response = $this->getJson('/api/v1/users?search=Alice');

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('Alice');
    })->group('v1');
});

describe('User Creation', function () {
    $password = 'test-password';

    it('creates a new user when authorized', function () use ($password) {
        $admin = loginAsUser();
        Permission::firstOrCreate(['name' => PermissionEnum::UserCreate->value, 'guard_name' => 'sanctum']);
        $admin->givePermissionTo(PermissionEnum::UserCreate);

        expect($this->postJson('/api/v1/users', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ]))->toBeSuccessResponse(status: 201);
    })->group('v1');

    it('denies creation without user.create permission', function () {
        loginAsUser();

        expect($this->postJson('/api/v1/users', [
            'name' => 'New User',
            'email' => 'new@example.com',
        ]))->toBeProblemResponse(status: 403);
    })->group('v1');
});

describe('User Show', function () {
    it('allows a user to view their own profile', function () {
        $user = loginAsUser();

        expect($this->getJson("/api/v1/users/{$user->id}"))
            ->toBeSuccessResponse()
            ->assertJsonPath('data.id', $user->id);
    })->group('v1');

    it('allows viewing another user with user.view permission', function () {
        $admin = loginAsUser();
        Permission::firstOrCreate(['name' => PermissionEnum::UserView->value, 'guard_name' => 'sanctum']);
        $admin->givePermissionTo(PermissionEnum::UserView);
        $other = User::factory()->create();

        expect($this->getJson("/api/v1/users/{$other->id}"))
            ->toBeSuccessResponse()
            ->assertJsonPath('data.id', $other->id);
    })->group('v1');

    it('denies viewing another user without user.view permission', function () {
        loginAsUser();
        $other = User::factory()->create();

        expect($this->getJson("/api/v1/users/{$other->id}"))
            ->toBeProblemResponse(status: 403);
    })->group('v1');

    it('returns 404 for a non-existent user with user.view permission', function () {
        $admin = loginAsUser();
        Permission::firstOrCreate(['name' => PermissionEnum::UserView->value, 'guard_name' => 'sanctum']);
        $admin->givePermissionTo(PermissionEnum::UserView);

        expect($this->getJson('/api/v1/users/999999'))
            ->toBeProblemResponse(status: 404);
    })->group('v1');
});

describe('User Update', function () {
    it('allows a user to update their own profile', function () {
        $user = loginAsUser();

        expect($this->putJson("/api/v1/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]))->toBeSuccessResponse()
            ->assertJsonPath('data.name', 'Updated Name');
    })->group('v1');

    it('allows updating another user with user.edit permission', function () {
        $admin = loginAsUser();
        Permission::firstOrCreate(['name' => PermissionEnum::UserEdit->value, 'guard_name' => 'sanctum']);
        $admin->givePermissionTo(PermissionEnum::UserEdit);
        $other = User::factory()->create(['name' => 'Original']);

        expect($this->putJson("/api/v1/users/{$other->id}", [
            'name' => 'Updated by Admin',
            'email' => $other->email,
        ]))->toBeSuccessResponse()
            ->assertJsonPath('data.name', 'Updated by Admin');
    })->group('v1');

    it('denies updating another user without user.edit permission', function () {
        loginAsUser();
        $other = User::factory()->create();

        expect($this->putJson("/api/v1/users/{$other->id}", [
            'name' => 'Hacked',
            'email' => $other->email,
        ]))->toBeProblemResponse(status: 403);
    })->group('v1');

    it('returns 404 when updating a non-existent user with user.edit permission', function () {
        $admin = loginAsUser();
        Permission::firstOrCreate(['name' => PermissionEnum::UserEdit->value, 'guard_name' => 'sanctum']);
        $admin->givePermissionTo(PermissionEnum::UserEdit);

        expect($this->putJson('/api/v1/users/999999', [
            'name' => 'Ghost',
            'email' => 'ghost@example.com',
        ]))->toBeProblemResponse(status: 404);
    })->group('v1');
});

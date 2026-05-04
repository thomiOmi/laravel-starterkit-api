<?php

declare(strict_types=1);

namespace Modules\AuditLog\Tests\Feature;

use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\Role\Models\Role;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('Model Activity Logging', function () {
    it('logs user creation', function () {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $user->id,
            'subject_type' => User::class,
            'description' => 'created',
        ]);
    });

    it('logs user update', function () {
        $user = User::factory()->create();
        $user->update(['name' => 'Updated Name']);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $user->id,
            'subject_type' => User::class,
            'description' => 'updated',
        ]);
    });

    it('logs role creation', function () {
        $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $role->id,
            'subject_type' => Role::class,
            'description' => 'created',
        ]);
    });
});

describe('Auth Event Logging', function () {
    it('logs login event', function () {
        $user = User::factory()->create();

        event(new Login('web', $user, false));

        $this->assertDatabaseHas('activity_log', [
            'causer_id' => $user->id,
            'log_name' => 'auth',
            'event' => 'login',
        ]);
    });
});

describe('Audit Log API Access', function () {
    it('denies access to unauthorized users', function () {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)
            ->getJson('/api/v1/audit-logs')
            ->assertForbidden();
    });

    it('allows access to admin users', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->getJson('/api/v1/audit-logs')
            ->assertSuccessful();
    });
});

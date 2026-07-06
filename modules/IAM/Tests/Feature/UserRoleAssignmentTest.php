<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Event::fake();
    Notification::fake();

    Permission::firstOrCreate(['name' => 'user.edit', 'guard_name' => 'sanctum']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

describe('User Role Management', function () {
    it('allows admin to assign roles to user and verifies database sync', function () {
        $admin = loginAsUser();
        $admin->givePermissionTo('user.edit');

        $user = User::factory()->create();
        $role = Role::create(['name' => 'editor', 'guard_name' => 'sanctum']);

        expect($this->putJson("/api/v1/users/{$user->id}/roles", [
            'roles' => ['editor'],
        ]))->toBeSuccessResponse();

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_id' => $user->id,
            'model_type' => User::class,
        ]);

        expect($user->fresh()->hasRole('editor'))->toBeTrue();
    })->group('v1');

    it('prevents privilege escalation by unauthorized users', function () {
        loginAsUser(); // Regular user
        $user = User::factory()->create();
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        expect($this->putJson("/api/v1/users/{$user->id}/roles", ['roles' => ['admin']]))
            ->toBeProblemResponse(status: 403);
    })->group('v1');

    it('returns 404 when assigning roles to a non-existent user', function () {
        $admin = loginAsUser();
        $admin->givePermissionTo('user.edit');
        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);

        expect($this->putJson('/api/v1/users/999999/roles', [
            'roles' => ['admin'],
        ]))->toBeProblemResponse(status: 404);
    })->group('v1');

    it('fails validation with non-existent role names', function () {
        $admin = loginAsUser();
        $admin->givePermissionTo('user.edit');
        $user = User::factory()->create();

        expect($this->putJson("/api/v1/users/{$user->id}/roles", [
            'roles' => ['non-existent-role'],
        ]))->toBeProblemResponse(status: 422);
    })->group('v1');
});

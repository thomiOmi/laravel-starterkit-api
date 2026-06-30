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

    Permission::create(['name' => 'user.edit', 'guard_name' => 'web']);
});

describe('User Role Management (SOP)', function () {
    it('allows admin to assign roles to user and verifies database sync', function () {
        /** @var TestCase $this */
        /** @var TestCase $this */
        $admin = loginAsUser();
        $admin->givePermissionTo('user.edit');

        $user = User::factory()->create();
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

        $this->putJson("/api/v1/users/{$user->id}/roles", [
            'roles' => ['editor'],
        ])->toBeSuccessResponse();

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_id' => $user->id,
            'model_type' => User::class,
        ]);

        expect($user->fresh()->hasRole('editor'))->toBeTrue();
    })->group('v1');

    it('prevents privilege escalation by unauthorized users', function () {
        /** @var TestCase $this */
        /** @var TestCase $this */
        loginAsUser(); // Regular user
        $user = User::factory()->create();
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $this->putJson("/api/v1/users/{$user->id}/roles", ['roles' => ['admin']])
            ->toBeProblemResponse(status: 403);
    })->group('v1');
});

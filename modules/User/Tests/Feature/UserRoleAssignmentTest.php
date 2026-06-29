<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->admin = loginAsUser();
    Permission::create(['name' => 'user.edit', 'guard_name' => 'web']);
    $this->admin->givePermissionTo('user.edit');
});

describe('User Role Assignment', function () {
    it('assigns roles to user', function () {
        $user = User::factory()->create();
        Role::create(['name' => 'editor', 'guard_name' => 'web']);

        $this->putJson("/api/v1/users/{$user->id}/roles", ['roles' => ['editor']])
            ->toBeSuccessResponse();

        expect($user->fresh()->hasRole('editor'))->toBeTrue();
    })->group('v1');
});

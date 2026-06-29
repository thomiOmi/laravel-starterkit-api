<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->admin = loginAsUser();
    Permission::create(['name' => 'user.delete', 'guard_name' => 'web']);
    Permission::create(['name' => 'user.edit', 'guard_name' => 'web']);
    $this->admin->givePermissionTo(['user.delete', 'user.edit']);
});

describe('User Bulk Actions', function () {
    it('bulk deletes users', function () {
        $users = User::factory()->count(2)->create();
        $ids = $users->pluck('id')->toArray();

        $this->postJson('/api/v1/users/bulk/delete', ['ids' => $ids])
            ->toBeSuccessResponse();

        foreach ($users as $user) {
            expect($user->fresh()->trashed())->toBeTrue();
        }
    })->group('v1');
});

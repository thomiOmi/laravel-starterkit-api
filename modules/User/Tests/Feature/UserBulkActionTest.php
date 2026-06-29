<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Event::fake();
    Notification::fake();

    Permission::create(['name' => 'user.edit', 'guard_name' => 'web']);
    Permission::create(['name' => 'user.delete', 'guard_name' => 'web']);
});

describe('User Bulk Operations', function () {
    it('allows authorized admin to bulk delete users', function () {
        $admin = loginAsUser();
        $admin->givePermissionTo('user.delete');
        $users = User::factory()->count(2)->create();
        $ids = $users->pluck('id')->toArray();

        $response = $this->postJson('/api/v1/users/bulk/delete', ['ids' => $ids]);

        $response->toBeSuccessResponse();
        foreach ($users as $user) {
            expect($user->fresh()->trashed())->toBeTrue();
        }
    })->group('v1');

    it('denies bulk delete for unauthorized users', function () {
        loginAsUser();
        $users = User::factory()->count(2)->create();

        $this->postJson('/api/v1/users/bulk/delete', ['ids' => $users->pluck('id')->toArray()])
            ->toBeProblemResponse(status: 403);
    })->group('v1');

    it('allows authorized admin to bulk restore users', function () {
        $admin = loginAsUser();
        $admin->givePermissionTo('user.edit');
        $users = User::factory()->count(2)->create();
        $ids = $users->pluck('id')->toArray();
        User::whereIn('id', $ids)->delete();

        $response = $this->postJson('/api/v1/users/bulk/restore', ['ids' => $ids]);

        $response->toBeSuccessResponse();
        foreach ($users as $user) {
            expect($user->fresh()->trashed())->toBeFalse();
        }
    })->group('v1');
});

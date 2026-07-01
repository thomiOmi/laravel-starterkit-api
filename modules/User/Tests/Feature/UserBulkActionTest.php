<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Role\Models\Permission;
use Modules\User\Models\User;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Event::fake();
    Notification::fake();

    Permission::firstOrCreate(['name' => 'user.edit', 'guard_name' => 'sanctum']);
    Permission::firstOrCreate(['name' => 'user.delete', 'guard_name' => 'sanctum']);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

describe('User Bulk Operations', function () {
    it('allows authorized admin to bulk delete users', function () {
        $admin = loginAsUser();
        $admin->givePermissionTo('user.delete');
        $users = User::factory()->count(2)->create();
        $ids = $users->pluck('id')->toArray();

        expect($this->postJson('/api/v1/users/bulk/delete', ['ids' => $ids]))
            ->toBeSuccessResponse();

        foreach ($users as $user) {
            expect($user->fresh()->trashed())->toBeTrue();
        }
    })->group('v1');

    it('denies bulk delete for regular users without permission', function () {
        loginAsUser(); // No permissions
        $users = User::factory()->count(2)->create();

        expect($this->postJson('/api/v1/users/bulk/delete', ['ids' => $users->pluck('id')->toArray()]))
            ->toBeProblemResponse(status: 403);
    })->group('v1');

    it('allows authorized admin to bulk restore users', function () {
        $admin = loginAsUser();
        $admin->givePermissionTo('user.edit');
        $users = User::factory()->count(2)->create();
        $ids = $users->pluck('id')->toArray();

        User::whereIn('id', $ids)->delete();

        expect($this->postJson('/api/v1/users/bulk/restore', ['ids' => $ids]))
            ->toBeSuccessResponse();

        foreach ($users as $user) {
            expect($user->fresh()->trashed())->toBeFalse();
        }
    })->group('v1');
});

<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\Role\Actions\BulkRestoreRolesAction;
use Modules\Role\Database\Factories\RoleFactory;
use Modules\Role\Models\Role;

describe('BulkRestoreRolesAction', function () {
    it('restores soft deleted roles', function () {
        $roles = RoleFactory::new()->count(3)->create(['guard_name' => 'web']);
        $ids = $roles->pluck('id')->toArray();
        Role::whereIn('id', $ids)->delete();

        $action = app(BulkRestoreRolesAction::class);
        $count = $action->handle($ids);

        expect($count)->toBe(3);
        foreach ($roles as $role) {
            expect($role->fresh()->trashed())->toBeFalse();
        }
    });

    it('returns 0 when no trashed roles match ids', function () {
        $action = app(BulkRestoreRolesAction::class);
        $count = $action->handle(['non-existent-id']);

        expect($count)->toBe(0);
    });

    it('clears cache for restored roles', function () {
        $roles = RoleFactory::new()->count(2)->create(['guard_name' => 'web']);
        $ids = $roles->pluck('id')->toArray();
        Role::whereIn('id', $ids)->delete();

        Cache::shouldReceive('forget')
            ->twice()
            ->with(Mockery::on(fn (string $key): bool => str_starts_with($key, 'role_')));

        $action = app(BulkRestoreRolesAction::class);
        $action->handle($ids);
    });
});

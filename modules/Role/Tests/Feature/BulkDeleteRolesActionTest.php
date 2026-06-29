<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Mockery;

namespace Modules\Role\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\Role\Actions\BulkDeleteRolesAction;
use Modules\Role\Database\Factories\RoleFactory;
use Modules\Role\Models\Role;

describe('BulkDeleteRolesAction', function () {
    it('deletes multiple roles', function () {
        $roles = RoleFactory::new()->count(3)->create(['guard_name' => 'web']);

        $action = app(BulkDeleteRolesAction::class);
        $count = $action->handle($roles->pluck('id')->toArray());

        expect($count)->toBe(3);
        foreach ($roles as $role) {
            expect($role->fresh()->trashed())->toBeTrue();
        }
    });

    it('protects super-admin role from deletion', function () {
        $superAdmin = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $other = RoleFactory::new()->create(['name' => 'editor', 'guard_name' => 'web']);

        $action = app(BulkDeleteRolesAction::class);
        $count = $action->handle([$superAdmin->id, $other->id]);

        expect($count)->toBe(1);
        expect($superAdmin->fresh()->trashed())->toBeFalse();
    });

    it('clears cache for deleted roles', function () {
        $roles = RoleFactory::new()->count(2)->create(['guard_name' => 'web']);
        $ids = $roles->pluck('id')->toArray();

        Cache::shouldReceive('forget')
            ->twice()
            ->with(Mockery::on(fn (string $key): bool => str_starts_with($key, 'role_')));

        $action = app(BulkDeleteRolesAction::class);
        $action->handle($ids);
    });
});

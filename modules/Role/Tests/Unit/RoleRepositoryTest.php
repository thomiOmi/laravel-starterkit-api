<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\Role\Database\Factories\RoleFactory;
use Modules\Role\Filters\RoleFilter;
use Modules\Role\Models\Permission;
use Modules\Role\Repositories\RoleRepository;

/**
 * Unit test for RoleRepository focus on Caching and Eager Loading.
 */
describe('RoleRepository', function () {
    it('paginates and eager loads permissions', function () {
        $role = RoleFactory::new()->create(['guard_name' => 'web']);
        $permission = Permission::firstOrCreate(['name' => 'test.view', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $repo = app(RoleRepository::class);
        $result = $repo->paginate(app(RoleFilter::class));

        expect($result->items()[0]->relationLoaded('permissions'))->toBeTrue();
    });

    it('caches roles by id with 60s TTL', function () {
        $role = RoleFactory::new()->create(['name' => 'cached', 'guard_name' => 'web']);
        $repo = app(RoleRepository::class);

        $repo->findById((string) $role->id);

        expect(Cache::has("role_{$role->id}"))->toBeTrue();
    });
});

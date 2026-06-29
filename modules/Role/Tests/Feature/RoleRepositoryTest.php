<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Modules\Role\Database\Factories\RoleFactory;
use Modules\Role\Filters\RoleFilter;
use Modules\Role\Models\Permission;
use Modules\Role\Models\Role;
use Modules\Role\Repositories\RoleRepository;

describe('RoleRepository paginate', function () {
    it('paginates all roles', function () {
        RoleFactory::new()->count(5)->create(['guard_name' => 'web']);

        $repo = app(RoleRepository::class);
        $result = $repo->paginate(app(RoleFilter::class));

        expect($result->total())->toBe(5)
            ->and($result->currentPage())->toBe(1);
    });

    it('respects custom page size', function () {
        RoleFactory::new()->count(15)->create(['guard_name' => 'web']);

        $repo = app(RoleRepository::class);
        $result = $repo->paginate(app(RoleFilter::class), pageSize: 5);

        expect($result->perPage())->toBe(5)
            ->and($result->total())->toBe(15);
    });

    it('eager loads permissions', function () {
        $role = RoleFactory::new()->create(['guard_name' => 'web']);
        $permission = Permission::create(['name' => 'role.repo.view', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $repo = app(RoleRepository::class);
        $result = $repo->paginate(app(RoleFilter::class));

        expect($result->items()[0]->relationLoaded('permissions'))->toBeTrue()
            ->and($result->items()[0]->permissions->first()->name)->toBe('role.repo.view');
    });

    it('defaults page to 1 when null', function () {
        RoleFactory::new()->count(3)->create(['guard_name' => 'web']);

        $repo = app(RoleRepository::class);
        $result = $repo->paginate(app(RoleFilter::class), pageSize: 10, page: null);

        expect($result->currentPage())->toBe(1);
    });
});

describe('RoleRepository findById', function () {
    it('finds a role by id', function () {
        $role = RoleFactory::new()->create(['name' => 'test-manager', 'guard_name' => 'web']);

        $repo = app(RoleRepository::class);
        $found = $repo->findById((string) $role->id);

        expect($found)->toBeInstanceOf(Role::class)
            ->id->toBe($role->id);
    });

    it('eager loads permissions on findById', function () {
        $role = RoleFactory::new()->create(['name' => 'editor-role', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'editor.view', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $repo = app(RoleRepository::class);
        $found = $repo->findById((string) $role->id);

        expect($found->relationLoaded('permissions'))->toBeTrue()
            ->and($found->permissions->first()->name)->toBe('editor.view');
    });

    it('returns null for non-existent id', function () {
        $repo = app(RoleRepository::class);
        $found = $repo->findById('non-existent');

        expect($found)->toBeNull();
    });

    it('caches role after first retrieval', function () {
        $role = RoleFactory::new()->create(['name' => 'cached-role', 'guard_name' => 'web']);

        $repo = app(RoleRepository::class);

        $first = $repo->findById((string) $role->id);
        expect($first)->not->toBeNull();

        $role->delete();

        $cached = $repo->findById((string) $role->id);
        expect($cached)->toBeInstanceOf(Role::class)
            ->name->toBe('cached-role');
    });
});

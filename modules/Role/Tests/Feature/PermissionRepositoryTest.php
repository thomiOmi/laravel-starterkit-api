<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Modules\Role\Database\Factories\PermissionFactory;
use Modules\Role\Filters\PermissionFilter;
use Modules\Role\Models\Permission;
use Modules\Role\Repositories\PermissionRepository;

describe('PermissionRepository paginate', function () {
    it('paginates all permissions', function () {
        PermissionFactory::new()->count(5)->create();

        $repo = app(PermissionRepository::class);
        $result = $repo->paginate(app(PermissionFilter::class));

        expect($result->total())->toBe(5)
            ->and($result->currentPage())->toBe(1);
    });

    it('respects custom page size', function () {
        PermissionFactory::new()->count(15)->create();

        $repo = app(PermissionRepository::class);
        $result = $repo->paginate(app(PermissionFilter::class), pageSize: 5);

        expect($result->perPage())->toBe(5)
            ->and($result->total())->toBe(15);
    });

    it('paginates to requested page', function () {
        PermissionFactory::new()->count(15)->create();

        $repo = app(PermissionRepository::class);
        $result = $repo->paginate(app(PermissionFilter::class), pageSize: 5, page: 2);

        expect($result->currentPage())->toBe(2)
            ->and($result->count())->toBe(5);
    });

    it('sorts by name by default', function () {
        PermissionFactory::new()->create(['name' => 'zebra.view']);
        PermissionFactory::new()->create(['name' => 'alpha.view']);

        $repo = app(PermissionRepository::class);
        $result = $repo->paginate(app(PermissionFilter::class));

        expect($result->items()[0]->name)->toBe('alpha.view');
    });
});

describe('PermissionRepository findById', function () {
    it('finds a permission by id', function () {
        $permission = PermissionFactory::new()->create(['name' => 'test.find']);

        $repo = app(PermissionRepository::class);
        $found = $repo->findById((string) $permission->id);

        expect($found)->toBeInstanceOf(Permission::class)
            ->id->toBe($permission->id);
    });

    it('returns null for non-existent id', function () {
        $repo = app(PermissionRepository::class);
        $found = $repo->findById('non-existent');

        expect($found)->toBeNull();
    });

    it('caches permission after first retrieval', function () {
        $permission = PermissionFactory::new()->create(['name' => 'cache.test']);

        $repo = app(PermissionRepository::class);

        $first = $repo->findById((string) $permission->id);
        expect($first)->not->toBeNull();

        $permission->delete();

        $cached = $repo->findById((string) $permission->id);
        expect($cached)->toBeInstanceOf(Permission::class)
            ->id->toBe($permission->id);
    });
});

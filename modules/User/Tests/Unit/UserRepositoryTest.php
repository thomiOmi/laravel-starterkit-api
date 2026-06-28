<?php

declare(strict_types=1);

use Modules\Role\Models\Permission;
use Modules\Role\Models\Role;
use Modules\User\Filters\UserFilter;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

describe('UserRepository paginate', function () {
    it('paginates all users', function () {
        User::factory()->count(5)->create();

        $repo = app(UserRepository::class);
        $result = $repo->paginate(app(UserFilter::class));

        expect($result->total())->toBe(5)
            ->and($result->currentPage())->toBe(1);
    });

    it('respects custom page size', function () {
        User::factory()->count(15)->create();

        $repo = app(UserRepository::class);
        $result = $repo->paginate(app(UserFilter::class), pageSize: 5);

        expect($result->perPage())->toBe(5)
            ->and($result->total())->toBe(15);
    });

    it('eager loads roles and permissions', function () {
        $role = Role::create(['name' => 'reviewer', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'review.view', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        $repo = app(UserRepository::class);
        $result = $repo->paginate(app(UserFilter::class));

        $first = $result->items()[0];

        expect($first->relationLoaded('roles'))->toBeTrue()
            ->and($first->relationLoaded('permissions'))->toBeTrue()
            ->and($first->roles->first()->name)->toBe('reviewer');
    });
});

describe('UserRepository findById', function () {
    it('finds a user by id', function () {
        $user = User::factory()->create(['name' => 'Test User']);

        $repo = app(UserRepository::class);
        $found = $repo->findById((string) $user->id);

        expect($found)->toBeInstanceOf(User::class)
            ->id->toBe($user->id)
            ->name->toBe('Test User');
    });

    it('eager loads roles and permissions on findById', function () {
        $role = Role::create(['name' => 'tester', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $repo = app(UserRepository::class);
        $found = $repo->findById((string) $user->id);

        expect($found->relationLoaded('roles'))->toBeTrue()
            ->and($found->relationLoaded('permissions'))->toBeTrue()
            ->and($found->roles->first()->name)->toBe('tester');
    });

    it('returns null for non-existent id', function () {
        $repo = app(UserRepository::class);
        $found = $repo->findById('non-existent');

        expect($found)->toBeNull();
    });

    it('caches user after first retrieval', function () {
        $user = User::factory()->create(['name' => 'Cached User']);

        $repo = app(UserRepository::class);

        $first = $repo->findById((string) $user->id);
        expect($first)->not->toBeNull();

        $user->delete();

        $cached = $repo->findById((string) $user->id);
        expect($cached)->toBeInstanceOf(User::class)
            ->name->toBe('Cached User');
    });
});

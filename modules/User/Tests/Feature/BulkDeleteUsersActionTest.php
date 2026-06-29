<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Mockery;

namespace Modules\User\Tests\Feature;

use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Actions\BulkDeleteUsersAction;
use Modules\User\Models\User;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');

    $this->actingAs($this->admin);
});

describe('BulkDeleteUsersAction', function () {
    it('soft deletes multiple users', function () {
        $users = User::factory()->count(3)->create();

        $action = app(BulkDeleteUsersAction::class);
        $count = $action->handle($users->pluck('id')->toArray());

        expect($count)->toBe(3);
        foreach ($users as $user) {
            expect($user->fresh()->trashed())->toBeTrue();
        }
    });

    it('skips the authenticated user from deletion', function () {
        User::factory()->count(2)->create();

        $action = app(BulkDeleteUsersAction::class);
        $count = $action->handle([
            $this->admin->id,
            ...User::where('id', '!=', $this->admin->id)->pluck('id')->toArray(),
        ]);

        expect($count)->toBe(2);
        expect($this->admin->fresh()->trashed())->toBeFalse();
    });

    it('returns 0 when only current user ids are provided', function () {
        $action = app(BulkDeleteUsersAction::class);
        $count = $action->handle([$this->admin->id]);

        expect($count)->toBe(0);
    });

    it('clears cache for deleted users', function () {
        $users = User::factory()->count(2)->create();
        $ids = $users->pluck('id')->toArray();

        Cache::shouldReceive('forget')
            ->twice()
            ->with(Mockery::on(fn (string $key): bool => str_starts_with($key, 'user_')));

        $action = app(BulkDeleteUsersAction::class);
        $action->handle($ids);
    });
});

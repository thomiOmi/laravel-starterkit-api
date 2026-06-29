<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Mockery;
use Modules\User\Actions\BulkRestoreUsersAction;
use Modules\User\Models\User;

describe('BulkRestoreUsersAction', function () {
    it('restores soft deleted users', function () {
        $users = User::factory()->count(3)->create();
        User::whereIn('id', $users->pluck('id'))->delete();

        $action = app(BulkRestoreUsersAction::class);
        $count = $action->handle($users->pluck('id')->toArray());

        expect($count)->toBe(3);
        foreach ($users as $user) {
            expect($user->fresh()->trashed())->toBeFalse();
        }
    });

    it('returns 0 when no trashed users match ids', function () {
        User::factory()->count(2)->create();

        $action = app(BulkRestoreUsersAction::class);
        $count = $action->handle(['non-existent-id']);

        expect($count)->toBe(0);
    });

    it('clears cache for restored users', function () {
        $users = User::factory()->count(2)->create();
        $ids = $users->pluck('id')->toArray();
        User::whereIn('id', $ids)->delete();

        Cache::shouldReceive('forget')
            ->twice()
            ->with(Mockery::on(fn (string $key): bool => str_starts_with($key, 'user_')));

        $action = app(BulkRestoreUsersAction::class);
        $action->handle($ids);
    });
});

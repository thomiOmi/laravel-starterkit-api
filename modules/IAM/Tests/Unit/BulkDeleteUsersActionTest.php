<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Cache;
use Modules\IAM\Actions\BulkDeleteUsersAction;
use Modules\IAM\Models\User;

describe('BulkDeleteUsersAction', function () {
    it('deletes users except self', function () {
        $auth = app(Guard::class);
        $admin = User::factory()->create();
        $auth->setUser($admin);
        $targets = User::factory()->count(3)->create();
        $action = new BulkDeleteUsersAction($auth);

        $count = $action->handle($targets->pluck('id')->toArray());

        expect($count)->toBe(3);
        foreach ($targets as $user) {
            expect($user->fresh()->trashed())->toBeTrue();
        }
        expect($admin->fresh()->trashed())->toBeFalse();
    });

    it('excludes self from deletion', function () {
        $auth = app(Guard::class);
        $user = User::factory()->create();
        $auth->setUser($user);
        $action = new BulkDeleteUsersAction($auth);

        $count = $action->handle([$user->id]);

        expect($count)->toBe(0);
        expect($user->fresh()->trashed())->toBeFalse();
    });

    it('forgets cache for each deleted user', function () {
        Cache::spy();
        $auth = app(Guard::class);
        $admin = User::factory()->create();
        $auth->setUser($admin);
        $target = User::factory()->create();
        $action = new BulkDeleteUsersAction($auth);

        $action->handle([$target->id]);

        Cache::shouldHaveReceived('deleteMultiple')
            ->with(["user_{$target->id}"])
            ->once();
    });
});

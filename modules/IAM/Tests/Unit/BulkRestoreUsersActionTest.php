<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\IAM\Actions\BulkRestoreUsersAction;
use Modules\IAM\Models\User;

describe('BulkRestoreUsersAction', function () {
    it('restores trashed users', function () {
        $user = User::factory()->create();
        $user->delete();
        $action = app(BulkRestoreUsersAction::class);

        $count = $action->handle([$user->id]);

        expect($count)->toBe(1);
        expect($user->fresh()->trashed())->toBeFalse();
    });

    it('forgets cache for each restored user', function () {
        Cache::spy();
        $user = User::factory()->create();
        $user->delete();
        $action = app(BulkRestoreUsersAction::class);

        $_ = $action->handle([$user->id]);

        Cache::shouldHaveReceived('forget')
            ->with("user_{$user->id}")
            ->once();
    });
});

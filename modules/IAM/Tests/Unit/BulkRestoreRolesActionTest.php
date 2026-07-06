<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\IAM\Actions\BulkRestoreRolesAction;
use Modules\IAM\Models\Role;

describe('BulkRestoreRolesAction', function () {
    it('restores trashed roles', function () {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $role->delete();
        $action = app(BulkRestoreRolesAction::class);

        $count = $action->handle([$role->id]);

        expect($count)->toBe(1);
        expect($role->fresh()->trashed())->toBeFalse();
    });

    it('forgets cache for each restored role', function () {
        Cache::spy();
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $role->delete();
        $action = app(BulkRestoreRolesAction::class);

        $action->handle([$role->id]);

        Cache::shouldHaveReceived('forget')
            ->with("role_{$role->id}")
            ->once();
    });
});

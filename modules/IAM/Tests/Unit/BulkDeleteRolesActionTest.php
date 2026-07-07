<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\IAM\Actions\BulkDeleteRolesAction;
use Modules\IAM\Models\Role;

describe('BulkDeleteRolesAction', function () {
    it('deletes roles except super-admin', function () {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        Role::create(['name' => Role::SUPER_ADMIN, 'guard_name' => 'web']);
        $action = app(BulkDeleteRolesAction::class);

        $count = $action->handle([$role->id]);

        expect($count)->toBe(1);
        expect($role->fresh()->trashed())->toBeTrue();
    });

    it('does not delete super-admin role', function () {
        $saRole = Role::create(['name' => Role::SUPER_ADMIN, 'guard_name' => 'web']);
        $action = app(BulkDeleteRolesAction::class);

        $count = $action->handle([$saRole->id]);

        expect($count)->toBe(0);
    });

    it('forgets cache for each role', function () {
        Cache::spy();
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $action = app(BulkDeleteRolesAction::class);

        $_ = $action->handle([$role->id]);

        Cache::shouldHaveReceived('forget')
            ->with("role_{$role->id}")
            ->once();
    });
});

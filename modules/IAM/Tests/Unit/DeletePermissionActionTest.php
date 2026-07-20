<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\DeletePermissionAction;
use Modules\IAM\Models\Permission;

describe('DeletePermissionAction', function () {
    it('deletes an existing permission', function () {
        $perm = Permission::create(['name' => 'to.delete', 'guard_name' => 'sanctum']);
        $action = app(DeletePermissionAction::class);

        expect($action->handle($perm))->toBeTrue();
        expect(Permission::where('id', $perm->id)->exists())->toBeFalse();
    });

    it('returns false for a deleted model', function () {
        $perm = Permission::create(['name' => 'to.delete', 'guard_name' => 'sanctum']);
        $perm->delete();
        $action = app(DeletePermissionAction::class);

        expect($action->handle($perm))->toBeFalse();
    });
});

<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\IAM\Actions\DeletePermissionAction;
use Modules\IAM\Models\Permission;

describe('DeletePermissionAction', function () {
    it('deletes an existing permission', function () {
        $perm = Permission::create(['name' => 'to.delete', 'guard_name' => 'sanctum']);
        $action = app(DeletePermissionAction::class);

        expect($action->handle($perm->id))->toBeTrue();
        expect(Permission::where('id', $perm->id)->exists())->toBeFalse();
    });

    it('throws exception for a non-existent permission', function () {
        $action = app(DeletePermissionAction::class);

        expect(fn () => $action->handle('999999'))->toThrow(ModelNotFoundException::class);
    });
});

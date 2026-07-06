<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\DeleteRoleAction;
use Modules\IAM\Models\Role;

describe('DeleteRoleAction', function () {
    it('deletes an existing role', function () {
        $role = Role::create(['name' => 'to.delete', 'guard_name' => 'web']);
        $action = app(DeleteRoleAction::class);

        expect($action->handle($role->id))->toBeTrue();
        expect($role->fresh()->trashed())->toBeTrue();
    });

    it('returns false for a non-existent role', function () {
        $action = app(DeleteRoleAction::class);

        expect($action->handle('999999'))->toBeFalse();
    });

    it('returns false for the super-admin role', function () {
        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $action = app(DeleteRoleAction::class);

        expect($action->handle($role->id))->toBeFalse();
        expect($role->fresh()->trashed())->toBeFalse();
    });
});

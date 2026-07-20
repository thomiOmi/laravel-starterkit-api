<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use App\Enums\RoleEnum;
use Modules\IAM\Actions\DeleteRoleAction;
use Modules\IAM\Models\Role;

describe('DeleteRoleAction', function () {
    it('deletes an existing role', function () {
        $role = Role::create(['name' => 'to.delete', 'guard_name' => 'sanctum']);
        $action = app(DeleteRoleAction::class);

        expect($action->handle($role))->toBeTrue();
        expect($role->fresh())->toBeNull();
    });

    it('returns false for a deleted model', function () {
        $role = Role::create(['name' => 'to.delete', 'guard_name' => 'sanctum']);
        $role->delete();
        $action = app(DeleteRoleAction::class);

        expect($action->handle($role))->toBeFalse();
    });

    it('returns false for the super-admin role', function () {
        $role = Role::create(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => 'sanctum']);
        $action = app(DeleteRoleAction::class);

        expect($action->handle($role))->toBeFalse();
        expect($role->fresh())->not->toBeNull();
    });
});

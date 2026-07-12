<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\IAM\Actions\DeleteRoleAction;
use Modules\IAM\Models\Role;

describe('DeleteRoleAction', function () {
    it('deletes an existing role', function () {
        $role = Role::create(['name' => 'to.delete', 'guard_name' => 'sanctum']);
        $action = app(DeleteRoleAction::class);

        expect($action->handle($role->id))->toBeTrue();
        expect($role->fresh())->toBeNull();
    });

    it('throws exception for a non-existent role', function () {
        $action = app(DeleteRoleAction::class);

        expect(fn () => $action->handle('999999'))->toThrow(ModelNotFoundException::class);
    });

    it('returns false for the super-admin role', function () {
        $role = Role::create(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => 'sanctum']);
        $action = app(DeleteRoleAction::class);

        expect($action->handle($role->id))->toBeFalse();
        expect($role->fresh())->not->toBeNull();
    });
});

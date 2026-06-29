<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Feature;

use Modules\Role\Actions\DeleteRoleAction;
use Modules\Role\Database\Factories\RoleFactory;
use Modules\Role\Models\Role;

describe('DeleteRoleAction', function () {
    it('deletes a role', function () {
        $role = RoleFactory::new()->create(['name' => 'editor', 'guard_name' => 'web']);

        $action = app(DeleteRoleAction::class);
        $result = $action->handle((string) $role->id);

        expect($result)->toBeTrue();
        expect($role->fresh()->trashed())->toBeTrue();
    });

    it('prevents deleting super-admin role', function () {
        $superAdmin = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $action = app(DeleteRoleAction::class);
        $result = $action->handle((string) $superAdmin->id);

        expect($result)->toBeFalse();
        expect($superAdmin->fresh()->trashed())->toBeFalse();
    });

    it('returns false for non-existent role', function () {
        $action = app(DeleteRoleAction::class);
        $result = $action->handle('non-existent');

        expect($result)->toBeFalse();
    });
});

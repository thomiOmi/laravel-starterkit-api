<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use App\Enums\RoleEnum;
use Modules\IAM\Actions\BulkDeleteRolesAction;
use Modules\IAM\Models\Role;

describe('BulkDeleteRolesAction', function () {
    it('deletes roles except super-admin', function () {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'sanctum']);
        Role::create(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => 'sanctum']);
        $action = app(BulkDeleteRolesAction::class);

        $count = $action->handle([$role->id]);

        expect($count)->toBe(1);
        expect($role->fresh())->toBeNull();
    });

    it('does not delete super-admin role', function () {
        $saRole = Role::create(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => 'sanctum']);
        $action = app(BulkDeleteRolesAction::class);

        $count = $action->handle([$saRole->id]);

        expect($count)->toBe(0);
    });
});

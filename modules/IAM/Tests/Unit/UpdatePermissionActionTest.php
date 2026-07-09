<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\UpdatePermissionAction;
use Modules\IAM\Models\Permission;
use Modules\IAM\Payloads\V1\PermissionPayload;

describe('UpdatePermissionAction', function () {
    it('updates an existing permission', function () {
        $perm = Permission::create(['name' => 'old.name', 'guard_name' => 'sanctum']);
        $action = app(UpdatePermissionAction::class);

        $result = $action->handle($perm->id, new PermissionPayload(
            name: 'new.name',
            guardName: 'web',
        ));

        expect($result)->toBeInstanceOf(Permission::class)
            ->name->toBe('new.name');
    });

    it('returns null for a non-existent permission', function () {
        $action = app(UpdatePermissionAction::class);

        $result = $action->handle('999999', new PermissionPayload(
            name: 'ghost',
            guardName: 'web',
        ));

        expect($result)->toBeNull();
    });
});

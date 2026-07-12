<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\IAM\Actions\UpdatePermissionAction;
use Modules\IAM\Models\Permission;
use Modules\IAM\Payloads\V1\PermissionPayload;

describe('UpdatePermissionAction', function () {
    it('updates an existing permission', function () {
        $perm = Permission::create(['name' => 'old.name', 'guard_name' => 'sanctum']);
        $action = app(UpdatePermissionAction::class);

        $result = $action->handle($perm->id, new PermissionPayload(
            name: 'new.name',
        ));

        expect($result)->toBeInstanceOf(Permission::class)
            ->name->toBe('new.name');
    });

    it('throws exception for a non-existent permission', function () {
        $action = app(UpdatePermissionAction::class);

        expect(fn () => $action->handle('999999', new PermissionPayload(
            name: 'ghost',
        )))->toThrow(ModelNotFoundException::class);
    });
});

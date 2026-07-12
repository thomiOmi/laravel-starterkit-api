<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\CreatePermissionAction;
use Modules\IAM\Models\Permission;
use Modules\IAM\Payloads\V1\PermissionPayload;

describe('CreatePermissionAction', function () {
    it('creates a new permission', function () {
        $action = app(CreatePermissionAction::class);

        $perm = $action->handle(new PermissionPayload(
            name: 'custom.perm',
        ));

        expect($perm)->toBeInstanceOf(Permission::class)
            ->name->toBe('custom.perm');
    });
});

<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\ShowPermissionAction;
use Modules\IAM\Models\Permission;

describe('ShowPermissionAction', function () {
    it('returns the permission model', function () {
        $perm = Permission::create(['name' => 'test.perm', 'guard_name' => 'sanctum']);
        $action = app(ShowPermissionAction::class);

        $result = $action->handle($perm);

        expect($result)->toBeInstanceOf(Permission::class)
            ->id->toBe($perm->id);
    });
});

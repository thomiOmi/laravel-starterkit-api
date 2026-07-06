<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\ListPermissionsAction;
use Modules\IAM\Filters\PermissionFilter;
use Modules\IAM\Models\Permission;

describe('ListPermissionsAction', function () {
    it('returns paginated permissions', function () {
        Permission::create(['name' => 'test.perm', 'guard_name' => 'web']);
        $action = app(ListPermissionsAction::class);

        $result = $action->handle(app(PermissionFilter::class));

        expect($result->total())->toBeGreaterThanOrEqual(1);
    });
});

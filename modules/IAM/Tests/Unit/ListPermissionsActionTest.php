<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Support\Facades\Request;
use Modules\IAM\Actions\ListPermissionsAction;
use Modules\IAM\Models\Permission;

describe('ListPermissionsAction', function () {
    it('returns paginated permissions', function () {
        Permission::create(['name' => 'test.perm', 'guard_name' => 'sanctum']);
        $action = app(ListPermissionsAction::class);

        $result = $action->handle(20, 1);

        expect($result->total())->toBeGreaterThanOrEqual(1);
    });

    it('applies the search filter', function () {
        Permission::create(['name' => 'user.edit', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'user.view', 'guard_name' => 'sanctum']);

        Request::merge(['search' => 'edit']);

        $action = app(ListPermissionsAction::class);
        $result = $action->handle(20, 1);

        expect($result->total())->toBe(1)
            ->and($result->first()->name)->toBe('user.edit');
    });
});

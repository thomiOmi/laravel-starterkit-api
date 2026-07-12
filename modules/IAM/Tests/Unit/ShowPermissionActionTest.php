<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\IAM\Actions\ShowPermissionAction;
use Modules\IAM\Models\Permission;

describe('ShowPermissionAction', function () {
    it('finds an existing permission by id', function () {
        $perm = Permission::create(['name' => 'test.perm', 'guard_name' => 'sanctum']);
        $action = app(ShowPermissionAction::class);

        $result = $action->handle($perm->id);

        expect($result)->toBeInstanceOf(Permission::class)
            ->id->toBe($perm->id);
    });

    it('throws exception for a non-existent permission', function () {
        $action = app(ShowPermissionAction::class);

        expect(fn () => $action->handle('999999'))->toThrow(ModelNotFoundException::class);
    });
});

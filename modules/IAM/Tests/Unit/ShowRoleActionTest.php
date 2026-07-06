<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\ShowRoleAction;
use Modules\IAM\Models\Role;

describe('ShowRoleAction', function () {
    it('finds an existing role by id', function () {
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'web']);
        $action = app(ShowRoleAction::class);

        $result = $action->handle($role->id);

        expect($result)->toBeInstanceOf(Role::class)
            ->id->toBe($role->id);
    });

    it('returns null for a non-existent role', function () {
        $action = app(ShowRoleAction::class);

        expect($action->handle('999999'))->toBeNull();
    });
});

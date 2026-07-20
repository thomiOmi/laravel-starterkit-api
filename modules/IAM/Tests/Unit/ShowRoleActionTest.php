<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\ShowRoleAction;
use Modules\IAM\Models\Role;

describe('ShowRoleAction', function () {
    it('returns the role with permissions loaded', function () {
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'sanctum']);
        $action = app(ShowRoleAction::class);

        $result = $action->handle($role);

        expect($result)->toBeInstanceOf(Role::class)
            ->id->toBe($role->id);
    });
});

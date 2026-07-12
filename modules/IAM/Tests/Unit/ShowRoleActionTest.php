<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\IAM\Actions\ShowRoleAction;
use Modules\IAM\Models\Role;

describe('ShowRoleAction', function () {
    it('finds an existing role by id', function () {
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'sanctum']);
        $action = app(ShowRoleAction::class);

        $result = $action->handle($role->id);

        expect($result)->toBeInstanceOf(Role::class)
            ->id->toBe($role->id);
    });

    it('throws exception for a non-existent role', function () {
        $action = app(ShowRoleAction::class);

        expect(fn () => $action->handle('999999'))->toThrow(ModelNotFoundException::class);
    });
});

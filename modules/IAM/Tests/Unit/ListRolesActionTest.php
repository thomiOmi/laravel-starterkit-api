<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use App\Enums\RoleEnum;
use Modules\IAM\Actions\ListRolesAction;
use Modules\IAM\Filters\RoleFilter;
use Modules\IAM\Models\Role;

describe('ListRolesAction', function () {
    it('returns paginated roles', function () {
        Role::create(['name' => RoleEnum::Admin->value, 'guard_name' => 'sanctum']);
        $action = app(ListRolesAction::class);

        $result = $action->handle(app(RoleFilter::class));

        expect($result->total())->toBeGreaterThanOrEqual(1);
    });
});

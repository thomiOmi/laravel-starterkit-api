<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Request;
use Modules\IAM\Actions\ListRolesAction;
use Modules\IAM\Models\Role;

describe('ListRolesAction', function () {
    it('returns paginated roles', function () {
        Role::create(['name' => RoleEnum::Admin->value, 'guard_name' => 'sanctum']);
        $action = app(ListRolesAction::class);

        $result = $action->handle();

        expect($result->total())->toBeGreaterThanOrEqual(1);
    });

    it('applies the search filter', function () {
        Role::create(['name' => 'editor', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'viewer', 'guard_name' => 'sanctum']);

        Request::merge(['search' => 'edit']);

        $action = app(ListRolesAction::class);
        $result = $action->handle();

        expect($result->total())->toBe(1)
            ->and($result->first()->name)->toBe('editor');
    });
});

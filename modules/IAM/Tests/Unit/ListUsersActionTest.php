<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\Request;
use Modules\IAM\Actions\ListUsersAction;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;

describe('ListUsersAction', function () {
    it('returns paginated users', function () {
        User::factory()->count(5)->create();
        $action = app(ListUsersAction::class);

        $result = $action->handle();

        expect($result->total())->toBe(5);
    });

    it('applies the role filter', function () {
        Role::firstOrCreate(['name' => RoleEnum::Admin->value, 'guard_name' => 'sanctum']);
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);
        User::factory()->count(3)->create();

        Request::merge(['filter' => ['role' => RoleEnum::Admin->value]]);

        $action = app(ListUsersAction::class);
        $result = $action->handle();

        expect($result->total())->toBe(1)
            ->and($result->first()->id)->toBe($admin->id);
    });

    it('applies the active status filter', function () {
        $active = User::factory()->create(['status' => UserStatus::Active]);
        User::factory()->count(3)->create(['status' => UserStatus::Inactive]);

        Request::merge(['filter' => ['status' => 'active']]);

        $action = app(ListUsersAction::class);
        $result = $action->handle();

        expect($result->total())->toBe(1)
            ->and($result->first()->id)->toBe($active->id);
    });
});

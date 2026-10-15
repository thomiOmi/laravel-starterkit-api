<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Modules\IAM\Actions\AssignRolesToUserAction;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Models\Role;

covers(AssignRolesToUserAction::class);

describe('AssignRolesToUserAction', function (): void {
    beforeEach(function (): void {
        foreach ([RoleEnum::SuperAdmin, RoleEnum::Admin, RoleEnum::User] as $role) {
            Role::query()->firstOrCreate(['name' => $role->value, 'guard_name' => 'sanctum']);
        }
    });

    it('syncs roles on the target user', function (): void {
        $user = UserFactory::new()->createOne();
        $action = resolve(AssignRolesToUserAction::class);

        $result = $action->handle($user, [RoleEnum::Admin->value, RoleEnum::User->value]);

        expect($result->is($user))->toBeTrue()
            ->and($user->refresh()->hasRole(RoleEnum::Admin->value))->toBeTrue()
            ->and($user->hasRole(RoleEnum::User->value))->toBeTrue()
            ->and($user->hasRole(RoleEnum::SuperAdmin->value))->toBeFalse();
    });

    it('replaces existing roles when given a new list or detaches all when empty', function (): void {
        $user = UserFactory::new()->createOne();
        $user->assignRole(RoleEnum::Admin->value);

        $action = resolve(AssignRolesToUserAction::class);

        $action->handle($user, [RoleEnum::User->value]);

        expect($user->refresh()->hasRole(RoleEnum::Admin->value))->toBeFalse()
            ->and($user->hasRole(RoleEnum::User->value))->toBeTrue();

        $action->handle($user, []);

        expect($user->refresh()->roles)->toBeEmpty();
    });
});

<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Policies\RolePolicy;

covers(RolePolicy::class);

describe('RolePolicy', function () {
    beforeEach(function () {
        foreach ([PermissionEnum::RoleView, PermissionEnum::RoleCreate, PermissionEnum::RoleEdit, PermissionEnum::RoleDelete] as $permission) {
            Permission::firstOrCreate(['name' => $permission->value, 'guard_name' => 'sanctum']);
        }

        foreach ([RoleEnum::SuperAdmin, RoleEnum::User] as $role) {
            Role::firstOrCreate(['name' => $role->value, 'guard_name' => 'sanctum']);
        }
    });

    it('grants view and create purely by permission', function () {
        $policy = new RolePolicy;
        $actor = UserFactory::new()->createOne();
        $actor->givePermissionTo(PermissionEnum::RoleView->value, PermissionEnum::RoleCreate->value);
        $role = Role::query()->where('name', RoleEnum::User->value)->firstOrFail();

        expect($policy->view($actor, $role))->toBeTrue()
            ->and($policy->create($actor))->toBeTrue();
    });

    it('blocks view and create without permission', function () {
        $policy = new RolePolicy;
        $plain = UserFactory::new()->createOne();
        $role = Role::query()->where('name', RoleEnum::User->value)->firstOrFail();

        expect($policy->view($plain, $role))->toBeFalse()
            ->and($policy->create($plain))->toBeFalse();
    });

    it('permits update/delete on normal roles with permission', function () {
        $policy = new RolePolicy;
        $editor = UserFactory::new()->createOne();
        $editor->givePermissionTo(PermissionEnum::RoleEdit->value, PermissionEnum::RoleDelete->value);
        $role = Role::query()->where('name', RoleEnum::User->value)->firstOrFail();

        expect($policy->update($editor, $role))->toBeTrue()
            ->and($policy->delete($editor, $role))->toBeTrue();
    });

    it('blocks update and delete on the super-admin role even with permission', function () {
        $policy = new RolePolicy;
        $editor = UserFactory::new()->createOne();
        $editor->givePermissionTo(PermissionEnum::RoleEdit->value, PermissionEnum::RoleDelete->value);
        $superAdmin = Role::query()->where('name', RoleEnum::SuperAdmin->value)->firstOrFail();

        expect($policy->update($editor, $superAdmin))->toBeFalse()
            ->and($policy->delete($editor, $superAdmin))->toBeFalse();
    });
});

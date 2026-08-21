<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Policies\UserPolicy;

covers(UserPolicy::class);

pest()->use(RefreshDatabase::class);

describe('UserPolicy', function () {
    beforeEach(function () {
        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate(['name' => $permission->value, 'guard_name' => 'sanctum']);
        }

        foreach ([RoleEnum::SuperAdmin, RoleEnum::Admin, RoleEnum::User] as $role) {
            Role::firstOrCreate(['name' => $role->value, 'guard_name' => 'sanctum']);
        }
    });

    it('allows view for self or with permission', function () {
        $policy = new UserPolicy;
        $owner = UserFactory::new()->createOne();
        $other = UserFactory::new()->createOne();
        $viewer = UserFactory::new()->createOne();
        $viewer->givePermissionTo(PermissionEnum::UserView->value);

        expect($policy->view($owner, $owner))->toBeTrue()
            ->and($policy->view($viewer, $owner))->toBeTrue()
            ->and($policy->view($other, $owner))->toBeFalse();
    });

    it('allows create only with permission', function () {
        $policy = new UserPolicy;
        $creator = UserFactory::new()->createOne();
        $creator->givePermissionTo(PermissionEnum::UserCreate->value);
        $plain = UserFactory::new()->createOne();

        expect($policy->create($creator))->toBeTrue()
            ->and($policy->create($plain))->toBeFalse();
    });

    it('blocks update for super-admin target', function () {
        $policy = new UserPolicy;
        $actor = UserFactory::new()->createOne();
        $actor->givePermissionTo(PermissionEnum::UserEdit->value);
        $superAdmin = UserFactory::new()->superAdmin()->createOne();

        expect($policy->update($actor, $superAdmin))->toBeFalse();
    });

    it('allows update for self or with permission', function () {
        $policy = new UserPolicy;
        $owner = UserFactory::new()->createOne();
        $editor = UserFactory::new()->createOne();
        $editor->givePermissionTo(PermissionEnum::UserEdit->value);
        $other = UserFactory::new()->createOne();

        expect($policy->update($owner, $owner))->toBeTrue()
            ->and($policy->update($editor, $owner))->toBeTrue()
            ->and($policy->update($other, $owner))->toBeFalse();
    });

    it('blocks delete for self and super-admin', function () {
        $policy = new UserPolicy;
        $actor = UserFactory::new()->createOne();
        $actor->givePermissionTo(PermissionEnum::UserDelete->value);
        $superAdmin = UserFactory::new()->superAdmin()->createOne();

        expect($policy->delete($actor, $actor))->toBeFalse()
            ->and($policy->delete($actor, $superAdmin))->toBeFalse()
            ->and($policy->delete($actor, UserFactory::new()->createOne()))->toBeTrue();
    });
});

<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Models\Permission;
use Modules\IAM\Policies\PermissionPolicy;

covers(PermissionPolicy::class);

describe('PermissionPolicy', function () {
    beforeEach(function () {
        foreach ([PermissionEnum::PermissionView, PermissionEnum::PermissionCreate, PermissionEnum::PermissionEdit, PermissionEnum::PermissionDelete] as $permission) {
            Permission::firstOrCreate(['name' => $permission->value, 'guard_name' => 'sanctum']);
        }
    });

    it('grants every ability purely by the matching permission', function (string $method, PermissionEnum $permission) {
        $policy = new PermissionPolicy;
        $actor = UserFactory::new()->createOne();
        $actor->givePermissionTo($permission->value);

        expect($policy->{$method}($actor, new Permission))->toBeTrue();
    })->with([
        'view' => ['view', PermissionEnum::PermissionView],
        'create' => ['create', PermissionEnum::PermissionCreate],
        'update' => ['update', PermissionEnum::PermissionEdit],
        'delete' => ['delete', PermissionEnum::PermissionDelete],
    ]);

    it('blocks everything without permissions', function () {
        $policy = new PermissionPolicy;
        $plain = UserFactory::new()->createOne();

        expect($policy->view($plain, new Permission))->toBeFalse()
            ->and($policy->create($plain))->toBeFalse()
            ->and($policy->update($plain, new Permission))->toBeFalse()
            ->and($policy->delete($plain, new Permission))->toBeFalse();
    });
});

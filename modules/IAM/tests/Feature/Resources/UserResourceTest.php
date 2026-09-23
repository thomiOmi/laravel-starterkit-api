<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Resources\UserResource;
use Modules\IAM\Models\Role;

covers(UserResource::class);

describe('UserResource', function (): void {
    it('exposes core fields and omits relations unless loaded', function (): void {
        $user = UserFactory::new()->createOne();

        $data = new UserResource($user)->resolve(new Request);

        expect($data['id'])->toBe($user->id)
            ->and($data['email'])->toBe($user->email)
            ->and($data)->not->toHaveKey('roles')
            ->and($data)->not->toHaveKey('permissions');
    });

    it('includes roles when loaded', function (): void {
        Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => 'sanctum']);
        $user = UserFactory::new()->superAdmin()->createOne();
        $user->load('roles');

        $data = new UserResource($user)->resolve(new Request);

        expect($data['roles'])->toContain(RoleEnum::SuperAdmin->value);
    });
});

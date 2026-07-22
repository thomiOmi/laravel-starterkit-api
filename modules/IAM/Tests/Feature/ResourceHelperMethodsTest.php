<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use Illuminate\Http\Resources\MissingValue;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\RoleResource;
use Modules\IAM\Resources\UserResource;

test('getRoles returns MissingValue when roles not loaded', function (): void {
    $user = User::factory()->make();
    $resource = UserResource::make($user);

    $data = $resource->toArray(request());

    expect($data['roles'])->toBeInstanceOf(MissingValue::class);
});

test('getPermissions returns MissingValue when permissions not loaded', function (): void {
    $user = User::factory()->make();
    $resource = UserResource::make($user);

    $data = $resource->toArray(request());

    expect($data['permissions'])->toBeInstanceOf(MissingValue::class);
});

test('getPermissions on RoleResource returns MissingValue when permissions not loaded', function (): void {
    $role = Role::factory()->make();
    $resource = RoleResource::make($role);

    $data = $resource->toArray(request());

    expect($data['permissions'])->toBeInstanceOf(MissingValue::class);
});

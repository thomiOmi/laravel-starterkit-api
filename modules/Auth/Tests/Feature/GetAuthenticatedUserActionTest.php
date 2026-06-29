<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Modules\Auth\Actions\GetAuthenticatedUserAction;
use Modules\Role\Models\Role;
use Modules\User\Models\User;

it('loads roles and permissions for authenticated user', function () {
    $role = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    $action = app(GetAuthenticatedUserAction::class);
    $loaded = $action->handle($user);

    expect($loaded->relationLoaded('roles'))->toBeTrue()
        ->and($loaded->relationLoaded('permissions'))->toBeTrue()
        ->and($loaded->roles->first()->name)->toBe('viewer');
});

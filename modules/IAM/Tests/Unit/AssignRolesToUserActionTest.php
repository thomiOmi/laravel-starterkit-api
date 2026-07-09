<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\AssignRolesToUserAction;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;

describe('AssignRolesToUserAction', function () {
    it('syncs roles to a user', function () {
        $user = User::factory()->create();
        Role::create(['name' => 'editor', 'guard_name' => 'sanctum']);
        $action = app(AssignRolesToUserAction::class);

        $result = $action->handle($user, ['editor']);

        expect($result)->toBeInstanceOf(User::class);
        expect($result->hasRole('editor'))->toBeTrue();
    });
});

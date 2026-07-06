<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\GetAuthenticatedUserAction;
use Modules\IAM\Models\User;

describe('GetAuthenticatedUserAction', function () {
    it('loads roles and permissions on the user', function () {
        $user = User::factory()->create();
        $action = app(GetAuthenticatedUserAction::class);

        $result = $action->handle($user);

        expect($result->relationLoaded('roles'))->toBeTrue();
        expect($result->relationLoaded('permissions'))->toBeTrue();
    });
});

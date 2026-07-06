<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\LogoutAction;
use Modules\IAM\Models\User;

describe('LogoutAction', function () {
    it('deletes the current access token', function () {
        $user = User::factory()->create();
        $token = $user->createToken('session');
        $user->withAccessToken($token->accessToken);
        $action = app(LogoutAction::class);

        $action->handle($user);

        expect($user->tokens()->count())->toBe(0);
    });
});

<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Actions\LogoutOtherDevicesAction;
use Modules\User\Models\User;

/**
 * Unit test for LogoutOtherDevicesAction.
 */
describe('LogoutOtherDevicesAction', function () {
    it('deletes all user tokens except the current one', function () {
        $user = User::factory()->create();
        $currentToken = $user->createToken('current')->accessToken;
        $user->createToken('other-1');
        $user->createToken('other-2');

        expect($user->tokens()->count())->toBe(3);

        $user->withAccessToken($currentToken);

        $action = app(LogoutOtherDevicesAction::class);
        $action->handle($user, (string) $currentToken->id);

        expect($user->tokens()->count())->toBe(1)
            ->and($user->tokens()->first()->id)->toBe($currentToken->id);
    });
});

<?php

declare(strict_types=1);

use Modules\IAM\Actions\LogoutOtherDevicesAction;
use Modules\IAM\Database\Factories\UserFactory;

covers(LogoutOtherDevicesAction::class);

describe('LogoutOtherDevicesAction', function (): void {
    it('revokes all tokens except current access token', function (): void {
        $user = UserFactory::new()->createOne();

        $token1 = $user->createToken('device-1');
        $token2 = $user->createToken('device-2');
        $token3 = $user->createToken('device-3');

        $user->withAccessToken($token2->accessToken);

        $action = resolve(LogoutOtherDevicesAction::class);
        $action->handle($user);

        expect($user->tokens()->count())->toBe(1)
            ->and($user->tokens()->first()?->id)->toBe($token2->accessToken->id);
    });
});

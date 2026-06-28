<?php

declare(strict_types=1);

use Modules\Auth\Actions\LogoutAction;
use Modules\User\Models\User;

it('deletes current access token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-device');
    $tokenId = $token->accessToken->getKey();

    $user->withAccessToken($token->accessToken);
    $action = app(LogoutAction::class);

    $action->handle($user);

    expect($user->tokens()->find($tokenId))->toBeNull();
});

it('does not remove other tokens when logging out', function () {
    $user = User::factory()->create();
    $user->createToken('device-1');
    $token2 = $user->createToken('device-2');

    $user->withAccessToken($token2->accessToken);
    $action = app(LogoutAction::class);

    $action->handle($user);

    expect($user->tokens()->count())->toBe(1);
});

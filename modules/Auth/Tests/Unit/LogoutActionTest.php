<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Actions\LogoutAction;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

it('deletes current access token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-device');
    $tokenId = $token->accessToken->getKey();

    $user->withAccessToken($token->accessToken);
    $action = app(LogoutAction::class);

    $action->handle($user, stateful: false);

    expect($user->tokens()->find($tokenId))->toBeNull();
});

it('does not remove other tokens when logging out', function () {
    $user = User::factory()->create();
    $user->createToken('device-1');
    $token2 = $user->createToken('device-2');

    $user->withAccessToken($token2->accessToken);
    $action = app(LogoutAction::class);

    $action->handle($user, stateful: false);

    expect($user->tokens()->count())->toBe(1);
});

it('logs out web guard when stateful', function () {
    $user = User::factory()->create();
    auth()->guard('web')->loginUsingId($user->id);

    expect(auth()->guard('web')->check())->toBeTrue();

    $action = app(LogoutAction::class);
    $action->handle($user, stateful: true);

    expect(auth()->guard('web')->check())->toBeFalse();
});

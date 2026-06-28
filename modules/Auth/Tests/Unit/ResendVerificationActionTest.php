<?php

declare(strict_types=1);

use Modules\Auth\Actions\ResendVerificationAction;
use Modules\User\Models\User;

describe('ResendVerificationAction', function () {
    it('sends verification notification for unverified user', function () {
        $user = User::factory()->unverified()->create();

        $action = app(ResendVerificationAction::class);
        $result = $action->handle($user);

        expect($result)->toBe(__('auth.verification_link_sent'));
    });

    it('returns already verified message for verified user', function () {
        $user = User::factory()->create();

        $action = app(ResendVerificationAction::class);
        $result = $action->handle($user);

        expect($result)->toBe(__('auth.verified'));
    });
});

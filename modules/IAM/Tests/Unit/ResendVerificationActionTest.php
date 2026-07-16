<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\ResendVerificationAction;
use Modules\IAM\Models\User;

describe('ResendVerificationAction', function () {
    it('returns verified message when email is already verified', function () {
        $user = User::factory()->unverified()->create();
        $user->markEmailAsVerified();
        $action = app(ResendVerificationAction::class);

        $result = $action->handle($user);

        expect($result)->toBe(__('auth.email_verified'));
    });

    it('sends verification notification for unverified user', function () {
        $user = User::factory()->unverified()->create();
        $action = app(ResendVerificationAction::class);

        $result = $action->handle($user);

        expect($result)->toBe(__('auth.email_verification_sent'));
    });
});

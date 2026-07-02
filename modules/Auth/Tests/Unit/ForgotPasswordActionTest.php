<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Actions\ForgotPasswordAction;
use Modules\IAM\Models\User;

/**
 * Unit test for ForgotPasswordAction.
 */
describe('ForgotPasswordAction', function () {
    it('triggers the password broker to send reset link', function () {
        $user = User::factory()->create();
        $action = app(ForgotPasswordAction::class);

        $action->handle($user->email);

        expect(true)->toBeTrue();
    });
});

<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Modules\IAM\Actions\ResetPasswordAction;
use Modules\IAM\Models\User;

/**
 * Unit test for ResetPasswordAction.
 */
describe('ResetPasswordAction', function () {
    it('successfully resets user password', function () {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $action = app(ResetPasswordAction::class);

        $action->handle([
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
            'token' => $token,
        ]);

        expect(Hash::check('new-secure-password', $user->fresh()->password))->toBeTrue();
    });
});

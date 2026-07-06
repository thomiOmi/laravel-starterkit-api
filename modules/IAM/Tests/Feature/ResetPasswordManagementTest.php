<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Modules\IAM\Models\User;

describe('Reset Password', function () {
    it('resets password with a valid token', function () {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);
        $newPassword = 'new-secure-password123!';

        expect($this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]))->toBeSuccessResponse();

        expect(Hash::check($newPassword, $user->fresh()->password))->toBeTrue();
    })->group('v1');

    it('fails with an invalid reset token', function () {
        $user = User::factory()->create();

        expect($this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password123!',
            'password_confirmation' => 'new-password123!',
        ]))->toBeProblemResponse(status: 422);
    })->group('v1');
});

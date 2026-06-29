<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Modules\User\Models\User;

describe('Password Management', function () {
    it('requests a password reset link', function () {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->toBeSuccessResponse();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    })->group('v1');
});

describe('Email Verification', function () {
    it('resends verification notification', function () {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($user, ['users:write'])
            ->postJson('/api/v1/auth/email/verification-notification')
            ->toBeSuccessResponse();
    })->group('v1');
});

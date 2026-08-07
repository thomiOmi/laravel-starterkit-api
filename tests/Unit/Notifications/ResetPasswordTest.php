<?php

declare(strict_types=1);

use App\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\IAM\Database\Factories\UserFactory;

covers(ResetPassword::class);

describe('ResetPassword notification', function () {

    it('implements ShouldQueue', function () {
        $reflection = new ReflectionClass(ResetPassword::class);

        expect($reflection->implementsInterface(ShouldQueue::class))->toBeTrue();
    });

    it('uses Queueable trait', function () {
        expect(Queueable::class)->toBeIn(class_uses(ResetPassword::class));
    });

});

describe('ResetPassword frontend URL generation', function (): void {
    it('builds a frontend URL with the token and an encoded email', function (): void {
        $user = UserFactory::new()->makeOne(['email' => 'john+reset@example.com']);

        $mail = (new ResetPassword('token-123'))->toMail($user);

        expect($mail->actionUrl)
            ->toStartWith(config('app.frontend_url').'/reset-password?')
            ->toContain('token=token-123')
            ->toContain('email=john%2Breset%40example.com');
    });
});

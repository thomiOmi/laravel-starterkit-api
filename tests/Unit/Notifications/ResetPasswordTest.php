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
        expect(Queueable::class)->toBeIn(class_uses(ResetPassword::class) ?: []);
    });

});

describe('ResetPassword frontend URL generation', function (): void {
    it('builds a frontend URL with the token and an encoded email', function (): void {
        $user = UserFactory::new()->makeOne(['email' => 'john+reset@example.com']);

        $mail = new ResetPassword('token-123')->toMail($user);

        expect($mail->actionUrl)
            ->toStartWith(config('app.frontend_url').'/reset-password?')
            ->toContain('token=token-123')
            ->toContain('email=john%2Breset%40example.com');
    });
});

describe('ResetPassword mail content', function (): void {
    it('builds a translated mail message in the current locale', function (): void {
        $user = UserFactory::new()->makeOne(['email' => 'john+reset@example.com']);

        $mail = new ResetPassword('token-123')->toMail($user);

        expect($mail->subject)->toBe(__('auth.password_reset_subject'))
            ->and($mail->introLines)->toBe([__('auth.password_reset_line')])
            ->and($mail->actionText)->toBe(__('auth.password_reset_action'))
            ->and($mail->actionUrl)->toStartWith(config('app.frontend_url').'/reset-password?')
            ->and($mail->outroLines)->toBe([
                __('auth.password_reset_expire', [
                    'count' => config()->integer('auth.passwords.'.config()->string('auth.defaults.passwords').'.expire', 60),
                ]),
                __('auth.password_reset_footer'),
            ]);
    });

    it('uses Indonesian translations when the locale is set to id', function (): void {
        app()->setLocale('id');

        $user = UserFactory::new()->makeOne();

        $mail = new ResetPassword('token-123')->toMail($user);

        expect($mail->subject)->toBe(__('auth.password_reset_subject'))
            ->and($mail->introLines)->toBe([__('auth.password_reset_line')])
            ->and($mail->outroLines)->toBe([
                __('auth.password_reset_expire', [
                    'count' => config()->integer('auth.passwords.'.config()->string('auth.defaults.passwords').'.expire', 60),
                ]),
                __('auth.password_reset_footer'),
            ]);
    });
});

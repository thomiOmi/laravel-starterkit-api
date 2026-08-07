<?php

declare(strict_types=1);

use App\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Modules\IAM\Database\Factories\UserFactory;

covers(VerifyEmail::class);

describe('VerifyEmail notification', function () {

    it('implements ShouldQueue', function () {
        $reflection = new ReflectionClass(VerifyEmail::class);

        expect($reflection->implementsInterface(ShouldQueue::class))->toBeTrue();
    });

    it('uses Queueable trait', function () {
        expect(Queueable::class)->toBeIn(class_uses(VerifyEmail::class));
    });

});

describe('VerifyEmail frontend URL generation', function (): void {
    it('builds a frontend URL with the signed parameters', function (): void {
        $user = UserFactory::new()->makeOne([
            'id' => Str::ulid()->toString(),
            'email' => 'verify@example.com',
        ]);

        $mail = (new VerifyEmail)->toMail($user);

        expect($mail->actionUrl)
            ->toStartWith(config('app.frontend_url').'/verify-email?')
            ->toContain('id='.$user->id)
            ->toContain('hash='.hash('sha1', $user->email))
            ->toContain('expires=')
            ->toContain('signature=');
    });
});

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\IAM\Actions\LoginAction;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Payloads\V1\LoginPayload;

covers(LoginAction::class);

pest()->use(RefreshDatabase::class);

describe('LoginAction', function () {
    it('authenticates with valid credentials and returns token', function () {
        $user = UserFactory::new()->createOne([
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $payload = new LoginPayload(email: 'jane@example.com', password: 'password123', deviceName: 'iPhone');

        $result = app(LoginAction::class)->handle($payload, '127.0.0.1', 'TestAgent');

        expect($result['user']->is($user))->toBeTrue()
            ->and($result['access_token'])->not->toBeEmpty()
            ->and($result['token_type'])->toBe('Bearer');
    });

    it('throws for unknown email', function () {
        $payload = new LoginPayload(email: 'ghost@example.com', password: 'password123');

        expect(fn () => app(LoginAction::class)->handle($payload))
            ->toThrow(ValidationException::class);
    });

    it('throws for wrong password', function () {
        UserFactory::new()->createOne(['email' => 'jane@example.com', 'password' => 'correct']);

        $payload = new LoginPayload(email: 'jane@example.com', password: 'wrong');

        expect(fn () => app(LoginAction::class)->handle($payload))
            ->toThrow(ValidationException::class);
    });

    it('blocks banned and suspended users', function () {
        $banned = UserFactory::new()->banned()->createOne(['email' => 'banned@example.com', 'password' => 'password123']);
        $suspended = UserFactory::new()->suspended()->createOne(['email' => 'suspended@example.com', 'password' => 'password123']);

        $payloadBanned = new LoginPayload(email: 'banned@example.com', password: 'password123');
        $payloadSuspended = new LoginPayload(email: 'suspended@example.com', password: 'password123');

        expect(fn () => app(LoginAction::class)->handle($payloadBanned))
            ->toThrow(ValidationException::class, __($banned->status->blockedMessageKey()))
            ->and(fn () => app(LoginAction::class)->handle($payloadSuspended))->toThrow(ValidationException::class, __($suspended->status->blockedMessageKey()));
    });

    it('allows pending users to authenticate', function () {
        UserFactory::new()->pending()->createOne([
            'email' => 'pending@example.com',
            'password' => 'password123',
        ]);

        $payload = new LoginPayload(email: 'pending@example.com', password: 'password123');

        $result = app(LoginAction::class)->handle($payload);

        expect($result['user']->email)->toBe('pending@example.com');
    });
});

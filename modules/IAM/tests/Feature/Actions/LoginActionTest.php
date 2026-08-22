<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Modules\IAM\Actions\LoginAction;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Payloads\V1\LoginPayload;

covers(LoginAction::class);

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

    it('blocks users with a non-authenticatable status', function (string $state) {
        $factory = match ($state) {
            'banned' => UserFactory::new()->banned(),
            'suspended' => UserFactory::new()->suspended(),
            'inactive' => UserFactory::new()->inactive(),
            default => throw new InvalidArgumentException("Unknown blocked state [{$state}]."),
        };

        $user = $factory->createOne(['email' => "{$state}@example.com", 'password' => 'password123']);

        $payload = new LoginPayload(email: "{$state}@example.com", password: 'password123');

        expect(fn () => app(LoginAction::class)->handle($payload))
            ->toThrow(ValidationException::class, __($user->status->blockedMessageKey()));
    })->with([
        'banned',
        'suspended',
        'inactive',
    ]);

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

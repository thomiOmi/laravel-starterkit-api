<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Modules\User\Actions\CreateUserAction;
use Modules\User\Events\UserCreated;
use Modules\User\Payloads\V1\UserPayload;

describe('CreateUserAction', function () {
    beforeEach(function () {
        $this->withoutDefer();
    });

    it('creates a user and dispatches UserCreated event', function () {
        Event::fake();

        $password = config('auth.default_password');
        $payload = new UserPayload(
            name: 'John Doe',
            email: 'john@example.com',
            password: $password,
        );

        $action = app(CreateUserAction::class);
        $user = $action->handle($payload);

        expect($user->name)->toBe('John Doe');
        expect($user->email)->toBe('john@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        Event::assertDispatched(UserCreated::class, function (UserCreated $event) use ($user) {
            return $event->user->id === $user->id;
        });
    });

    it('creates a user without password for optional field', function () {
        $payload = new UserPayload(
            name: 'Jane Doe',
            email: 'jane@example.com',
        );

        $action = app(CreateUserAction::class);
        $user = $action->handle($payload);

        expect($user->name)->toBe('Jane Doe');
        expect($user->email)->toBe('jane@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    });
});

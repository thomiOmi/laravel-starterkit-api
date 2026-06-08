<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Payloads\V1\RegisterPayload;
use Modules\User\Models\User;

/**
 * Action for registering a new user.
 */
final readonly class RegisterAction
{
    /**
     * Create a new RegisterAction instance.
     */
    public function __construct(
        private DatabaseManager $database
    ) {}

    /**
     * Execute the register action.
     *
     * Creates the user, assigns the default 'user' role,
     * and triggers the email verification notification.
     *
     * @param  RegisterPayload  $payload  The registration payload.
     * @return User The newly created user.
     */
    public function handle(RegisterPayload $payload): User
    {
        return $this->database->transaction(function () use ($payload) {
            $user = User::create([
                'name' => $payload->name,
                'email' => $payload->email,
                'password' => Hash::make($payload->password),
            ]);

            $user->assignRole('user');

            $user->sendEmailVerificationNotification();

            return $user;
        });
    }
}

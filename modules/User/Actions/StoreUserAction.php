<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Hash;
use Modules\User\Events\UserCreated;
use Modules\User\Models\User;
use Modules\User\Payloads\V1\UserPayload;

/**
 * Action for creating a new user.
 */
final readonly class StoreUserAction
{
    /**
     * Create a new StoreUserAction instance.
     */
    public function __construct(
        private DatabaseManager $database
    ) {}

    /**
     * Execute the create user action.
     *
     * @param  UserPayload  $payload  The user payload.
     * @return User The newly created user instance.
     */
    public function handle(UserPayload $payload): User
    {
        return $this->database->transaction(function () use ($payload) {
            $user = User::create([
                'name' => $payload->name,
                'email' => $payload->email,
                'password' => $payload->password ? Hash::make($payload->password) : null,
            ]);

            UserCreated::dispatch($user);

            return $user;
        });
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\Auth\Payloads\V1\RegisterPayload;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

/**
 * Action for registering a new user.
 */
final readonly class RegisterAction
{
    /**
     * Create a new RegisterAction instance.
     */
    public function __construct(
        private DatabaseManager $database,
        private UserRepository $userRepository
    ) {}

    /**
     * Execute the registration action.
     *
     * @param  RegisterPayload  $payload  The registration payload.
     * @return User The newly registered user.
     */
    public function handle(RegisterPayload $payload): User
    {
        return $this->database->transaction(function () use ($payload) {
            return $this->userRepository->create([
                'name' => $payload->name,
                'email' => $payload->email,
                'password' => $payload->password,
            ]);
        });
    }
}

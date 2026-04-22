<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Modules\User\DTOs\UserDTO;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

class RegisterAction
{
    /**
     * Create a new RegisterAction instance.
     */
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    /**
     * Execute the register action.
     *
     * @param  UserDTO  $dto  The user data transfer object.
     * @return array{user: User, access_token: string, token_type: string}
     */
    public function execute(UserDTO $dto): array
    {
        $user = $this->userRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
        ]);

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }
}

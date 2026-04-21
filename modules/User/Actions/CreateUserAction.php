<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Support\Facades\Hash;
use Modules\User\DTOs\UserDTO;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

class CreateUserAction
{
    /**
     * Create a new CreateUserAction instance.
     *
     * @param  UserRepository  $userRepository  The user repository.
     */
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    /**
     * Execute the create user action.
     *
     * @param  UserDTO  $dto  The user data transfer object.
     */
    public function execute(UserDTO $dto): User
    {
        return $this->userRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
        ]);
    }
}

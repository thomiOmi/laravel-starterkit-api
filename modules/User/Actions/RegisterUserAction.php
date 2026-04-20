<?php

namespace Modules\User\Actions;

use Illuminate\Support\Facades\Hash;
use Modules\User\DTOs\UserDTO;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

class RegisterUserAction
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    public function execute(UserDTO $dto): User
    {
        return $this->userRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
        ]);
    }
}

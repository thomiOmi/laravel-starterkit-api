<?php

namespace Modules\User\Actions;

use Illuminate\Support\Facades\Hash;
use Modules\User\DTOs\UserDTO;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

class UpdateUserAction
{
    /**
     * Create a new UpdateUserAction instance.
     *
     * @param  UserRepository  $userRepository  The user repository.
     */
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    /**
     * Execute the update user action.
     *
     * @param  string|int  $id  The user ID.
     * @param  UserDTO  $dto  The user data transfer object.
     */
    public function execute(string|int $id, UserDTO $dto): bool
    {
        $data = [
            'name' => $dto->name,
            'email' => $dto->email,
        ];

        if ($dto->password) {
            $data['password'] = Hash::make($dto->password);
        }

        return $this->userRepository->update($id, $data);
    }
}

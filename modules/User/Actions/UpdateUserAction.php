<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\DTOs\UserDTO;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

/**
 * Action for updating an existing user.
 */
class UpdateUserAction
{
    /**
     * Create a new UpdateUserAction instance.
     *
     * @param  UserRepository  $userRepository  The user repository instance.
     */
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    /**
     * Execute the update user action.
     *
     * @param  string|int  $id  The user ID.
     * @param  UserDTO  $dto  The user data transfer object.
     * @return User The updated user instance.
     */
    public function execute(string|int $id, UserDTO $dto): User
    {
        $data = [
            'name' => $dto->name,
            'email' => $dto->email,
        ];

        if ($dto->password) {
            $data['password'] = $dto->password;
        }

        /** @var User */
        return $this->userRepository->update($id, $data);
    }
}

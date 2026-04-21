<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Repositories\UserRepository;

class DeleteUserAction
{
    /**
     * Create a new DeleteUserAction instance.
     *
     * @param  UserRepository  $userRepository  The user repository.
     */
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    /**
     * Execute the delete user action.
     *
     * @param  string|int  $id  The user ID.
     */
    public function execute(string|int $id): bool
    {
        return $this->userRepository->delete($id);
    }
}

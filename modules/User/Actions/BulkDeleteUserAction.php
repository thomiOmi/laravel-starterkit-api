<?php

namespace Modules\User\Actions;

use Modules\User\Repositories\UserRepository;

class BulkDeleteUserAction
{
    /**
     * Create a new BulkDeleteUserAction instance.
     *
     * @param  UserRepository  $userRepository  The user repository.
     */
    public function __construct(protected UserRepository $userRepository) {}

    /**
     * Execute the bulk delete user action.
     *
     * @param  array  $ids  The user IDs to delete.
     * @return int The number of deleted users.
     */
    public function execute(array $ids): int
    {
        return $this->userRepository->bulkDelete($ids);
    }
}

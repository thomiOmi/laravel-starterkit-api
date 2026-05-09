<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Repositories\UserRepository;

/**
 * Action for performing bulk delete on users.
 */
class BulkDeleteUserAction
{
    /**
     * Create a new BulkDeleteUserAction instance.
     *
     * @param  UserRepository  $userRepository  The user repository instance.
     */
    public function __construct(protected UserRepository $userRepository) {}

    /**
     * Execute the bulk delete user action.
     *
     * @param  array<int, string|int>  $ids  The user IDs to delete.
     * @return int The number of deleted users.
     */
    public function execute(array $ids): int
    {
        return $this->userRepository->bulk($ids, 'delete');
    }
}

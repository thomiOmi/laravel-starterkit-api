<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\User\Repositories\UserRepository;

/**
 * Action for bulk restoring users.
 */
final readonly class BulkRestoreUsersAction
{
    /**
     * Create a new BulkRestoreUsersAction instance.
     */
    public function __construct(
        private DatabaseManager $database,
        private UserRepository $repository
    ) {}

    /**
     * Execute the bulk restore users action.
     *
     * @param  array<int, string|int>  $ids  The user IDs to restore.
     * @return int The number of users restored.
     */
    public function handle(array $ids): int
    {
        return $this->database->transaction(function () use ($ids) {
            return $this->repository->bulkRestore($ids);
        });
    }
}

<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\Role\Repositories\RoleRepository;

/**
 * Action for bulk deleting roles.
 */
final readonly class BulkDeleteRolesAction
{
    /**
     * Create a new BulkDeleteRolesAction instance.
     */
    public function __construct(
        private DatabaseManager $database,
        private RoleRepository $repository
    ) {}

    /**
     * Execute the bulk delete roles action.
     *
     * @param  array<int, string|int>  $ids  The role IDs to delete.
     * @return int The number of roles deleted.
     */
    public function handle(array $ids): int
    {
        return $this->database->transaction(function () use ($ids) {
            return $this->repository->bulkDelete($ids);
        });
    }
}

<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\Role\Models\Role;

/**
 * Action for bulk restoring roles.
 */
final readonly class BulkRestoreRolesAction
{
    /**
     * Create a new BulkRestoreRolesAction instance.
     */
    public function __construct(
        private DatabaseManager $database
    ) {}

    /**
     * Execute the bulk restore roles action.
     *
     * @param  array<int, string|int>  $ids  The role IDs to restore.
     * @return int The number of roles restored.
     */
    public function handle(array $ids): int
    {
        return $this->database->transaction(function () use ($ids): int {
            return Role::onlyTrashed()
                ->whereIn('id', $ids)
                ->toBase()
                ->update([
                    (new Role)->getDeletedAtColumn() => null,
                ]);
        });
    }
}

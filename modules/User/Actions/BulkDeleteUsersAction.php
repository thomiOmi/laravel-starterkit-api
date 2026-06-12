<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\User\Models\User;

/**
 * Action for bulk deleting users.
 */
final readonly class BulkDeleteUsersAction
{
    /**
     * Create a new BulkDeleteUsersAction instance.
     */
    public function __construct(
        private DatabaseManager $database
    ) {}

    /**
     * Execute the bulk delete users action.
     *
     * @param  array<int, string|int>  $ids  The user IDs to delete.
     * @return int The number of users deleted.
     */
    public function handle(array $ids): int
    {
        $ids = array_filter($ids, fn ($id) => $id !== auth()->id());

        if (empty($ids)) {
            return 0;
        }

        return $this->database->transaction(function () use ($ids) {
            /** @var int $deletedCount */
            $deletedCount = User::whereIn('id', $ids)->delete();

            return $deletedCount;
        });
    }
}

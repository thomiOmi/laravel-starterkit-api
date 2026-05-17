<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\User\Models\User;

/**
 * Action for performing bulk delete on users.
 */
final readonly class BulkDeleteUserAction
{
    /**
     * Create a new BulkDeleteUserAction instance.
     */
    public function __construct(
        private DatabaseManager $database
    ) {}

    /**
     * Execute the bulk delete user action.
     *
     * @param  array<int, string|int>  $ids  The user IDs to delete.
     * @return int The number of deleted users.
     */
    public function handle(array $ids): int
    {
        return $this->database->transaction(function () use ($ids) {
            return User::whereIn('id', $ids)->delete();
        });
    }
}

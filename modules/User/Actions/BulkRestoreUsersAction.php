<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Models\User;

final readonly class BulkRestoreUsersAction
{
    /**
     * @param  array<int, string|int>  $ids
     */
    public function handle(array $ids): int
    {
        /** @var int $count */
        $count = User::onlyTrashed()->whereIn('id', $ids)->restore();

        return $count;
    }
}

<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\User;

final readonly class BulkRestoreUsersAction
{
    /**
     * @param  array<int, string|int>  $ids
     */
    #[\NoDiscard]
    public function handle(array $ids): int
    {
        foreach ($ids as $id) {
            Cache::forget("user_{$id}");
        }

        /** @var int $count */
        $count = User::onlyTrashed()->whereIn('id', $ids)->restore();

        return $count;
    }
}

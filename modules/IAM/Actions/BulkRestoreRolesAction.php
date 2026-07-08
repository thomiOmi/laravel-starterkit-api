<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\Role;

final readonly class BulkRestoreRolesAction
{
    /**
     * @param  array<int, string|int>  $ids
     */
    public function handle(array $ids): int
    {
        foreach ($ids as $id) {
            Cache::forget("role_{$id}");
        }

        /** @var int $count */
        $count = Role::onlyTrashed()->whereIn('id', $ids)->restore();

        return $count;
    }
}

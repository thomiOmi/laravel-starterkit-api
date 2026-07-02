<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\Role;

final readonly class BulkDeleteRolesAction
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
        $count = Role::whereIn('id', $ids)
            ->where('name', '!=', Role::SUPER_ADMIN)
            ->delete();

        return $count;
    }
}

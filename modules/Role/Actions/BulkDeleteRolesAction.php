<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Modules\Role\Models\Role;

final readonly class BulkDeleteRolesAction
{
    /**
     * @param  array<int, string|int>  $ids
     */
    public function handle(array $ids): int
    {
        /** @var int $count */
        $count = Role::whereIn('id', $ids)->delete();

        return $count;
    }
}

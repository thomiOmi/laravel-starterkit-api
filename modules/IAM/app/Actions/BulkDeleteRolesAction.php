<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Enums\RoleEnum;
use Modules\IAM\Models\Role;

final readonly class BulkDeleteRolesAction
{
    /**
     * @param  array<int, string|int>  $ids
     */
    public function handle(array $ids): int
    {
        /** @var int $count */
        $count = Role::whereIn('id', $ids)
            ->where('name', '!=', RoleEnum::SuperAdmin->value)
            ->delete();

        return $count;
    }
}

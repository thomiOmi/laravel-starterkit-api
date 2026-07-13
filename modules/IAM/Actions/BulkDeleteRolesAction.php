<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Cache;
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

        $cacheKeys = array_map(fn (string|int $id): string => "role_{$id}", $ids);
        Cache::deleteMultiple($cacheKeys);

        return $count;
    }
}

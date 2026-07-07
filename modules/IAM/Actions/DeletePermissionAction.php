<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\Permission;

final readonly class DeletePermissionAction
{
    #[\NoDiscard]
    public function handle(string $id): bool
    {
        $permission = Permission::query()->find($id);

        if (! $permission) {
            return false;
        }

        Cache::forget("permission_{$permission->id}");

        return $permission->delete() ?? false;
    }
}

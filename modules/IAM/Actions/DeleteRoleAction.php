<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\Role;

final readonly class DeleteRoleAction
{
    public function handle(string $id): bool
    {
        $role = Role::query()->find($id);

        if (! $role) {
            return false;
        }

        if ($role->name === Role::SUPER_ADMIN) {
            return false;
        }

        Cache::forget("role_{$role->id}");

        return $role->delete() ?? false;
    }
}

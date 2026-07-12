<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\Role;

final readonly class DeleteRoleAction
{
    public function handle(string $id): bool
    {
        $role = Role::query()->findOrFail($id);

        if ($role->name === RoleEnum::SuperAdmin->value) {
            return false;
        }

        Cache::forget("role_{$role->id}");

        return $role->delete() ?? false;
    }
}

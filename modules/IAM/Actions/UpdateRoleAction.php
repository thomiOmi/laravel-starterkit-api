<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\Role;
use Modules\IAM\Payloads\V1\RolePayload;

final readonly class UpdateRoleAction
{
    public function handle(string $id, RolePayload $payload): ?Role
    {
        $role = Role::query()->find($id);

        if (! $role) {
            return null;
        }

        $role->update($payload->toArray());

        if ($payload->permissions !== []) {
            $role->syncPermissions($payload->permissions);
            $role->load('permissions:id,name');
        }

        Cache::forget("role_{$role->id}");

        return $role;
    }
}

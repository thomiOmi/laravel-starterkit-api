<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\Role\Models\Role;
use Modules\Role\Payloads\V1\RolePayload;

final readonly class UpdateRoleAction
{
    public function handle(Role $role, RolePayload $payload): Role
    {
        $role->update($payload->toArray());

        if ($payload->permissions !== []) {
            $role->syncPermissions($payload->permissions);
        }

        Cache::forget("role_{$role->id}");

        return $role;
    }
}

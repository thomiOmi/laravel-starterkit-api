<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\Role\Models\Permission;
use Modules\Role\Payloads\V1\PermissionPayload;

final readonly class UpdatePermissionAction
{
    public function handle(Permission $permission, PermissionPayload $payload): Permission
    {
        $permission->update($payload->toArray());

        Cache::forget("permission_{$permission->id}");

        return $permission;
    }
}

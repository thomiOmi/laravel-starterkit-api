<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\Permission;
use Modules\IAM\Payloads\V1\PermissionPayload;

final readonly class UpdatePermissionAction
{
    public function handle(string $id, PermissionPayload $payload): ?Permission
    {
        $permission = Permission::query()->find($id);

        if (! $permission) {
            return null;
        }

        $permission->update($payload->toArray());

        Cache::forget("permission_{$permission->id}");

        return $permission;
    }
}

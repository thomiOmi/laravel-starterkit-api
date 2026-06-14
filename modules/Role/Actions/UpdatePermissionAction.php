<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Modules\Role\Models\Permission;
use Modules\Role\Payloads\V1\PermissionPayload;

final readonly class UpdatePermissionAction
{
    public function handle(Permission $permission, PermissionPayload $payload): Permission
    {
        $permission->update($payload->toArray());

        return $permission;
    }
}

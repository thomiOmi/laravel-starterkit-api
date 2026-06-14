<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Modules\Role\Models\Permission;
use Modules\Role\Payloads\V1\PermissionPayload;

final readonly class StorePermissionAction
{
    public function handle(PermissionPayload $payload): Permission
    {
        /** @var Permission $permission */
        $permission = Permission::create($payload->toArray());

        return $permission;
    }
}

<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\Permission;
use Modules\IAM\Payloads\V1\PermissionPayload;

final readonly class CreatePermissionAction
{
    #[\NoDiscard]
    public function handle(PermissionPayload $payload): Permission
    {
        /** @var Permission $permission */
        $permission = Permission::create($payload->toArray());

        return $permission;
    }
}

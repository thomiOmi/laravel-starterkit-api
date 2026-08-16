<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\Permission;
use Modules\IAM\Payloads\V1\PermissionPayload;

final readonly class UpdatePermissionAction
{
    public function handle(Permission $permission, PermissionPayload $payload): Permission
    {
        $permission->update($payload->toArray());

        return $permission;
    }
}

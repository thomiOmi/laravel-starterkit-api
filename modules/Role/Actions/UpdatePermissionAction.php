<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\Role\Models\Permission;
use Modules\Role\Payloads\V1\PermissionPayload;
use Modules\Role\Repositories\PermissionRepository;

final readonly class UpdatePermissionAction
{
    public function __construct(
        private PermissionRepository $repository
    ) {}

    public function handle(string|Permission $permission, PermissionPayload $payload): ?Permission
    {
        if (is_string($permission)) {
            $permission = $this->repository->findById($permission);
        }

        if (! $permission) {
            return null;
        }

        $permission->update($payload->toArray());

        Cache::forget("permission_{$permission->id}");

        return $permission;
    }
}

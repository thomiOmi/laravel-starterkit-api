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

    public function handle(string $id, PermissionPayload $payload): ?Permission
    {
        $permission = $this->repository->findById($id);

        if (! $permission) {
            return null;
        }

        $permission->update($payload->toArray());

        Cache::forget("permission_{$permission->id}");

        return $permission;
    }
}

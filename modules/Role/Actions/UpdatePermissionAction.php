<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\Role\Models\Permission;
use Modules\Role\Payloads\V1\PermissionPayload;
use Modules\Role\Repositories\PermissionRepository;

final readonly class UpdatePermissionAction
{
    public function __construct(
        private DatabaseManager $database,
        private PermissionRepository $repository
    ) {}

    public function handle(Permission $permission, PermissionPayload $payload): Permission
    {
        return $this->database->transaction(function () use ($permission, $payload): Permission {
            return $this->repository->update($permission, $payload->toArray());
        });
    }
}

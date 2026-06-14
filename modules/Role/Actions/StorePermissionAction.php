<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\Role\Models\Permission;
use Modules\Role\Payloads\V1\PermissionPayload;
use Modules\Role\Repositories\PermissionRepository;

final readonly class StorePermissionAction
{
    public function __construct(
        private DatabaseManager $database,
        private PermissionRepository $repository
    ) {}

    public function handle(PermissionPayload $payload): Permission
    {
        return $this->database->transaction(function () use ($payload): Permission {
            return $this->repository->create($payload->toArray());
        });
    }
}

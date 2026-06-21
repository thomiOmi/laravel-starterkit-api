<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\Role\Models\Role;
use Modules\Role\Payloads\V1\RolePayload;
use Modules\Role\Repositories\RoleRepository;

final readonly class UpdateRoleAction
{
    public function __construct(
        private RoleRepository $repository
    ) {}

    public function handle(string $id, RolePayload $payload): ?Role
    {
        $role = $this->repository->findById($id);

        if (! $role) {
            return null;
        }

        $role->update($payload->toArray());

        if ($payload->permissions !== []) {
            $role->syncPermissions($payload->permissions);
        }

        Cache::forget("role_{$role->id}");

        return $role;
    }
}

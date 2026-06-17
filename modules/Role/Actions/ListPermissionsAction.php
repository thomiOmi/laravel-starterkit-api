<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\Role\Filters\PermissionFilter;
use Modules\Role\Models\Permission;
use Modules\Role\Repositories\PermissionRepository;

final readonly class ListPermissionsAction
{
    public function __construct(
        private PermissionRepository $repository
    ) {}

    /** @return Paginator<int, Permission> */
    public function handle(PermissionFilter $filter, int $pageSize = 20, ?int $page = null): Paginator
    {
        return $this->repository->paginate($filter, $pageSize, $page);
    }
}

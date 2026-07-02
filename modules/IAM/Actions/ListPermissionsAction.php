<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Filters\PermissionFilter;
use Modules\IAM\Models\Permission;

final readonly class ListPermissionsAction
{
    /** @return Paginator<int, Permission> */
    public function handle(PermissionFilter $filter, int $pageSize = 20, ?int $page = null): Paginator
    {
        return $filter->apply(Permission::query())->paginate($pageSize, ['*'], 'page', $page);
    }
}

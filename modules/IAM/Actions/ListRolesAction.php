<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Filters\RoleFilter;
use Modules\IAM\Models\Role;

final readonly class ListRolesAction
{
    /** @return Paginator<int, Role> */
    public function handle(RoleFilter $filter, int $pageSize = 10, ?int $page = null): Paginator
    {
        return $filter->apply(Role::query())->paginate($pageSize, ['*'], 'page', $page);
    }
}

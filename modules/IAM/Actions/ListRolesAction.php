<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Filters\RoleFilter;
use Modules\IAM\Models\Role;

final readonly class ListRolesAction
{
    /**
     * Handle the action to list roles with filtering and pagination.
     *
     * @param  RoleFilter  $filter  The filter to apply to the role query.
     * @param  int  $pageSize  The number of roles per page.
     * @param  int|null  $page  The current page number.
     * @return Paginator<int, Role> A paginator instance containing the roles.
     */
    public function handle(RoleFilter $filter, int $pageSize = 10, ?int $page = null): Paginator
    {
        $builder = $filter->apply(
            Role::with(['permissions:id,name'])
        );

        return $builder->paginate($pageSize, $builder->getQuery()->columns ?? ['*'], 'page', $page);
    }
}

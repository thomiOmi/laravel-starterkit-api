<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Filters\RoleFilter;
use Modules\IAM\Models\Role;

final readonly class ListRolesAction
{
    /**
     * Handle the action to list roles with optimized query and eager loading.
     *
     * @param  RoleFilter  $filter  The filter to apply to the query.
     * @param  int  $pageSize  The number of items per page.
     * @param  int|null  $page  The page number to retrieve.
     * @return Paginator<int, Role>
     */
    public function handle(RoleFilter $filter, int $pageSize = 10, ?int $page = null): Paginator
    {
        $builder = $filter->apply(
            Role::query()
                ->with(['permissions:id,name'])
                ->select([
                    'id',
                    'name',
                    'description',
                    'created_at',
                    'updated_at',
                ])
        );

        /** @var array<int, string> $columns */
        $columns = $builder->getQuery()->columns ?? ['*'];

        return $builder->paginate(
            $pageSize,
            $columns,
            'page',
            $page
        );
    }
}

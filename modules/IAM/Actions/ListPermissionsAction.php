<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Filters\PermissionFilter;
use Modules\IAM\Models\Permission;

final readonly class ListPermissionsAction
{
    /**
     * Handle the action to list permissions with optimized query.
     *
     * @param  PermissionFilter  $filter  The filter to apply to the query.
     * @param  int  $pageSize  The number of items per page.
     * @param  int|null  $page  The page number to retrieve.
     * @return Paginator<int, Permission>
     */
    public function handle(PermissionFilter $filter, int $pageSize = 20, ?int $page = null): Paginator
    {
        $builder = $filter->apply(
            Permission::query()
                ->select([
                    'id',
                    'name',
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

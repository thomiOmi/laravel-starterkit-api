<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Models\Permission;

final readonly class ListPermissionsAction
{
    /**
     * Handle the action to list permissions with optimized query.
     *
     * @param  int  $pageSize  The number of items per page.
     * @param  int|null  $page  The page number to retrieve.
     * @return Paginator<int, Permission>
     */
    public function handle(int $pageSize = 20, ?int $page = null): Paginator
    {
        return Permission::query()
            ->filter(request())
            ->paginate($pageSize, ['*'], 'page', $page);
    }
}

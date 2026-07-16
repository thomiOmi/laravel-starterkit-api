<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Models\Role;

final readonly class ListRolesAction
{
    /**
     * Handle the action to list roles with optimized query and eager loading.
     *
     * @param  int  $pageSize  The number of items per page.
     * @param  int|null  $page  The page number to retrieve.
     * @return Paginator<int, Role>
     */
    public function handle(int $pageSize = 10, ?int $page = null): Paginator
    {
        return Role::query()
            ->with(['permissions:id,name'])
            ->filter(request())
            ->paginate($pageSize, ['*'], 'page', $page);
    }
}

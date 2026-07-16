<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Models\User;

final readonly class ListUsersAction
{
    /**
     * Handle the action to list users with optimized query and eager loading.
     *
     * @param  int  $pageSize  The number of items per page.
     * @param  int|null  $page  The page number to retrieve.
     * @return Paginator<int, User>
     */
    public function handle(int $pageSize = 10, ?int $page = null): Paginator
    {
        return User::query()
            ->with(['roles:id,name,guard_name', 'roles.permissions:id,name', 'permissions:id,name'])
            ->filter(request())
            ->paginate($pageSize, ['*'], 'page', $page);
    }
}

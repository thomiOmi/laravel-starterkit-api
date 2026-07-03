<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Filters\UserFilter;
use Modules\IAM\Models\User;

final readonly class ListUsersAction
{
    /**
     * Handle the action to list users with filtering and pagination.
     *
     * @param  UserFilter  $filter  The filter to apply to the user query.
     * @param  int  $pageSize  The number of users per page.
     * @param  int|null  $page  The current page number.
     * @return Paginator<int, User> A paginator instance containing the users.
     */
    public function handle(UserFilter $filter, int $pageSize = 10, ?int $page = null): Paginator
    {
        $builder = $filter->apply(
            User::with(['roles.permissions:id,name', 'permissions:id,name'])
        );

        return $builder->paginate($pageSize, $builder->getQuery()->columns ?? ['*'], 'page', $page);
    }
}

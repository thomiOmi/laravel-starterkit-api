<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Filters\UserFilter;
use Modules\IAM\Models\User;

final readonly class ListUsersAction
{
    /**
     * Handle the action to list users with optimized query and eager loading.
     *
     * @param  UserFilter  $filter  The filter to apply to the query.
     * @param  int  $pageSize  The number of items per page.
     * @param  int|null  $page  The page number to retrieve.
     * @return Paginator<int, User>
     */
    public function handle(UserFilter $filter, int $pageSize = 10, ?int $page = null): Paginator
    {
        $builder = $filter->apply(
            User::query()
                ->with(['roles:id,name', 'roles.permissions:id,name', 'permissions:id,name'])
                ->select([
                    'id',
                    'name',
                    'email',
                    'avatar',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                    'deleted_at',
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

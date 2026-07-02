<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Filters\UserFilter;
use Modules\IAM\Models\User;

final readonly class ListUsersAction
{
    /** @return Paginator<int, User> */
    public function handle(UserFilter $filter, int $pageSize = 10, ?int $page = null): Paginator
    {
        return $filter->apply(User::query())->paginate($pageSize, ['*'], 'page', $page);
    }
}

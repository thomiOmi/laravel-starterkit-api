<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\User\Filters\UserFilter;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

/**
 * Action for listing paginated users with filters.
 */
final readonly class ListUsersAction
{
    /**
     * Create a new ListUsersAction instance.
     */
    public function __construct(
        private UserRepository $repository
    ) {}

    /**
     * Execute the list users action.
     *
     * @param  UserFilter  $filter  The user filter instance.
     * @param  int  $perPage  Number of items per page.
     * @return Paginator<int, User> The paginated users.
     */
    public function handle(UserFilter $filter, int $perPage = 10): Paginator
    {
        return $this->repository->paginate($filter, $perPage);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\Role\Filters\RoleFilter;
use Modules\Role\Models\Role;
use Modules\Role\Repositories\RoleRepository;

/**
 * Action for listing paginated roles with filters.
 */
final readonly class ListRolesAction
{
    /**
     * Create a new ListRolesAction instance.
     */
    public function __construct(
        private RoleRepository $repository
    ) {}

    /**
     * Execute the list roles action.
     *
     * @param  RoleFilter  $filter  The role filter instance.
     * @param  int  $pageSize  Number of items per page.
     * @param  int|null  $page  The page number.
     * @return Paginator<int, Role> The paginated roles.
     */
    public function handle(RoleFilter $filter, int $pageSize = 10, ?int $page = null): Paginator
    {
        return $this->repository->paginate($filter, $pageSize, $page);
    }
}

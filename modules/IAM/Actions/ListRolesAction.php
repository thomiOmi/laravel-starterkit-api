<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Support\Filters\BaseFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\IAM\Filters\RoleFilter;
use Modules\IAM\Models\Role;

final readonly class ListRolesAction
{
    /**
     * Handle the action to list roles with filtering, sorting, and sparse fields.
     *
     * @param  BaseFilter<Role>|null  $filter
     * @return LengthAwarePaginator<int, Role>
     */
    public function handle(
        ?BaseFilter $filter = null,
        int $perPage = 10,
        int $page = 1,
    ): LengthAwarePaginator {
        $filter = $filter ?? new RoleFilter(request());
        $query = Role::query()->with(['permissions:id,name']);
        $filter($query);

        return $query->paginate(
            perPage: $perPage,
            page: $page,
        );
    }
}

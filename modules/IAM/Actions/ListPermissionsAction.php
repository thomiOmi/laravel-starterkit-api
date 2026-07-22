<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Http\Filters\BaseFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\IAM\Filters\PermissionFilter;
use Modules\IAM\Models\Permission;

final readonly class ListPermissionsAction
{
    /**
     * Handle the action to list permissions with filtering, sorting, and sparse fields.
     *
     * @param  BaseFilter<Permission>|null  $filter
     * @return LengthAwarePaginator<int, Permission>
     */
    public function handle(
        ?BaseFilter $filter = null,
        int $perPage = 10,
        int $page = 1,
    ): LengthAwarePaginator {
        $filter = $filter ?? new PermissionFilter(request());
        $query = Permission::query();
        $filter($query);

        return $query->paginate(
            perPage: $perPage,
            page: $page,
        );
    }
}

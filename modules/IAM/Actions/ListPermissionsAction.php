<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\IAM\Filters\PermissionFilter;
use Modules\IAM\Models\Permission;

final readonly class ListPermissionsAction
{
    /**
     * Handle the action to list permissions with filtering, sorting, and sparse fields.
     *
     * @return LengthAwarePaginator<int, Permission>
     */
    public function handle(): LengthAwarePaginator
    {
        return Permission::query()
            ->tap(new PermissionFilter(request()))
            ->paginate(
                perPage: max(1, min((int) request()->integer('page.size', 15), 100)),
                page: max(1, (int) request()->integer('page.number', 1)),
            );
    }
}

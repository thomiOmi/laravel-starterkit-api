<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\IAM\Filters\RoleFilter;
use Modules\IAM\Models\Role;

final readonly class ListRolesAction
{
    /**
     * Handle the action to list roles with filtering, sorting, and sparse fields.
     *
     * @return LengthAwarePaginator<int, Role>
     */
    public function handle(): LengthAwarePaginator
    {
        return Role::query()
            ->with(['permissions:id,name'])
            ->tap(new RoleFilter(request()))
            ->paginate(
                perPage: max(1, min((int) request()->integer('page.size', 15), 100)),
                page: max(1, (int) request()->integer('page.number', 1)),
            );
    }
}

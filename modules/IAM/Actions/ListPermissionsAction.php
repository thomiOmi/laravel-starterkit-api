<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Http\Filters\BasePaginate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\IAM\Filters\PermissionFilter;
use Modules\IAM\Models\Permission;

final readonly class ListPermissionsAction
{
    /**
     * Handle the action to list permissions with filtering, sorting, and sparse fields.
     * Conditionally applies default sparse field selection (only when specific fields
     * are not requested by the user) to reduce database payload and optimize performance.
     *
     * @return LengthAwarePaginator<int, Permission> The paginated permissions list.
     */
    public function handle(): LengthAwarePaginator
    {
        $query = Permission::query();

        if (! request()->has('fields.permissions')) {
            $query->select([
                'id',
                'name',
                'guard_name',
                'created_at',
                'updated_at',
            ]);
        }

        return $query
            ->tap(new PermissionFilter(request()))
            ->pipe(new BasePaginate(request()));
    }
}

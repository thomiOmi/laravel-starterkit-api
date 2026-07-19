<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Http\Filters\BasePaginate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\IAM\Filters\RoleFilter;
use Modules\IAM\Models\Role;

final readonly class ListRolesAction
{
    /**
     * Handle the action to list roles with filtering, sorting, and sparse fields.
     *
     * Default-select only specific columns if user-requested sparse fields
     * are not present, to prevent overfetching.
     *
     * @return LengthAwarePaginator<int, Role> The paginated role list.
     */
    public function handle(): LengthAwarePaginator
    {
        $query = Role::query();

        if (! request()->has('fields.roles')) {
            $query->select([
                'id',
                'name',
                'description',
                'guard_name',
                'created_at',
                'updated_at',
            ]);
        }

        return $query
            ->with(['permissions:id,name'])
            ->tap(new RoleFilter(request()))
            ->pipe(new BasePaginate(request()));
    }
}

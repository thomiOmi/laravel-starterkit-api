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
     * Conditionally applies default sparse field selection (only when specific fields
     * are not requested by the user) to reduce database payload and optimize performance.
     *
     * @return LengthAwarePaginator<int, Role> The paginated roles list.
     */
    public function handle(): LengthAwarePaginator
    {
        $query = Role::query()
            ->with(['permissions:id,name']);

        if (! request()->has('fields.roles')) {
            $query->select([
                'id',
                'name',
                'guard_name',
                'description',
                'created_at',
                'updated_at',
            ]);
        }

        return $query
            ->tap(new RoleFilter(request()))
            ->pipe(new BasePaginate(request()));
    }
}

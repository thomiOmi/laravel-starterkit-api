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
     * @return LengthAwarePaginator<int, Role>
     */
    public function handle(): LengthAwarePaginator
    {
        return Role::query()
            ->with(['permissions:id,name'])
            ->tap(new RoleFilter(request()))
            ->pipe(new BasePaginate(request()));
    }
}

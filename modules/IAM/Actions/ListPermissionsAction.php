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
     *
     * @return LengthAwarePaginator<int, Permission>
     */
    public function handle(): LengthAwarePaginator
    {
        return Permission::query()
            ->tap(new PermissionFilter(request()))
            ->pipe(new BasePaginate(request()));
    }
}

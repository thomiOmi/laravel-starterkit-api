<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\IAM\Filters\UserFilter;
use Modules\IAM\Models\User;

final readonly class ListUsersAction
{
    /**
     * Handle the action to list users with filtering, sorting, and sparse fields.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles:id,name,guard_name', 'roles.permissions:id,name', 'permissions:id,name'])
            ->tap(new UserFilter(request()))
            ->paginate(
                perPage: max(1, min((int) request()->integer('page.size', 15), 100)),
                page: max(1, (int) request()->integer('page.number', 1)),
            );
    }
}

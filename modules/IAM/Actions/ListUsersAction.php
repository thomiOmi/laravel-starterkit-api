<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Http\Filters\BaseFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\IAM\Filters\UserFilter;
use Modules\IAM\Models\User;

final readonly class ListUsersAction
{
    /**
     * Handle the action to list users with filtering, sorting, and sparse fields.
     *
     * @param  BaseFilter<User>|null  $filter
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(
        ?BaseFilter $filter = null,
        int $perPage = 15,
        int $page = 1,
    ): LengthAwarePaginator {
        $filter = $filter ?? new UserFilter(request());
        $query = User::query()
            ->with(['roles:id,name,guard_name', 'roles.permissions:id,name', 'permissions:id,name']);
        $filter($query);

        return $query->paginate(
            perPage: max(1, min($perPage, 100)),
            page: max(1, $page),
        );
    }
}

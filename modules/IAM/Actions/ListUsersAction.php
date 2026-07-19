<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Http\Filters\BasePaginate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\IAM\Filters\UserFilter;
use Modules\IAM\Models\User;

final readonly class ListUsersAction
{
    /**
     * Handle the action to list users with filtering, sorting, and sparse fields.
     *
     * Default-select only specific, non-sensitive columns if user-requested
     * sparse fields are not present, to prevent overfetching.
     *
     * @return LengthAwarePaginator<int, User> The paginated user list.
     */
    public function handle(): LengthAwarePaginator
    {
        $query = User::query();

        if (! request()->has('fields.users')) {
            $query->select([
                'id',
                'name',
                'email',
                'avatar',
                'email_verified_at',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);
        }

        return $query
            ->with(['roles:id,name,guard_name', 'roles.permissions:id,name', 'permissions:id,name'])
            ->tap(new UserFilter(request()))
            ->pipe(new BasePaginate(request()));
    }
}

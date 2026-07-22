<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use App\Support\Filters\BaseFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\IAM\Filters\DeviceFilter;
use Modules\IAM\Models\User;

final readonly class ListDevicesAction
{
    /**
     * Handle the action to list devices for a user with filtering, sorting, and pagination.
     *
     * @param  BaseFilter<PersonalAccessToken>|null  $filter
     * @return LengthAwarePaginator<int, PersonalAccessToken>
     */
    public function handle(
        User $user,
        ?BaseFilter $filter = null,
        int $perPage = 10,
        int $page = 1,
    ): LengthAwarePaginator {
        $filter = $filter ?? new DeviceFilter(request());
        $query = PersonalAccessToken::query()
            ->where('tokenable_id', $user->getKey())
            ->where('tokenable_type', $user->getMorphClass())
            ->select([
                'id',
                'name',
                'last_used_at',
                'created_at',
                'ip_address',
                'user_agent',
            ]);
        $filter($query);

        return $query->paginate(
            perPage: $perPage,
            page: $page,
        );
    }
}

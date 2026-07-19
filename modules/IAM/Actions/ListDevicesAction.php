<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Http\Filters\BasePaginate;
use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\IAM\Filters\DeviceFilter;
use Modules\IAM\Models\User;

final readonly class ListDevicesAction
{
    /**
     * Handle the action to list devices for a user with filtering, sorting, and pagination.
     *
     * Default-select only specific columns if user-requested sparse fields
     * are not present, to prevent overfetching.
     *
     * @param  User  $user  The user instance.
     * @return LengthAwarePaginator<int, PersonalAccessToken> The paginated device token list.
     */
    public function handle(User $user): LengthAwarePaginator
    {
        $query = PersonalAccessToken::query()
            ->where('tokenable_id', $user->getKey())
            ->where('tokenable_type', $user->getMorphClass());

        if (! request()->has('fields.personal_access_tokens')) {
            $query->select([
                'id',
                'name',
                'last_used_at',
                'created_at',
                'ip_address',
                'user_agent',
            ]);
        }

        return $query
            ->tap(new DeviceFilter(request()))
            ->pipe(new BasePaginate(request()));
    }
}

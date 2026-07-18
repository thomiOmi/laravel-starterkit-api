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
     * @return LengthAwarePaginator<int, PersonalAccessToken>
     */
    public function handle(User $user): LengthAwarePaginator
    {
        return PersonalAccessToken::query()
            ->where('tokenable_id', $user->getKey())
            ->where('tokenable_type', $user->getMorphClass())
            ->select([
                'id',
                'name',
                'last_used_at',
                'created_at',
                'ip_address',
                'user_agent',
            ])
            ->tap(new DeviceFilter(request()))
            ->pipe(new BasePaginate(request()));
    }
}

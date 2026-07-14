<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Models\User;

final readonly class ListDevicesAction
{
    /**
     * Handle the action to list devices for a user with pagination.
     *
     * @param  User  $user  The user whose devices to list.
     * @param  int  $pageSize  The number of items per page.
     * @param  int|null  $page  The page number to retrieve.
     * @return Paginator<int, PersonalAccessToken>
     */
    public function handle(User $user, int $pageSize = 20, ?int $page = null): Paginator
    {
        $builder = $user->tokens()
            ->select([
                'id',
                'name',
                'last_used_at',
                'created_at',
                'ip_address',
                'user_agent',
            ])
            ->orderBy('last_used_at', 'desc')
            ->orderBy('created_at', 'desc');

        /** @var array<int, string> $columns */
        $columns = $builder->getBaseQuery()->columns ?? ['*'];

        /** @var Paginator<int, PersonalAccessToken> $paginator */
        $paginator = $builder->paginate(
            $pageSize,
            $columns,
            'page',
            $page
        );

        return $paginator;
    }
}

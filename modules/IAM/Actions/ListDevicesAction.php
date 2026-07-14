<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Models\User;

final readonly class ListDevicesAction
{
    /**
     * @return Paginator<int, PersonalAccessToken>
     */
    public function handle(User $user, int $pageSize = 10, ?int $page = null): Paginator
    {
        $columns = [
            'id',
            'name',
            'last_used_at',
            'created_at',
            'ip_address',
            'user_agent',
        ];

        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, PersonalAccessToken> $paginator */
        $paginator = $user->tokens()
            ->select($columns)
            ->orderBy('last_used_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(
                $pageSize,
                $columns,
                'page',
                $page
            );

        return $paginator;
    }
}

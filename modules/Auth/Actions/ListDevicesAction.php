<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Database\Eloquent\Collection;
use Modules\User\Models\User;

final readonly class ListDevicesAction
{
    /**
     * @return Collection<int, PersonalAccessToken>
     */
    public function handle(User $user): Collection
    {
        /** @var Collection<int, PersonalAccessToken> */
        return $user->tokens()
            ->select(['id', 'name', 'ip_address', 'user_agent', 'abilities', 'last_used_at', 'expires_at', 'created_at'])
            ->orderBy('last_used_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Database\Eloquent\Collection;
use Modules\IAM\Models\User;

final readonly class ListDevicesAction
{
    /**
     * @return Collection<int, PersonalAccessToken>
     */
    #[\NoDiscard]
    public function handle(User $user): Collection
    {
        /** @var Collection<int, PersonalAccessToken> */
        return $user->tokens()
            ->select([
                'id',
                'name',
                'last_used_at',
                'created_at',
                'ip_address',
                'user_agent',
            ])
            ->orderBy('last_used_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

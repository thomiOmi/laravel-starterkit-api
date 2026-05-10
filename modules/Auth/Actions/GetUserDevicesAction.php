<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\User\Models\User;

/**
 * Action for retrieving authenticated user's active devices.
 */
class GetUserDevicesAction
{
    /**
     * Get active devices for the user.
     *
     * @param  Request  $request  The current request.
     * @return Collection<int, PersonalAccessToken> Active tokens.
     */
    public function execute(Request $request): Collection
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Collection<int, PersonalAccessToken> $tokens */
        $tokens = $user->tokens()->orderBy('last_used_at', 'desc')->get();

        return $tokens;
    }
}

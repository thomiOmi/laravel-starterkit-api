<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\User\Models\User;

/**
 * Action for logging out all other devices except the current one.
 */
class LogoutOtherDevicesAction
{
    /**
     * Logout other devices except the current one.
     *
     * @param  Request  $request  The current request.
     */
    public function execute(Request $request): void
    {
        /** @var User $user */
        $user = $request->user();

        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        $user->tokens()
            ->where('id', '!=', $currentToken->id)
            ->delete();
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\User\Models\User;

/**
 * Action for logging out the current user session.
 */
class LogoutAction
{
    /**
     * Execute the logout action.
     *
     * @param  Request  $request  The current request.
     */
    public function execute(Request $request): void
    {
        /** @var User $user */
        $user = $request->user();

        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        $currentToken->delete();
    }
}

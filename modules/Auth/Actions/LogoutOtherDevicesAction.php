<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\Request;

class LogoutOtherDevicesAction
{
    /**
     * Logout other devices except the current one.
     */
    public function execute(Request $request): void
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        $user->tokens()
            ->where('id', '!=', $currentToken->id)
            ->delete();
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\Request;
use Modules\User\Models\User;

/**
 * Action for logging out a specific device.
 */
class LogoutDeviceAction
{
    /**
     * Logout a specific device.
     *
     * @param  Request  $request  The current request.
     * @param  string  $tokenId  The token ID to delete.
     */
    public function execute(Request $request, string $tokenId): void
    {
        /** @var User $user */
        $user = $request->user();

        $user->tokens()->where('id', $tokenId)->delete();
    }
}

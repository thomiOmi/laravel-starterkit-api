<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\Request;

class LogoutDeviceAction
{
    /**
     * Logout a specific device.
     */
    public function execute(Request $request, string $tokenId): void
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();
    }
}

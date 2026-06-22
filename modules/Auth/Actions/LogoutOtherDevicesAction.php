<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Modules\User\Models\User;

final readonly class LogoutOtherDevicesAction
{
    public function handle(User $user, string $currentPassword): void
    {
        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        $user->tokens()
            ->where('id', '!=', $currentToken->getKey())
            ->delete();
    }
}

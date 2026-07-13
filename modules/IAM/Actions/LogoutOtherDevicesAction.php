<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Modules\IAM\Models\User;

final readonly class LogoutOtherDevicesAction
{
    public function handle(User $user): void
    {
        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        $user->tokens()
            ->where('id', '!=', $currentToken->getKey())
            ->delete();
    }
}

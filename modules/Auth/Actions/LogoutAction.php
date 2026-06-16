<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Modules\User\Models\User;

/**
 * Action for logging out the current user session.
 */
final readonly class LogoutAction
{
    /**
     * Execute the logout action.
     */
    public function handle(User $user): void
    {
        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        $currentToken->delete();
    }
}

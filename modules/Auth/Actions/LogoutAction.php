<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Modules\User\Models\User;

/**
 * Action for logging out the current user session.
 */
final readonly class LogoutAction
{
    public function __construct(
        private AuthFactory $auth,
    ) {}

    /**
     * Execute the logout action.
     */
    public function handle(User $user, bool $stateful = false): void
    {
        event(new Logout('web', $user));

        if ($stateful) {
            $this->auth->guard('web')->logout();

            return;
        }

        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        $currentToken->delete();
    }
}

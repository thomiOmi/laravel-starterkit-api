<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Contracts\Auth\Guard;
use Modules\User\Models\User;

final readonly class DeleteUserAction
{
    public function __construct(
        private Guard $auth
    ) {}

    public function handle(User $user): bool
    {
        if ($user->id === $this->auth->id()) {
            return false;
        }

        return (bool) $user->delete();
    }
}

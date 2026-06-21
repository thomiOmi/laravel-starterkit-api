<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Cache;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

final readonly class DeleteUserAction
{
    public function __construct(
        private Guard $auth,
        private UserRepository $repository
    ) {}

    public function handle(string|User $user): bool
    {
        if (is_string($user)) {
            $user = $this->repository->findById($user);
        }

        if (! $user) {
            return false;
        }

        if ($user->id === $this->auth->id()) {
            return false;
        }

        Cache::forget("user_{$user->id}");

        return (bool) $user->delete();
    }
}

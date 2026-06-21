<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Cache;
use Modules\User\Repositories\UserRepository;

final readonly class DeleteUserAction
{
    public function __construct(
        private Guard $auth,
        private UserRepository $repository
    ) {}

    public function handle(string $id): bool
    {
        if ($id === $this->auth->id()) {
            return false;
        }

        $user = $this->repository->findById($id);

        if (! $user) {
            return false;
        }

        Cache::forget("user_{$user->id}");

        return (bool) $user->delete();
    }
}

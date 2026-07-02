<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\User;

final readonly class DeleteUserAction
{
    public function __construct(
        private Guard $auth,
    ) {}

    public function handle(string $id): bool
    {
        if ($id === $this->auth->id()) {
            return false;
        }

        $user = User::query()->find($id);

        if (! $user) {
            return false;
        }

        Cache::forget("user_{$user->id}");

        return (bool) $user->delete();
    }
}

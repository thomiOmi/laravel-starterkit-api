<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Enums\RoleEnum;
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

        $user = User::query()->with('roles')->findOrFail($id);

        /** @var User|null $currentUser */
        $currentUser = $this->auth->user();

        if ($user->hasRole(RoleEnum::SuperAdmin->value) && ($currentUser === null || ! $currentUser->hasRole(RoleEnum::SuperAdmin->value))) {
            return false;
        }

        Cache::forget("user_{$user->id}");

        return $user->delete() ?? false;
    }
}

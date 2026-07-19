<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Enums\RoleEnum;
use Illuminate\Contracts\Auth\Guard;
use Modules\IAM\Models\User;

final readonly class DeleteUserAction
{
    public function __construct(
        private Guard $auth,
    ) {}

    /**
     * Handle the action to delete a user.
     *
     * @param  string  $id  The ID of the user to delete.
     * @return bool True if deleted successfully, false otherwise.
     */
    public function handle(string $id): bool
    {
        if ($id === $this->auth->id()) {
            return false;
        }

        $user = User::query()->with(['roles:id,name,guard_name'])->findOrFail($id);

        /** @var User|null $currentUser */
        $currentUser = $this->auth->user();

        if ($user->hasRole(RoleEnum::SuperAdmin->value) && ($currentUser === null || ! $currentUser->hasRole(RoleEnum::SuperAdmin->value))) {
            return false;
        }

        return $user->delete() ?? false;
    }
}

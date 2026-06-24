<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

final readonly class AssignRolesToUserAction
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    /**
     * Assign roles to a user.
     *
     * @param  string  $userId  The user ID.
     * @param  array<int, string>  $roles  The list of roles to assign.
     */
    public function handle(string $userId, array $roles): ?User
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            return null;
        }

        $user->syncRoles($roles);

        return $user;
    }
}

<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\User\Models\User;
use Modules\User\Payloads\V1\UserPayload;
use Modules\User\Repositories\UserRepository;

/**
 * Action for updating an existing user.
 */
final readonly class UpdateUserAction
{
    /**
     * Create a new UpdateUserAction instance.
     */
    public function __construct(
        private DatabaseManager $database,
        private UserRepository $repository
    ) {}

    /**
     * Execute the update user action.
     *
     * @param  User  $user  The user model instance.
     * @param  UserPayload  $payload  The user payload.
     * @return User The updated user instance.
     */
    public function handle(User $user, UserPayload $payload): User
    {
        return $this->database->transaction(function () use ($user, $payload) {
            return $this->repository->update($user, $payload->toArray());
        });
    }
}

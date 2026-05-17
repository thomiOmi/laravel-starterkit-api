<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\User\Models\User;

/**
 * Action for deleting a user.
 */
final readonly class DeleteUserAction
{
    /**
     * Create a new DeleteUserAction instance.
     */
    public function __construct(
        private DatabaseManager $database
    ) {}

    /**
     * Execute the delete user action.
     *
     * @param  User  $user  The user model instance.
     * @return bool True if the user was deleted successfully, false otherwise.
     */
    public function handle(User $user): bool
    {
        return $this->database->transaction(fn () => (bool) $user->delete());
    }
}

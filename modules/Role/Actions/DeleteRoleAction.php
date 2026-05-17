<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\Role\Models\Role;

/**
 * Action for deleting a role.
 */
final readonly class DeleteRoleAction
{
    /**
     * Create a new DeleteRoleAction instance.
     */
    public function __construct(
        private DatabaseManager $database
    ) {}

    /**
     * Execute the delete role action.
     *
     * @param  Role  $role  The role model instance.
     * @return bool True if the role was deleted successfully, false otherwise.
     */
    public function handle(Role $role): bool
    {
        return $this->database->transaction(fn () => (bool) $role->delete());
    }
}

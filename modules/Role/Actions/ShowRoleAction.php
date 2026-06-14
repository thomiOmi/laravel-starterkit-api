<?php

declare(strict_types=1);

namespace Modules\Role\Actions;

use Modules\Role\Models\Role;
use Modules\Role\Repositories\RoleRepository;

/**
 * Action for retrieving a single role with cache optimization.
 */
final readonly class ShowRoleAction
{
    /**
     * Create a new ShowRoleAction instance.
     */
    public function __construct(
        private RoleRepository $repository
    ) {}

    /**
     * Execute the show role action.
     *
     * @param  string  $id  The role ID.
     * @return Role|null The role instance or null if not found.
     */
    public function handle(string $id): ?Role
    {
        return $this->repository->findById($id);
    }
}

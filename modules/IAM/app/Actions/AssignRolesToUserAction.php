<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\DB;
use Modules\IAM\Models\User;
use Throwable;

final readonly class AssignRolesToUserAction
{
    /**
     * @param  array<int, string>  $roles
     *
     * @throws Throwable When the role assignment transaction fails.
     */
    public function handle(User $user, array $roles): User
    {
        DB::transaction(fn () => $user->syncRoles($roles));

        return $user;
    }
}

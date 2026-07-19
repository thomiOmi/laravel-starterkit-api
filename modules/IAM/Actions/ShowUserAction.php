<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\User;

final readonly class ShowUserAction
{
    /**
     * Handle the action to retrieve a single user profile.
     *
     * Optimized with sparse field selection to retrieve only required columns.
     *
     * @param  string  $id  The ID of the user to retrieve.
     * @return User The user model instance.
     */
    public function handle(string $id): User
    {
        return User::select([
            'id',
            'name',
            'email',
            'avatar',
            'email_verified_at',
            'created_at',
            'updated_at',
            'deleted_at',
        ])->with(['roles:id,name,guard_name', 'roles.permissions:id,name', 'permissions:id,name'])->findOrFail($id);
    }
}

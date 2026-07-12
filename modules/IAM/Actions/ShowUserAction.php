<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\User;

final readonly class ShowUserAction
{
    public function handle(string $id): ?User
    {
        return User::select([
            'id',
            'name',
            'email',
            'avatar',
            'provider',
            'provider_id',
            'email_verified_at',
            'created_at',
            'updated_at',
            'deleted_at',
        ])->with(['roles:id,name,guard_name', 'roles.permissions:id,name', 'permissions:id,name'])->find($id);
    }
}

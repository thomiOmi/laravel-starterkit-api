<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\User;

final readonly class ShowUserAction
{
    public function handle(string $id): ?User
    {
        return User::query()
            ->with(['roles.permissions:id,name', 'permissions:id,name'])
            ->select([
                'id',
                'name',
                'email',
                'avatar',
                'email_verified_at',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->find($id);
    }
}

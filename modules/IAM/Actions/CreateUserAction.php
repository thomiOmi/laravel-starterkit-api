<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Enums\RoleEnum;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\UserPayload;

final readonly class CreateUserAction
{
    public function handle(UserPayload $payload): User
    {
        $user = User::create($payload->toArray());

        $user->assignRole(RoleEnum::User);

        return $user->loadMissing(['roles:id,name', 'roles.permissions:id,name', 'permissions:id,name']);
    }
}

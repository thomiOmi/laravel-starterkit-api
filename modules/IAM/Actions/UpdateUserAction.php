<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Contracts\Identity;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\UserPayload;

final readonly class UpdateUserAction
{
    /**
     * Handle the update of an existing user's details.
     *
     * Supports both a pre-loaded Identity/User model or a string ID for flexibility and performance.
     *
     * @param  Identity|string  $user  The User model instance or the string ID of the user.
     * @param  UserPayload  $payload  The data payload containing update information.
     * @return User The updated User model instance.
     */
    public function handle(Identity|string $user, UserPayload $payload): User
    {
        if (is_string($user)) {
            $user = User::query()->findOrFail($user);
        }

        /** @var User $user */
        $user->fill($payload->toArray());
        $user->save();

        return $user->loadMissing(['roles:id,name,guard_name', 'roles.permissions:id,name', 'permissions:id,name']);
    }
}

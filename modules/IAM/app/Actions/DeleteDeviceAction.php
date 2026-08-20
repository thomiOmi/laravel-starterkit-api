<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\IAM\Models\User;

final readonly class DeleteDeviceAction
{
    /**
     * Revoke a device token owned by the given user.
     *
     * @throws ModelNotFoundException When the device belongs to another user.
     */
    public function handle(User $user, PersonalAccessToken $device): void
    {
        throw_if($user->getKey() !== $device->tokenable_id, ModelNotFoundException::class);

        $device->delete();
    }
}

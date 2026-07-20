<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Modules\IAM\Models\User;

final readonly class DeleteDeviceAction
{
    public function handle(User $user, PersonalAccessToken $device): void
    {
        abort_if($user->getKey() != $device->tokenable_id, 404);

        $device->delete();
    }
}

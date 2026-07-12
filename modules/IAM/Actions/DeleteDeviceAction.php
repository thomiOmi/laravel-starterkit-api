<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Modules\IAM\Models\User;

final readonly class DeleteDeviceAction
{
    public function handle(User $user, string $deviceId): void
    {
        $user->tokens()->findOrFail($deviceId)->delete();
    }
}

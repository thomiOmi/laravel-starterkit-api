<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use App\Models\Sanctum\PersonalAccessToken;
use Modules\User\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DeleteDeviceAction
{
    public function handle(User $user, string $deviceId): void
    {
        /** @var PersonalAccessToken|null $token */
        $token = $user->tokens()->find($deviceId);

        if ($token === null) {
            throw new NotFoundHttpException;
        }

        $token->delete();
    }
}

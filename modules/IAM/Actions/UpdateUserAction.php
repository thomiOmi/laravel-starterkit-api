<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\UserPayload;

final readonly class UpdateUserAction
{
    #[\NoDiscard]
    public function handle(string $id, UserPayload $payload): ?User
    {
        $user = User::query()->find($id);

        if (! $user) {
            return null;
        }

        $user->update($payload->toArray());

        Cache::forget("user_{$user->id}");

        return $user;
    }
}

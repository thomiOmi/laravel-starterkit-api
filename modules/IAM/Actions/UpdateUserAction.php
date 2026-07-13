<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\UserPayload;

final readonly class UpdateUserAction
{
    public function handle(string $id, UserPayload $payload): User
    {
        $user = User::query()->findOrFail($id);

        $user->fill($payload->toArray());
        $user->save();

        Cache::forget("user_{$user->id}");

        return $user->loadMissing(['roles:id,name', 'roles.permissions:id,name', 'permissions:id,name']);
    }
}

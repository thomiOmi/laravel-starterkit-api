<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Modules\User\Events\UserCreated;
use Modules\User\Models\User;
use Modules\User\Payloads\V1\UserPayload;

final readonly class StoreUserAction
{
    public function handle(UserPayload $payload): User
    {
        $user = User::create($payload->toArray());

        defer(function () use ($user) {
            Log::info('User created', [
                'user_id' => $user->id,
                'trace_id' => Context::get('trace_id'),
            ]);

            UserCreated::dispatch($user);
        });

        return $user;
    }
}

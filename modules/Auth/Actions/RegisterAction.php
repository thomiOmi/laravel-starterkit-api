<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Modules\Auth\Payloads\V1\RegisterPayload;
use Modules\User\Models\User;

final readonly class RegisterAction
{
    public function handle(RegisterPayload $payload): User
    {
        return User::create([
            'name' => $payload->name,
            'email' => $payload->email,
            'password' => $payload->password,
        ]);
    }
}

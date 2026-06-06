<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\Auth\Payloads\V1\RegisterPayload;
use Modules\User\Models\User;

final readonly class RegisterAction
{
    public function __construct(
        private DatabaseManager $database
    ) {}

    public function handle(RegisterPayload $payload): User
    {
        return $this->database->transaction(function () use ($payload) {
            return User::create([
                'name' => $payload->name,
                'email' => $payload->email,
                'password' => $payload->password,
            ]);
        });
    }
}

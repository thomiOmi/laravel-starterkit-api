<?php

declare(strict_types=1);

namespace Modules\Auth\Payloads\V1;

use Modules\Auth\Requests\V1\LoginRequest;

final readonly class LoginPayload
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $deviceName = null
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            email: strtolower(trim($request->string('email')->toString())),
            password: $request->string('password')->toString(),
            deviceName: $request->string('device_name')->toString() ?: null,
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\Payloads\V1;

use Modules\Auth\Requests\V1\RegisterRequest;

final readonly class RegisterPayload
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $deviceName = null,
    ) {}

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            name: trim($request->string('name')->toString()),
            email: strtolower(trim($request->string('email')->toString())),
            password: $request->string('password')->toString(),
            deviceName: trim($request->string('device_name')->toString()) ?: null,
        );
    }
}

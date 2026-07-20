<?php

declare(strict_types=1);

namespace Modules\IAM\Payloads\V1;

use Modules\IAM\Requests\V1\LoginRequest;

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
            email: $request->safe()->string('email')->toString(),
            password: $request->safe()->string('password')->toString(),
            deviceName: $request->safe()->string('device_name')->trim()->toString() ?: null,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return array_filter([
            'email' => $this->email,
            'password' => $this->password,
            'device_name' => $this->deviceName,
        ], fn (mixed $value) => $value !== null);
    }
}

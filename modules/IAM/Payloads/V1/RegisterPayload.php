<?php

declare(strict_types=1);

namespace Modules\IAM\Payloads\V1;

use Modules\IAM\Requests\V1\RegisterRequest;

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
        $deviceName = $request->safe()->string('device_name')->trim()->toString();

        return new self(
            name: $request->safe()->string('name')->trim()->toString(),
            email: $request->safe()->string('email')->trim()->lower()->toString(),
            password: $request->safe()->string('password')->toString(),
            deviceName: $deviceName !== '' ? $deviceName : null,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}

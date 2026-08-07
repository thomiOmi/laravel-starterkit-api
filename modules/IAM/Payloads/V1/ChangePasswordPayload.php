<?php

declare(strict_types=1);

namespace Modules\IAM\Payloads\V1;

use Modules\IAM\Requests\V1\ChangePasswordRequest;

final readonly class ChangePasswordPayload
{
    public function __construct(
        public string $currentPassword,
        public string $password,
    ) {}

    public static function fromRequest(ChangePasswordRequest $request): self
    {
        return new self(
            currentPassword: $request->safe()->string('current_password')->toString(),
            password: $request->safe()->string('password')->toString(),
        );
    }

    /**
     * @return array{current_password: string, password: string}
     */
    public function toArray(): array
    {
        return [
            'current_password' => $this->currentPassword,
            'password' => $this->password,
        ];
    }
}

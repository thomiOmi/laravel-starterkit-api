<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

readonly class ForgotPasswordDTO
{
    public function __construct(
        public string $email
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            email: $request->validated('email')
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            email: $request->validated('email'),
            password: $request->validated('password')
        );
    }
}

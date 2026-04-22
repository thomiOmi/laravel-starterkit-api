<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Illuminate\Http\Request;

readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            email: $request->validated('email'),
            password: $request->validated('password')
        );
    }
}

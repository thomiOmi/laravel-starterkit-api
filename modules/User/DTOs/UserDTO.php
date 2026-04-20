<?php

namespace Modules\User\DTOs;

readonly class UserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password')
        );
    }
}

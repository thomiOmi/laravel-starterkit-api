<?php

declare(strict_types=1);

namespace Modules\User\DTOs;

readonly class UserDTO
{
    /**
     * Create a new UserDTO instance.
     *
     * @param  string  $name  The user's name.
     * @param  string  $email  The user's email address.
     * @param  string|null  $password  The user's password (optional for updates).
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null
    ) {}

    /**
     * Create a UserDTO instance from a request.
     *
     * @param  mixed  $request  The incoming HTTP request.
     */
    public static function fromRequest($request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password')
        );
    }
}

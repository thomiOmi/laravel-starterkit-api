<?php

declare(strict_types=1);

namespace Modules\User\DTOs;

use Modules\User\Requests\UserRequest;

/**
 * Data Transfer Object for User data.
 */
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
     * @param  UserRequest  $request  The incoming HTTP request.
     * @return self The created UserDTO instance.
     */
    public static function fromRequest(UserRequest $request): self
    {
        /** @var array{name: string, email: string, password: string|null} $validated */
        $validated = $request->validated();

        return new self(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password']
        );
    }
}

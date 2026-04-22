<?php

declare(strict_types=1);

namespace Modules\User\DTOs;

use Illuminate\Foundation\Http\FormRequest;

readonly class UserDTO
{
    /**
     * Create a new UserDTO instance.
     *
     * @param  string  $name  The user's name.
     * @param  string  $email  The user's email address.
     * @param  string|null  $password  The user's password (optional for updates).
     * @param  string|null  $avatar  The user's avatar path.
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public ?string $avatar = null
    ) {}

    /**
     * Create a UserDTO instance from a request.
     *
     * @param  FormRequest  $request  The incoming HTTP request.
     */
    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            avatar: $request->validated('avatar')
        );
    }
}

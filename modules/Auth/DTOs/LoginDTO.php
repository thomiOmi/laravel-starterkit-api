<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Login DTO
 *
 * Data transfer object for login request
 */
readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            email: $request->validated('email'),
            password: $request->validated('password')
        );
    }
}

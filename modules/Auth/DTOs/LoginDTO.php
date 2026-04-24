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
        public string $password,
        public ?string $device_name = null
    ) {}

    /**
     * Create a new LoginDTO instance from a form request.
     *
     * @param  FormRequest  $request  The request to create the DTO from.
     * @return self The LoginDTO instance.
     */
    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            email: $request->validated('email'),
            password: $request->validated('password'),
            device_name: $request->validated('device_name', $request->userAgent() ?? 'auth_token')
        );
    }
}

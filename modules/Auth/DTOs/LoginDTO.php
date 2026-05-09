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
        /** @var string $email */
        $email = $request->validated('email');

        /** @var string $password */
        $password = $request->validated('password');

        /** @var string|null $deviceName */
        $deviceName = $request->validated('device_name', $request->userAgent() ?? 'auth_token');

        return new self(
            email: $email,
            password: $password,
            device_name: $deviceName
        );
    }
}

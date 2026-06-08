<?php

declare(strict_types=1);

namespace Modules\Auth\Payloads\V1;

use Modules\Auth\Requests\V1\LoginRequest;

/**
 * Payload for user login data.
 */
final readonly class LoginPayload
{
    /**
     * Create a new LoginPayload instance.
     *
     * @param  string  $email  The user's email address.
     * @param  string  $password  The user's password.
     * @param  string|null  $deviceName  Optional device name for the token.
     */
    public function __construct(
        public string $email,
        public string $password,
        public ?string $deviceName = null
    ) {}

    /**
     * Create a LoginPayload instance from a login request.
     *
     * @param  LoginRequest  $request  The incoming login request.
     * @return self The created LoginPayload instance.
     */
    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            email: strtolower(trim($request->string('email')->toString())),
            password: $request->string('password')->toString(),
            deviceName: $request->string('device_name')->toString() ?: null,
        );
    }
}

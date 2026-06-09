<?php

declare(strict_types=1);

namespace Modules\Auth\Payloads\V1;

use Modules\Auth\Requests\V1\RegisterRequest;

/**
 * Payload for user registration data.
 */
final readonly class RegisterPayload
{
    /**
     * Create a new RegisterPayload instance.
     *
     * @param  string  $name  The user's full name.
     * @param  string  $email  The user's email address.
     * @param  string  $password  The user's plain-text password.
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}

    /**
     * Create a RegisterPayload instance from a registration request.
     *
     * @param  RegisterRequest  $request  The incoming registration request.
     * @return self The created RegisterPayload instance.
     */
    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            name: trim($request->string('name')->toString()),
            email: strtolower(trim($request->string('email')->toString())),
            password: $request->string('password')->toString(),
        );
    }
}

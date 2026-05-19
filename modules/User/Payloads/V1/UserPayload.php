<?php

declare(strict_types=1);

namespace Modules\User\Payloads\V1;

use Modules\User\Requests\V1\UserRequest;

/**
 * Payload for User data.
 */
final readonly class UserPayload
{
    /**
     * Create a new UserPayload instance.
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
     * Create a UserPayload instance from a request.
     *
     * @param  UserRequest  $request  The incoming HTTP request.
     * @return self The created UserPayload instance.
     */
    public static function fromRequest(UserRequest $request): self
    {
        return new self(
            name: trim($request->string('name')->toString()),
            email: strtolower(trim($request->string('email')->toString())),
            password: $request->filled('password') ? $request->string('password')->toString() : null,
        );
    }
}

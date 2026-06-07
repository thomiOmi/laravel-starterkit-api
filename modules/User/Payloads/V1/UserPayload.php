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

    /**
     * Convert the payload to an array for Eloquent.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ], fn ($value) => $value !== null);
    }
}

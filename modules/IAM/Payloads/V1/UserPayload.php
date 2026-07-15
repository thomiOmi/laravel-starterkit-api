<?php

declare(strict_types=1);

namespace Modules\IAM\Payloads\V1;

use Modules\IAM\Requests\V1\UserRequest;

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
            name: $request->safe()->string('name')->trim()->toString(),
            email: $request->safe()->string('email')->trim()->lower()->toString(),
            password: $request->safe()->has('password') ? $request->safe()->string('password')->toString() : null,
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
        ], fn (mixed $value) => $value !== null);
    }
}

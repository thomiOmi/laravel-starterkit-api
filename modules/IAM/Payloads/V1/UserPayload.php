<?php

declare(strict_types=1);

namespace Modules\IAM\Payloads\V1;

use App\Enums\UserStatusEnum;
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
     * @param  UserStatusEnum|null  $status  The user's account status (optional for updates).
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public ?UserStatusEnum $status = null
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
            status: $request->safe()->has('status') ? $request->safe()->enum('status', UserStatusEnum::class) : null,
        );
    }

    /**
     * Convert the payload to an array for Eloquent.
     *
     * @return array{name: string, email: string, password?: string, status?: UserStatusEnum}
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'status' => $this->status,
        ], fn (mixed $value) => $value !== null);
    }
}

<?php

declare(strict_types=1);

namespace Modules\IAM\Payloads\V1;

use Modules\IAM\Requests\V1\UpdateProfileRequest;

/**
 * Payload for updating the authenticated user's profile.
 */
final readonly class UpdateProfilePayload
{
    /**
     * @param  string|null  $name  The new name (null when not provided).
     * @param  string|null  $email  The new email (null when not provided).
     * @param  string|null  $avatarMediaId  The media ID of the new avatar (null when not provided).
     */
    public function __construct(
        public ?string $name,
        public ?string $email,
        public ?string $avatarMediaId,
    ) {}

    /**
     * Create an UpdateProfilePayload instance from a request.
     */
    public static function fromRequest(UpdateProfileRequest $request): self
    {
        return new self(
            name: $request->safe()->has('name') ? $request->safe()->string('name')->trim()->toString() : null,
            email: $request->safe()->has('email') ? $request->safe()->string('email')->trim()->lower()->toString() : null,
            avatarMediaId: $request->safe()->has('avatar') ? $request->safe()->string('avatar')->toString() : null,
        );
    }

    /**
     * @return array{name: string|null, email: string|null, avatar_media_id: string|null}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'avatar_media_id' => $this->avatarMediaId,
        ];
    }
}

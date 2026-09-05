<?php

declare(strict_types=1);

namespace Modules\IAM\Payloads\V1;

use Illuminate\Http\UploadedFile;
use Modules\IAM\Http\Requests\V1\UpdateProfileRequest;

/**
 * Payload for updating the authenticated user's profile.
 */
final readonly class UpdateProfilePayload
{
    /**
     * @param  string|null  $name  The new name (null when not provided).
     * @param  string|null  $email  The new email (null when not provided).
     * @param  UploadedFile|null  $avatarFile  The new avatar file (null when not provided).
     */
    public function __construct(
        public ?string $name,
        public ?string $email,
        public ?UploadedFile $avatarFile,
    ) {}

    /**
     * Create an UpdateProfilePayload instance from a request.
     */
    public static function fromRequest(UpdateProfileRequest $request): self
    {
        $avatar = $request->file('avatar');

        return new self(
            name: $request->safe()->has('name') ? $request->safe()->string('name')->trim()->toString() : null,
            email: $request->safe()->has('email') ? $request->safe()->string('email')->trim()->toString() : null,
            avatarFile: $avatar instanceof UploadedFile ? $avatar : null,
        );
    }

    /**
     * @return array{name: string|null, email: string|null}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}

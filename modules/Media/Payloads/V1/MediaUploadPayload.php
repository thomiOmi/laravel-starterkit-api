<?php

declare(strict_types=1);

namespace Modules\Media\Payloads\V1;

use App\Enums\MediaVisibilityEnum;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Modules\Media\Requests\V1\MediaUploadRequest;

/**
 * Payload for Upload Media data.
 */
final readonly class MediaUploadPayload
{
    public function __construct(
        public UploadedFile $file,
        public MediaVisibilityEnum $visibility,
        public string $uploadedBy,
    ) {}

    /**
     * Create a payload from the request.
     */
    public static function fromRequest(MediaUploadRequest $request): self
    {
        $file = $request->file('file');

        $userId = $request->user()?->getAuthIdentifier();

        if (! is_string($userId)) {
            throw new InvalidArgumentException('Authenticated user is required.');
        }

        return new self(
            file: $file instanceof UploadedFile ? $file : throw new InvalidArgumentException('Uploaded file is required.'),
            visibility: $request->enum('visibility', MediaVisibilityEnum::class) ?? MediaVisibilityEnum::Public,
            uploadedBy: $userId,
        );
    }

    /**
     * Convert the payload to an array for Eloquent.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'visibility' => $this->visibility->value,
            'uploaded_by' => $this->uploadedBy,
        ];
    }
}

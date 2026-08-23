<?php

declare(strict_types=1);

namespace Modules\Media\Payloads\V1;

use App\Enums\MediaCollection;
use Illuminate\Http\UploadedFile;
use Modules\Media\Http\Requests\V1\MediaUploadRequest;

/**
 * Payload for a media upload.
 */
final readonly class MediaUploadPayload
{
    public function __construct(
        public UploadedFile $file,
        public string $collectionName,
    ) {}

    public static function fromRequest(MediaUploadRequest $request): self
    {
        $collection = $request->safe()->string('collection_name')->trim()->toString();

        return new self(
            file: $request->file('file'),
            collectionName: $collection !== '' ? $collection : MediaCollection::Default->value,
        );
    }
}

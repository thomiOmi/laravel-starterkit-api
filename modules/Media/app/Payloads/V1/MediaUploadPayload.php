<?php

declare(strict_types=1);

namespace Modules\Media\Payloads\V1;

use Illuminate\Http\UploadedFile;
use Modules\Media\Http\Requests\V1\MediaUploadRequest;

/**
 * Payload for a media upload.
 */
final readonly class MediaUploadPayload
{
    /**
     * @param  array<string, string>  $customHeaders
     */
    public function __construct(
        public UploadedFile $file,
        public string $collectionName,
        public bool $preservingOriginal = false,
        public ?string $conversionsDisk = null,
        public ?string $onQueue = null,
        public array $customHeaders = [],
    ) {}

    public static function fromRequest(MediaUploadRequest $request): self
    {
        $collection = $request->safe()->string('collection_name')->trim()->toString();
        /** @var mixed $file */
        $file = $request->file('file');

        if ($file instanceof UploadedFile) {
            $resolved = $file;
        } elseif (is_array($file) && isset($file[0]) && $file[0] instanceof UploadedFile) {
            $resolved = $file[0];
        } else {
            throw new \InvalidArgumentException('File is required.');
        }

        return new self(
            file: $resolved,
            collectionName: $collection !== '' ? $collection : 'default',
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Media\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Modules\Media\Actions\UploadMediaAction;
use Modules\Media\Models\Media;
use Modules\Media\Payloads\V1\MediaUploadPayload;

/**
 * Fluent builder for attaching media to a model.
 *
 * Usage:
 * $post->addMedia($file)->usingName('cover')->withCustomProperties(['alt' => '...'])->toMediaCollection('images');
 */
final class PendingMedia
{
    private ?string $name = null;

    /** @var array<string, mixed> */
    private array $customProperties = [];

    private ?string $disk = null;

    public function __construct(
        private readonly Model $model,
        private readonly UploadedFile $file,
    ) {}

    public function usingName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function withCustomProperties(array $properties): self
    {
        $this->customProperties = $properties;

        return $this;
    }

    public function withDisk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    public function toMediaCollection(string $collection = 'default', ?string $disk = null): Media
    {
        $payload = new MediaUploadPayload(
            file: $this->file,
            collectionName: $collection,
        );

        // Resolve the action via container to keep Media module self-contained.
        $action = app(UploadMediaAction::class);

        // If custom disk is provided, temporarily override config for this call.
        $effectiveDisk = $disk ?? $this->disk;

        if ($effectiveDisk !== null) {
            config(['media.disk' => $effectiveDisk]);
        }

        // Determine uploader from auth context if available.
        $uploader = null;

        if (auth()->check()) {
            $authUser = auth()->user();
            if ($authUser instanceof Model) {
                $uploader = $authUser;
            }
        }

        $result = $action->handle($payload, $this->model, $uploader);

        /** @var Media $media */
        $media = $result['media'];

        // Apply custom properties and name if provided.
        if ($this->customProperties !== [] || $this->name !== null) {
            if ($this->customProperties !== []) {
                $media->custom_properties = array_merge(
                    is_array($media->custom_properties) ? $media->custom_properties : [],
                    $this->customProperties
                );
            }

            if ($this->name !== null) {
                $media->original_name = $this->name;
            }

            $media->save();
        }

        return $media->fresh() ?? $media;
    }
}

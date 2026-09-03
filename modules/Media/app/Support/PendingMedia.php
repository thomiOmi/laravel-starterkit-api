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

    private ?string $fileName = null;

    private bool $preservingOriginal = false;

    /** @var array<string, mixed> */
    private array $manipulations = [];

    /** @var callable|null */
    private $sanitizer = null;

    public function __construct(
        private readonly Model $model,
        private readonly UploadedFile $file,
    ) {}

    public function usingName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function usingFileName(string $fileName): self
    {
        $this->fileName = $fileName;

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

    /**
     * @param  array<string, mixed>  $manipulations
     */
    public function withManipulations(array $manipulations): self
    {
        $this->manipulations = $manipulations;

        return $this;
    }

    public function preservingOriginal(): self
    {
        $this->preservingOriginal = true;

        return $this;
    }

    public function sanitizingFileName(callable $callback): self
    {
        $this->sanitizer = $callback;

        return $this;
    }

    public function withDisk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    public function toMediaCollection(string $collection = 'default', ?string $disk = null): Media
    {
        $file = $this->file;

        // Handle usingFileName
        if ($this->fileName !== null) {
            $path = $file->getRealPath();
            $path = is_string($path) ? $path : $file->getPathname();
            $file = new UploadedFile($path, $this->fileName, $file->getMimeType(), null, true);
        }

        // Handle sanitizingFileName
        if ($this->sanitizer !== null) {
            $rawSanitized = ($this->sanitizer)($file->getClientOriginalName());

            if (is_string($rawSanitized)) {
                $sanitized = $rawSanitized;
            } elseif (is_int($rawSanitized) || is_float($rawSanitized)) {
                $sanitized = (string) $rawSanitized;
            } else {
                $sanitized = 'file';
            }

            $path = $file->getRealPath();
            $path = is_string($path) ? $path : $file->getPathname();
            $file = new UploadedFile($path, $sanitized, $file->getMimeType(), null, true);
        }

        $payload = new MediaUploadPayload(
            file: $file,
            collectionName: $collection,
            preservingOriginal: $this->preservingOriginal,
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

        // Apply custom properties, manipulations and name if provided.
        $hasCustom = $this->customProperties !== [] || $this->manipulations !== [] || $this->name !== null;

        if ($hasCustom) {
            if ($this->customProperties !== [] || $this->manipulations !== []) {
                $merged = array_merge(
                    is_array($media->custom_properties) ? $media->custom_properties : [],
                    $this->customProperties
                );

                if ($this->manipulations !== []) {
                    $merged['manipulations'] = $this->manipulations;
                }

                $media->custom_properties = $merged;
            }

            if ($this->name !== null) {
                $media->original_name = $this->name;
            }

            $media->save();
        }

        return $media->fresh() ?? $media;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Media\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;

/**
 * Fluent helper to attach a file to a HasMedia model.
 */
final class FileAdder
{
    private string $collectionName = 'default';

    private string $disk;

    /** @var array<string, mixed> */
    private array $customProperties = [];

    private ?string $fileName = null;

    private ?string $name = null;

    private bool $preserveOriginal = false;

    /** @var array<string, mixed> */
    private array $manipulations = [];

    private bool $withResponsiveImages = false;

    /** @var Closure|null */
    private mixed $fileNameSanitizer = null;

    public function __construct(
        private readonly Model $model,
        private readonly UploadedFile|string $file,
    ) {
        $this->disk = config()->string('media.disk', 'public');
    }

    public function toMediaCollection(string $collectionName): Media
    {
        $this->collectionName = $collectionName;

        return $this->store();
    }

    public function toMediaCollectionOnCloudDisk(string $collectionName): Media
    {
        return $this->usingDisk(config()->string('filesystems.cloud', 's3'))->toMediaCollection($collectionName);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function withCustomProperties(array $properties): self
    {
        $this->customProperties = $properties;

        return $this;
    }

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

    public function sanitizingFileName(Closure $callback): self
    {
        $this->fileNameSanitizer = $callback;

        return $this;
    }

    public function preservingOriginal(): self
    {
        $this->preserveOriginal = true;

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

    public function withResponsiveImages(): self
    {
        $this->withResponsiveImages = true;

        return $this;
    }

    public function usingDisk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    private function store(): Media
    {
        $file = $this->file instanceof UploadedFile ? $this->file : new UploadedFile($this->file, basename($this->file));

        /** @var string $fileName */
        $fileName = $this->fileName ?? $file->hashName();

        if ($this->fileNameSanitizer !== null) {
            $sanitized = ($this->fileNameSanitizer)($fileName);
            if (is_string($sanitized) && $sanitized !== '') {
                $fileName = $sanitized;
            }
        }

        /** @var string $fileName */
        if ($this->name !== null) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $fileName = $this->name.'.'.$extension;
        }

        if ($this->preserveOriginal) {
            $fileName = $file->getClientOriginalName();
        }

        $path = $this->collectionName.'/'.$fileName;

        Storage::disk($this->disk)->putFileAs($this->collectionName, $file, $fileName);

        $key = $this->model->getKey();
        $stringKey = is_string($key) || is_int($key) ? (string) $key : null;

        // Handle manipulations and responsive images flags if needed - for now just store
        if ($this->withResponsiveImages) {
            // Placeholder for responsive images generation
        }

        $meta = array_merge(['original_name' => $file->getClientOriginalName()], $this->customProperties);

        if ($this->manipulations !== []) {
            $meta['manipulations'] = $this->manipulations;
        }

        if ($this->withResponsiveImages) {
            $meta['responsive'] = true;
        }

        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        $media = Media::query()->create([
            'collection_name' => $this->collectionName,
            'name' => $name,
            'file_name' => $fileName,
            'disk' => $this->disk,
            'conversions_disk' => $this->disk,
            'mime_type' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'visibility' => 'private',
            'original_name' => $file->getClientOriginalName(),
            'original_extension' => $extension !== '' ? $extension : null,
            'sha256' => is_string($file->getRealPath()) ? hash_file('sha256', $file->getRealPath()) : null,
            'manipulations' => $this->manipulations !== [] ? $this->manipulations : [],
            'custom_properties' => $this->customProperties !== [] ? $this->customProperties : null,
            'generated_conversions' => [],
            'responsive_images' => [],
            'meta' => $meta,
            'uploaded_by_type' => null,
            'uploaded_by_id' => null,
            'model_type' => $this->model->getMorphClass(),
            'model_id' => $stringKey,
            'order_column' => $this->nextOrderColumn(),
        ]);

        return $media;
    }

    private function nextOrderColumn(): int
    {
        $key = $this->model->getKey();
        $stringKey = is_string($key) || is_int($key) ? (string) $key : '';

        $max = Media::query()
            ->where('model_type', $this->model::class)
            ->where('model_id', $stringKey)
            ->where('collection_name', $this->collectionName)
            ->max('order_column');

        return is_int($max) ? $max + 1 : 1;
    }
}

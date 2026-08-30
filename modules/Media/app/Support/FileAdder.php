<?php

declare(strict_types=1);

namespace Modules\Media\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Contracts\HasMedia;
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

    /**
     * @param  array<string, mixed>  $properties
     */
    public function withCustomProperties(array $properties): self
    {
        $this->customProperties = $properties;

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

        $filename = $file->hashName();
        $path = $this->collectionName.'/'.$filename;

        Storage::disk($this->disk)->putFileAs($this->collectionName, $file, $filename);

        $key = $this->model->getKey();
        $stringKey = is_string($key) || is_int($key) ? (string) $key : null;

        $media = Media::query()->create([
            'collection_name' => $this->collectionName,
            'disk' => $this->disk,
            'mime_type' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'path' => $path,
            'visibility' => 'private',
            'meta' => array_merge(['original_name' => $file->getClientOriginalName()], $this->customProperties),
            'uploaded_by' => $stringKey,
            'model_type' => $this->model::class,
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

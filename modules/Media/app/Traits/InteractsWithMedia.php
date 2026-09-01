<?php

declare(strict_types=1);

namespace Modules\Media\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Modules\Media\Actions\DeleteMediaAction;
use Modules\Media\Actions\ReorderMediaAction;
use Modules\Media\Models\Media;
use Modules\Media\Support\MediaCollection;
use Modules\Media\Support\MediaConversion;
use Modules\Media\Support\PendingMedia;

/**
 * Provides media relationship and helper methods for owning models.
 *
 * @mixin Model
 */
trait InteractsWithMedia
{
    /**
     * Get the media owned by the model.
     *
     * @return MorphMany<Media, Model>
     */
    public function media(): MorphMany
    {
        return $this->getModelForMedia()->morphMany(Media::class, 'model');
    }

    private function getModelForMedia(): Model
    {
        if (! $this instanceof Model) {
            throw new \LogicException('InteractsWithMedia can only be used on Eloquent models.');
        }

        return $this;
    }

    /**
     * Start a fluent media attachment for the model.
     */
    public function addMedia(UploadedFile $file): PendingMedia
    {
        if (! $this instanceof Model) {
            throw new \LogicException('InteractsWithMedia can only be used on Eloquent models.');
        }

        return new PendingMedia($this, $file);
    }

    public function addMediaFromRequest(string $key): PendingMedia
    {
        /** @var mixed $file */
        $file = request()->file($key);

        if (! $file instanceof UploadedFile) {
            throw new \InvalidArgumentException("File not found for key {$key}.");
        }

        return $this->addMedia($file);
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<int, PendingMedia>
     */
    public function addMultipleMediaFromRequest(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            /** @var mixed $files */
            $files = request()->file($key);

            if ($files instanceof UploadedFile) {
                $result[] = $this->addMedia($files);
            } elseif (is_array($files)) {
                foreach ($files as $file) {
                    /** @var mixed $file */
                    if ($file instanceof UploadedFile) {
                        $result[] = $this->addMedia($file);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function addMediaFromUrl(string $url, ?string $filename = null, array $headers = []): PendingMedia
    {
        $response = Http::withHeaders($headers)->get($url);

        if (! $response->successful()) {
            throw new \InvalidArgumentException("Failed to fetch URL: {$url}");
        }

        $content = $response->body();
        $rawPath = parse_url($url, PHP_URL_PATH);
        $path = is_string($rawPath) && $rawPath !== '' ? $rawPath : 'file';
        $name = $filename ?? basename($path);

        return $this->addMediaFromString($content, $name);
    }

    public function addMediaFromString(string $content, string $filename): PendingMedia
    {
        $file = UploadedFile::fake()->createWithContent($filename, $content);

        return $this->addMedia($file);
    }

    /**
     * Get all media for the given collection ordered by order_column.
     *
     * @return Collection<int, Media>
     */
    public function getMedia(string $collection = 'default'): Collection
    {
        return $this->media()
            ->where('collection_name', $collection)
            ->orderBy('order_column')
            ->get();
    }

    public function getFirstMedia(string $collection = 'default'): ?Media
    {
        return $this->media()
            ->where('collection_name', $collection)
            ->orderBy('order_column')
            ->first();
    }

    public function getFirstMediaUrl(string $collection = 'default'): ?string
    {
        return $this->getFirstMedia($collection)?->url();
    }

    public function getFirstMediaSignedUrl(string $collection = 'default', int $ttlMinutes = 10): ?string
    {
        return $this->getFirstMedia($collection)?->signedUrl($ttlMinutes);
    }

    /**
     * Clear all media from the given collection.
     */
    public function clearMediaCollection(string $collection): int
    {
        $media = $this->getMedia($collection);

        $count = 0;

        foreach ($media as $item) {
            app(DeleteMediaAction::class)->handle($item);
            $count++;
        }

        return $count;
    }

    /**
     * Reorder media within a collection.
     *
     * @param  array<int, string>  $orderedIds
     */
    public function reorderMedia(string $collection, array $orderedIds): void
    {
        if (! $this instanceof Model) {
            throw new \LogicException('InteractsWithMedia can only be used on Eloquent models.');
        }

        app(ReorderMediaAction::class)->handle($this, $collection, $orderedIds);
    }

    /**
     * Register the media collections for the model.
     * Override in the model to define collections.
     */
    public function registerMediaCollections(): void
    {
        //
    }

    /**
     * Register the media conversions for the model.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        //
    }

    /** @var array<string, MediaCollection> */
    private array $mediaCollections = [];

    /** @var array<string, MediaConversion> */
    private array $mediaConversions = [];

    public function addMediaCollection(string $name): MediaCollection
    {
        $collection = new MediaCollection($name);
        $this->mediaCollections[$name] = $collection;

        return $collection;
    }

    public function addMediaConversion(string $name): MediaConversion
    {
        $conversion = new MediaConversion($name);
        $this->mediaConversions[$name] = $conversion;

        return $conversion;
    }

    public function getMediaCollection(string $name): ?MediaCollection
    {
        if ($this->mediaCollections === []) {
            $this->registerMediaCollections();
        }

        return $this->mediaCollections[$name] ?? null;
    }

    /**
     * @return array<string, MediaCollection>
     */
    public function getMediaCollections(): array
    {
        if ($this->mediaCollections === []) {
            $this->registerMediaCollections();
        }

        return $this->mediaCollections;
    }

    /**
     * @return array<string, MediaConversion>
     */
    public function getMediaConversions(?Media $media = null): array
    {
        if ($this->mediaConversions === []) {
            $this->registerMediaConversions($media);
        }

        return $this->mediaConversions;
    }
}

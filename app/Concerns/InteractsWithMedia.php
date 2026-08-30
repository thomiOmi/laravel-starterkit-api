<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Modules\Media\Models\Media;
use Modules\Media\Support\FileAdder;

/**
 * @mixin Model
 */
trait InteractsWithMedia
{
    /**
     * Get all media attached to the model.
     *
     * @return MorphMany<Media, $this>
     */
    public function media(): MorphMany
    {
        /** @var MorphMany<Media, $this> $relation */
        $relation = $this->morphMany(Media::class, 'model');

        return $relation;
    }

    /**
     * Add a file to a media collection.
     */
    public function addMedia(UploadedFile|string $file): FileAdder
    {
        return new FileAdder($this, $file);
    }

    /**
     * Add media from the current request.
     */
    public function addMediaFromRequest(string $key): FileAdder
    {
        /** @var UploadedFile $file */
        $file = request()->file($key);

        return $this->addMedia($file);
    }

    /**
     * Get all media for the given collection.
     *
     * @return Collection<int, Media>
     */
    public function getMedia(string $collectionName): Collection
    {
        return $this->media()->where('collection_name', $collectionName)->ordered()->get();
    }

    /**
     * Get the first media for the given collection.
     */
    public function getFirstMedia(string $collectionName): ?Media
    {
        return $this->media()->where('collection_name', $collectionName)->ordered()->first();
    }

    /**
     * Get the URL of the first media in the collection.
     */
    public function getFirstMediaUrl(string $collectionName, string $conversion = ''): string
    {
        $media = $this->getFirstMedia($collectionName);

        return $media !== null ? $media->getUrl($conversion) : '';
    }

    /**
     * Determine if media exists in the collection.
     */
    public function hasMedia(string $collectionName): bool
    {
        return $this->media()->where('collection_name', $collectionName)->exists();
    }

    /**
     * Clear the media collection.
     */
    public function clearMediaCollection(string $collectionName): void
    {
        $this->media()->where('collection_name', $collectionName)->get()->each(function (Media $media): void {
            $media->delete();
        });
    }

    /**
     * Define a media collection.
     */
    public function addMediaCollection(string $name): object
    {
        return new class
        {
            public function singleFile(): self
            {
                return $this;
            }
        };
    }

    /**
     * Register the media collections for the model.
     */
    public function registerMediaCollections(): void
    {
        //
    }

    /**
     * Register the media conversions for the model.
     */
    public function registerMediaConversions(mixed $media = null): void
    {
        //
    }
}

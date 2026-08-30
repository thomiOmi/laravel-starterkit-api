<?php

declare(strict_types=1);

namespace Modules\Media\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Modules\Media\Actions\DeleteMediaAction;
use Modules\Media\Models\Media;
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
}

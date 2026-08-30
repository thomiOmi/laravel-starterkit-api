<?php

declare(strict_types=1);

namespace Modules\Media\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Modules\Media\Enums\MediaVisibilityEnum;
use Modules\Media\Models\Media;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Media::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'model_type' => null,
            'model_id' => null,
            'collection_name' => 'default',
            'disk' => 'public',
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(1024, 512_000),
            'path' => fake()->unique()->md5().'.png',
            'visibility' => MediaVisibilityEnum::Private,
            'original_name' => fake()->word().'.png',
            'original_extension' => 'png',
            'sha256' => hash('sha256', (string) fake()->unique()->md5()),
            'meta' => ['original_name' => fake()->word().'.png'],
            'custom_properties' => null,
            'order_column' => 0,
            'uploaded_by_type' => null,
            'uploaded_by_id' => null,
        ];
    }

    /**
     * Attach the media to the given model as owner.
     */
    public function forModel(Model $model, string $collection = 'default'): static
    {
        return $this->state(fn (): array => [
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'collection_name' => $collection,
        ]);
    }

    /**
     * Attach the media with the given uploader.
     */
    public function uploadedBy(Model $uploader): static
    {
        return $this->state(fn (): array => [
            'uploaded_by_type' => $uploader->getMorphClass(),
            'uploaded_by_id' => $uploader->getKey(),
        ]);
    }

    /**
     * Place the media in the given collection.
     */
    public function inCollection(string $collectionName): static
    {
        return $this->state(fn (): array => ['collection_name' => $collectionName]);
    }

    /**
     * Mark the media publicly visible.
     */
    public function public(): static
    {
        return $this->state(fn (): array => ['visibility' => MediaVisibilityEnum::Public]);
    }
}

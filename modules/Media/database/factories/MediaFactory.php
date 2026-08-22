<?php

declare(strict_types=1);

namespace Modules\Media\Database\Factories;

use App\Enums\MediaVisibilityEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\IAM\Models\User;
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
            'collection_name' => 'default',
            'disk' => 'public',
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(1024, 512_000),
            'path' => fake()->unique()->md5().'.png',
            'visibility' => MediaVisibilityEnum::Private,
            'meta' => ['original_name' => fake()->word().'.png'],
        ];
    }

    /**
     * Attach the media to the given owner.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (): array => ['uploaded_by' => $user->id]);
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

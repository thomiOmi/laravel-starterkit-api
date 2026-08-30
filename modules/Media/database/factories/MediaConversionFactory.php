<?php

declare(strict_types=1);

namespace Modules\Media\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaConversion;

/**
 * @extends Factory<MediaConversion>
 */
class MediaConversionFactory extends Factory
{
    protected $model = MediaConversion::class;

    public function definition(): array
    {
        return [
            'media_id' => Media::factory(),
            'name' => fake()->randomElement(['thumbnail', 'medium', 'large']),
            'disk' => 'public',
            'path' => 'conversions/'.fake()->uuid().'.webp',
            'mime_type' => 'image/webp',
            'size' => fake()->numberBetween(1024, 200000),
            'etag' => fake()->md5(),
        ];
    }

    public function forMedia(Media $media, string $name = 'thumbnail'): static
    {
        return $this->state(fn (): array => [
            'media_id' => $media->id,
            'name' => $name,
        ]);
    }
}

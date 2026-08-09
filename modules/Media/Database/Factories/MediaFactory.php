<?php

declare(strict_types=1);

namespace Modules\Media\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\Media\Models\Media;

/**
 * @extends Factory<Media>
 */
#[UseModel(Media::class)]
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'disk' => fake()->randomElement(['public', 'local']),
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(1024, 5242880),
            'path' => 'media/'.fake()->uuid().'.png',
            'meta' => [
                'original_name' => fake()->word().'.png',
                'extension' => 'png',
            ],
            'uploaded_by' => UserFactory::new(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\ApiKey\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ApiKey\Models\ApiKey;
use Modules\User\Models\User;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ApiKey>
     */
    protected $model = ApiKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->word(),
            'key' => hash('sha256', 'test-key'),
            'secret_prefix' => 'sk_',
            'abilities' => ['*'],
            'ip_whitelist' => null,
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }
}

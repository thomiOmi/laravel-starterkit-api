<?php

declare(strict_types=1);

namespace Modules\ApiKey\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\ApiKey\Models\ApiKey;
use Modules\User\Models\User;

class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $plainKey = Str::random(40);

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->word.' Key',
            'key' => hash('sha256', $plainKey),
            'secret_prefix' => Str::substr($plainKey, 0, 8),
            'abilities' => ['*'],
            'ip_whitelist' => null,
            'expires_at' => null,
        ];
    }
}

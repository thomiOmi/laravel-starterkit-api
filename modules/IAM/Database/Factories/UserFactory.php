<?php

declare(strict_types=1);

namespace Modules\IAM\Database\Factories;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\IAM\Models\User;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<User>, mixed>
     */
    public function definition(): array
    {
        $defaultPassword = config('auth.default_password');

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make(filled($defaultPassword) ? (string) $defaultPassword : Str::random(32)),
            'remember_token' => Str::random(10),
            'provider' => null,
            'provider_id' => null,
            'avatar' => null,
            'deleted_at' => null,
        ];
    }

    /**
     * Indicate that the user's account is pending email verification.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Pending,
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is a social login user.
     *
     * @param  string  $provider  The social provider name.
     */
    public function social(string $provider = 'google'): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => null,
            'provider' => $provider,
            'provider_id' => fake()->numerify($provider.'-######'),
            'avatar' => fake()->imageUrl(),
        ]);
    }
}

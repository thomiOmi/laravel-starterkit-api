<?php

declare(strict_types=1);

namespace Modules\Role\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Role\Models\Permission;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<Permission>, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'guard_name' => 'web',
        ];
    }
}

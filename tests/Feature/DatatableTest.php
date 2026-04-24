<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Tests\TestCase;

class DatatableTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    /**
     * Test datatable for a given endpoint.
     */
    protected function runDatatableTests(string $endpoint, string $modelClass, string $searchField = 'name'): void
    {
        // 1. Test Pagination
        $modelClass::factory()->count(20)->create();
        $response = $this->actingAs($this->admin)
            ->getJson("{$endpoint}?per_page=10&page=2");

        $response->assertStatus(200)
            ->assertJsonPath('meta.pagination.current_page', 2)
            ->assertJsonPath('meta.pagination.per_page', 10)
            ->assertJsonCount(10, 'data');

        // 2. Test Sorting
        $modelClass::query()->delete();
        $modelClass::factory()->create([$searchField => 'AAA Record']);
        $modelClass::factory()->create([$searchField => 'ZZZ Record']);

        $response = $this->actingAs($this->admin)
            ->getJson("{$endpoint}?sort_by={$searchField}&sort_direction=asc");
        $this->assertEquals('AAA Record', $response->json('data.0.'.$searchField));

        $response = $this->actingAs($this->admin)
            ->getJson("{$endpoint}?sort_by={$searchField}&sort_direction=desc");
        $this->assertEquals('ZZZ Record', $response->json('data.0.'.$searchField));

        // 3. Test Global Search
        $modelClass::factory()->create([$searchField => 'UniqueSearchTerm']);
        $response = $this->actingAs($this->admin)
            ->getJson("{$endpoint}?search=UniqueSearchTerm");

        $response->assertStatus(200)
            ->assertJsonFragment([$searchField => 'UniqueSearchTerm']);
    }

    public function test_user_datatable(): void
    {
        $this->runDatatableTests('/api/users', User::class);
    }

    public function test_role_datatable(): void
    {
        // Spatie Role doesn't have a factory by default in this setup,
        // but we can test the endpoint exists and returns the structure
        $response = $this->actingAs($this->admin)
            ->getJson('/api/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'meta' => ['pagination'],
                'meta',
            ]);
    }
}

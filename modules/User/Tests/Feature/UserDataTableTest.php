<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Tests\TestCase;

class UserDataTableTest extends TestCase
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

    public function test_datatable_pagination(): void
    {
        User::factory()->count(20)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/users?per_page=10&page=2');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonCount(10, 'data');
    }

    public function test_datatable_sorting(): void
    {
        User::query()->delete(); // Clear users from factory

        User::factory()->create(['name' => 'AAA User', 'email' => 'aaa@example.com']);
        User::factory()->create(['name' => 'ZZZ User', 'email' => 'zzz@example.com']);
        User::factory()->create(['name' => 'MMM User', 'email' => 'mmm@example.com']);

        // Sort by name ASC
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/users?sort_by=name&sort_direction=asc');

        $response->assertStatus(200);
        $this->assertEquals('AAA User', $response->json('data.0.name'));

        // Sort by name DESC
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/users?sort_by=name&sort_direction=desc');

        $response->assertStatus(200);
        $this->assertEquals('ZZZ User', $response->json('data.0.name'));
    }

    public function test_datatable_global_search(): void
    {
        User::factory()->create(['name' => 'UniqueName SearchMe', 'email' => 'search@example.com']);
        User::factory()->create(['name' => 'Other User', 'email' => 'other@example.com']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/users?search=SearchMe');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'UniqueName SearchMe');
    }

    public function test_datatable_column_filtering(): void
    {
        User::factory()->create(['name' => 'Target User', 'email' => 'filter_me@example.com']);
        User::factory()->create(['name' => 'Ignore Me', 'email' => 'ignore@example.com']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/users?filters[email]=filter_me');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'filter_me@example.com');
    }

    public function test_bulk_delete_users(): void
    {
        $users = User::factory()->count(5)->create();
        $ids = $users->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/users/bulk-delete', [
                'ids' => $ids,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.count', 5);

        foreach ($ids as $id) {
            $this->assertSoftDeleted('users', ['id' => $id]);
        }
    }
}

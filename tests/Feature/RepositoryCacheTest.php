<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;
use Tests\TestCase;

class RepositoryCacheTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Force array driver for predictable testing
        config([
            'cache.default' => 'array',
            'cache_enterprise.enabled' => true,
            'cache_enterprise.default_ttl' => 3600,
        ]);

        Cache::flush();
        $this->repository = app(UserRepository::class);
    }

    public function test_find_by_id_caches_result(): void
    {
        $user = User::factory()->create(['name' => 'Original']);

        // First call to findById will populate cache
        $this->repository->findById($user->id);

        // Update DB directly bypassing repository/cache
        User::where('id', $user->id)->update(['name' => 'Direct DB Update']);

        // Repository should return 'Original' from cache, not 'Direct DB Update'
        $cached = $this->repository->findById($user->id);
        $this->assertEquals('Original', $cached->name);
    }

    public function test_clear_cache_manually(): void
    {
        $user = User::factory()->create(['name' => 'Initial']);

        // Set version to 1 and cache data
        $baseKey = strtolower(str_replace('\\', '.', get_class($this->repository)));
        Cache::put($baseKey.'.version', 1);

        $this->repository->findById($user->id);

        // Update DB directly
        User::where('id', $user->id)->update(['name' => 'Updated']);

        // Trigger cache invalidation via repository helper (protected, use reflection)
        $reflection = new \ReflectionMethod($this->repository, 'clearCache');
        $reflection->setAccessible(true);
        $reflection->invoke($this->repository);

        $newVersion = Cache::get($baseKey.'.version');
        $this->assertEquals(2, $newVersion, 'Version should have been incremented to 2');

        $fresh = $this->repository->findById($user->id);
        $this->assertEquals('Updated', $fresh->name);
    }

    public function test_repository_operations_invalidate_cache(): void
    {
        $user = User::factory()->create(['name' => 'Original']);
        $baseKey = strtolower(str_replace('\\', '.', get_class($this->repository)));
        Cache::put($baseKey.'.version', 1);

        // 1. Test Update
        $this->repository->findById($user->id);
        $this->repository->update($user->id, ['name' => 'Updated via Repo']);
        $this->assertEquals(2, Cache::get($baseKey.'.version'));

        // 2. Test Create
        $this->repository->findById($user->id);
        $this->repository->create([
            'name' => 'New User',
            'email' => 'new'.rand().'@example.com',
            'password' => 'password123',
        ]);
        $this->assertEquals(3, Cache::get($baseKey.'.version'));

        // 3. Test Delete
        $this->repository->findById($user->id);
        $this->repository->delete($user->id);
        $this->assertEquals(4, Cache::get($baseKey.'.version'));
    }
}

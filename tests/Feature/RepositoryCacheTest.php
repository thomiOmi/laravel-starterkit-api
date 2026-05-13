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
            'cache_enterprise.default_ttl' => 3600
        ]);

        Cache::flush();
        $this->repository = app(UserRepository::class);
    }

    public function test_find_by_id_caches_result(): void
    {
        $user = User::factory()->create(['name' => 'Original']);

        // Populate cache
        $this->repository->findById($user->id);

        // Update DB directly bypassing cache
        User::where('id', $user->id)->update(['name' => 'Direct DB Update']);

        // Should still return 'Original' from cache
        $cached = $this->repository->findById($user->id);
        $this->assertEquals('Original', $cached->name);
    }

    public function test_clear_cache_manually(): void
    {
        $user = User::factory()->create();

        $this->repository->findById($user->id);

        // Update DB directly
        User::where('id', $user->id)->update(['name' => 'Updated']);

        // Clear cache via facade (this is what clearCache falls back to for array)
        Cache::flush();

        $fresh = $this->repository->findById($user->id);
        $this->assertEquals('Updated', $fresh->name);
    }
}

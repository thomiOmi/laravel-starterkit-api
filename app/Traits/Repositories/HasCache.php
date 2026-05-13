<?php

declare(strict_types=1);

namespace App\Traits\Repositories;

use Illuminate\Support\Facades\Cache;

/**
 * Trait for adding caching capabilities to repositories.
 */
trait HasCache
{
    /**
     * Get data from cache or store it if not present.
     *
     * @param  string  $key  The cache key.
     * @param  \Closure  $callback  The callback to retrieve data.
     * @param  int|null  $ttl  Cache time-to-live in seconds (default: from config).
     * @return mixed
     */
    protected function cache(string $key, \Closure $callback, ?int $ttl = null): mixed
    {
        if (config('cache_enterprise.enabled', true) === false) {
            return $callback();
        }

        $configTtl = config('cache_enterprise.default_ttl', 3600);
        $ttl = $ttl ?? (is_numeric($configTtl) ? (int) $configTtl : 3600);

        /** @var mixed $result */
        $result = Cache::get($this->getCacheKey($key));

        if ($result !== null) {
            return $result;
        }

        $result = $callback();
        Cache::put($this->getCacheKey($key), $result, $ttl);

        return $result;
    }

    /**
     * Generate a unique cache key based on the repository, version, and parameters.
     */
    protected function getCacheKey(string $key): string
    {
        $baseKey = strtolower(str_replace('\\', '.', get_class($this)));
        $version = Cache::get($baseKey . '.version', '1');
        $versionString = is_scalar($version) ? (string) $version : '1';

        return "{$baseKey}.v{$versionString}.{$key}";
    }

    /**
     * Invalidate cache for this repository by incrementing the version.
     */
    protected function clearCache(): void
    {
        $baseKey = strtolower(str_replace('\\', '.', get_class($this)));

        try {
            // Try to increment existing version
            Cache::increment($baseKey . '.version');
        } catch (\Throwable $e) {
            // If increment fails or key doesn't exist, set a new timestamp-based version
            Cache::put($baseKey . '.version', (string) now()->timestamp, 86400 * 30);
        }
    }
}

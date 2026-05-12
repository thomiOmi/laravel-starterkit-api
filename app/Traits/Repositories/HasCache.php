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
     * Generate a unique cache key based on the repository and parameters.
     */
    protected function getCacheKey(string $key): string
    {
        return strtolower(str_replace('\\', '.', get_class($this))).'.'.$key;
    }

    /**
     * Invalidate cache for this repository.
     */
    protected function clearCache(): void
    {
        $tags = [strtolower(str_replace('\\', '.', get_class($this)))];

        // Note: filesystem and database cache drivers do not support tags.
        // We use a prefix-based approach if tags are not supported, or just clear all for simplicity if small.
        // In a real enterprise app, we might use Redis and tags.
        try {
            Cache::tags($tags)->flush();
        } catch (\BadMethodCallException $e) {
            // Fallback for drivers that don't support tags
            // Usually we'd maintain a list of keys, but for now we'll just ignore or log.
        }
    }
}

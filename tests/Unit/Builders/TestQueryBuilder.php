<?php

declare(strict_types=1);

namespace Tests\Unit\Builders;

use App\Builders\BaseQueryBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * Test-only {@see BaseQueryBuilder} subclass with configurable whitelist metadata.
 *
 * @template TModel of Model
 *
 * @extends BaseQueryBuilder<TModel>
 */
final class TestQueryBuilder extends BaseQueryBuilder
{
    /**
     * Override the whitelist metadata with per-test configuration.
     *
     * @param  array{
     *     allowedFilters?: array<int, string>,
     *     allowedSorts?: array<int, string>,
     *     allowedFields?: array<int, string>,
     *     allowedIncludes?: array<int, string>,
     *     searchableColumns?: array<int, string>,
     *     exactMatchColumns?: array<int, string>,
     * }  $config
     */
    public function configure(array $config = []): static
    {
        $this->allowedFilters = $config['allowedFilters'] ?? [];
        $this->allowedSorts = $config['allowedSorts'] ?? [];
        $this->allowedFields = $config['allowedFields'] ?? [];
        $this->allowedIncludes = $config['allowedIncludes'] ?? [];
        $this->searchableColumns = $config['searchableColumns'] ?? [];
        $this->exactMatchColumns = $config['exactMatchColumns'] ?? [];

        return $this;
    }
}

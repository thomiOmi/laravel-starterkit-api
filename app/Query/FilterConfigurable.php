<?php

declare(strict_types=1);

namespace App\Query;

/**
 * Contract for models that expose query-filter configuration to the custom
 * Eloquent builder. Implementing this interface lets the builder read the
 * configuration with concrete return types instead of dynamic property or
 * method access.
 */
interface FilterConfigurable
{
    /**
     * @return array<int, string>
     */
    public function getAllowedFilters(): array;

    /**
     * @return array<int, string>
     */
    public function getAllowedSorts(): array;

    /**
     * @return array<int, string>
     */
    public function getAllowedFields(): array;

    /**
     * @return array<int, string>
     */
    public function getSearchable(): array;

    public function getFieldsKey(): ?string;
}

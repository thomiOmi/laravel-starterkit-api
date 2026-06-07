<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
abstract class BaseFilter
{
    /**
     * The builder instance.
     *
     * @var Builder<TModel>
     */
    protected Builder $builder; // @phpstan-ignore property.uninitialized

    /**
     * The list of allowed filters.
     *
     * @var array<int, string>
     */
    protected array $allowedFilters = [];

    /**
     * The list of allowed sorts.
     *
     * @var array<int, string>
     */
    protected array $allowedSorts = [];

    /**
     * Create a new QueryFilters instance.
     */
    public function __construct(protected Request $request) {}

    /**
     * Apply the filters to the builder.
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->getFilters() as $name => $value) {
            if (! $this->isFilterAllowed($name)) {
                continue;
            }

            $method = Str::camel((string) $name);

            if ($value !== null && $value !== '' && method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }

        $this->applySorting();

        return $this->builder;
    }

    /**
     * Determine if a filter is allowed.
     */
    protected function isFilterAllowed(string $name): bool
    {
        return empty($this->allowedFilters) || in_array($name, $this->allowedFilters, true);
    }

    /**
     * Apply sorting to the query.
     */
    protected function applySorting(): void
    {
        $sortBy = $this->request->string('sort_by')->trim()->toString();
        $sortDirection = $this->request->string('sort_direction', 'desc')->lower()->toString();

        /** @var 'asc'|'desc' $direction */
        $direction = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc';

        if ($sortBy !== '' && $this->isSortAllowed($sortBy)) {
            $this->builder->orderBy($sortBy, $direction);
        } else {
            $this->builder->latest();
        }
    }

    /**
     * Determine if a sort is allowed.
     */
    protected function isSortAllowed(string $name): bool
    {
        return in_array($name, $this->allowedSorts, true);
    }

    /**
     * Get all applicable filters from the request.
     *
     * @return array<string, mixed>
     */
    protected function getFilters(): array
    {
        /** @var array<string, mixed> */
        return $this->request->except(['sort_by', 'sort_direction', 'page', 'per_page']);
    }
}

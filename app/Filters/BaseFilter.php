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
        foreach ($this->getFilters() as $name => $value) {
            if (! $this->isFilterAllowed($name)) {
                continue;
            }

            $method = Str::camel((string) $name);

            if ($value !== null && $value !== '' && method_exists($this, $method)) {
                $this->{$method}($value, $builder);
            }
        }

        $this->applySorting($builder);

        return $builder;
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
     *
     * @param  Builder<TModel>  $builder
     */
    protected function applySorting(Builder $builder): void
    {
        $sortBy = trim((string) $this->request->query('sort_by', ''));
        $sortDirection = strtolower((string) $this->request->query('sort_direction', 'desc'));

        /** @var 'asc'|'desc' $direction */
        $direction = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc';

        if ($sortBy !== '' && in_array($sortBy, $this->allowedSorts, true)) {
            $builder->orderBy($sortBy, $direction);
        } else {
            $builder->latest();
        }
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

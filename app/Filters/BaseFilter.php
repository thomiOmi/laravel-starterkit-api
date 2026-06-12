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

        $filters = empty($this->allowedFilters)
            ? $this->getFilters()
            : $this->request->only($this->allowedFilters);

        foreach ($filters as $name => $value) {
            $method = Str::camel((string) $name);

            if ($value !== null && $value !== '' && method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }

        $this->applySorting();

        return $this->builder;
    }

    /**
     * Apply sorting to the query.
     */
    protected function applySorting(): void
    {
        $sortBy = trim((string) $this->request->query('sort_by', ''));
        $sortDirection = strtolower((string) $this->request->query('sort_direction', 'desc'));

        /** @var 'asc'|'desc' $direction */
        $direction = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc';

        if ($sortBy !== '' && in_array($sortBy, $this->allowedSorts, true)) {
            $this->builder->orderBy($sortBy, $direction);
        } else {
            $this->builder->orderBy($this->builder->getModel()->getQualifiedKeyName(), 'desc');
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

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
     * The request instance.
     */
    protected Request $request;

    /**
     * The builder instance.
     *
     * @var Builder<TModel>
     */
    protected Builder $builder; // @phpstan-ignore-line

    /**
     * Create a new QueryFilters instance.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

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
        $sortBy = $this->request->string('sort_by')->trim();
        $sortDirection = $this->request->string('sort_direction', 'desc')->lower()->toString();
        /** @var 'asc'|'desc' $direction */
        $direction = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc';

        if ($sortBy->isNotEmpty()) {
            $this->builder->orderBy($sortBy->toString(), $direction);
        } else {
            $this->builder->latest();
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

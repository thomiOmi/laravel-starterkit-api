<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class BaseFilter
{
    /**
     * The request instance.
     */
    protected Request $request;

    /**
     * The builder instance.
     */
    protected Builder $builder;

    /**
     * Create a new QueryFilters instance.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Apply the filters to the builder.
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->getFilters() as $name => $value) {
            $method = Str::camel($name);

            if (method_exists($this, $method)) {
                call_user_func_array([$this, $method], array_filter([$value]));
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
        $sortBy = $this->request->input('sort_by');
        $sortDirection = $this->request->input('sort_direction', 'asc');

        if ($sortBy) {
            $this->builder->orderBy($sortBy, $sortDirection);
        }
    }

    /**
     * Get all applicable filters from the request.
     */
    protected function getFilters(): array
    {
        return $this->request->except(['sort_by', 'sort_direction', 'page', 'per_page']);
    }
}

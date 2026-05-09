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
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->getFilters() as $name => $value) {
            $method = Str::camel($name);

            if (method_exists($this, $method)) {
                /** @var callable $callback */
                $callback = [$this, $method];
                call_user_func_array($callback, array_filter([$value]));
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
        /** @var string|null $sortByInput */
        $sortByInput = $this->request->input('sort_by');
        $sortBy = (string) $sortByInput;

        /** @var string|null $sortDirectionInput */
        $sortDirectionInput = $this->request->input('sort_direction', 'desc');
        /** @var 'asc'|'desc' $sortDirection */
        $sortDirection = strtolower((string) $sortDirectionInput) === 'asc' ? 'asc' : 'desc';

        if ($sortBy !== '') {
            $this->builder->orderBy($sortBy, $sortDirection);
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

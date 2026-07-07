<?php

declare(strict_types=1);

namespace Modules\IAM\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Base query filter for Eloquent models.
 *
 * Provides sparse field selection, full-text search across multiple columns,
 * named filter methods, and multi-column sorting. Extend this class in each
 * module to define allowed filters, sorts, and search behaviour.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
abstract class BaseFilter
{
    /**
     * The query parameter name for the global search term.
     */
    protected string $searchParameter = 'search';

    /**
     * Allowed filter keys. Only these keys from the `filter[...]` query
     * parameter are dispatched to named methods on the filter class.
     *
     * @var array<int, string>
     */
    protected array $allowedFilters = [];

    /**
     * Allowed sort columns. Only these columns are accepted in the `sort`
     * query parameter. Prefix a column with `-` for descending order.
     *
     * @var array<int, string>
     */
    protected array $allowedSorts = [];

    /**
     * Allowed fields for sparse field selection. When defined, only these
     * columns are accepted via the `fields[{resource}]` query parameter.
     * The primary key is always included automatically.
     *
     * @var array<int, string>
     */
    protected array $allowedFields = [];

    /**
     * Custom key used in the `fields[...]` query parameter. Defaults to
     * the model's table name when left null.
     */
    protected ?string $fieldsKey = null;

    /**
     * @param  Request  $request  The current HTTP request.
     */
    public function __construct(protected Request $request) {}

    /**
     * Apply all filter operations to the given query builder.
     *
     * The execution order is: sparse fields, global search, named filters,
     * then sorting. This ensures field selection is applied before any
     * scope-affecting clauses.
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder): Builder
    {
        $builder = $this->handleFields($builder);

        $builder = $this->handleSearch($builder);

        $builder = $this->handleFilters($builder);

        $this->applySorting($builder);

        return $builder;
    }

    /**
     * Apply sparse field selection from the `fields[{resource}]` query
     * parameter. Only columns listed in `allowedFields` are included
     * in the SELECT clause. The model's primary key is always added.
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    protected function handleFields(Builder $builder): Builder
    {
        $key = $this->fieldsKey ?? $builder->getModel()->getTable();

        $fieldsGroup = $this->request->query('fields');
        $fields = is_array($fieldsGroup) ? ($fieldsGroup[$key] ?? null) : null;

        if (! is_string($fields) || $fields === '') {
            return $builder;
        }

        $requested = array_map('trim', explode(',', $fields));
        $requested = array_values(array_filter($requested, fn (string $field): bool => $field !== ''));

        if ($requested === []) {
            return $builder;
        }

        $keyName = $builder->getModel()->getKeyName();

        if ($this->allowedFields !== []) {
            $columns = array_intersect($requested, $this->allowedFields);
        } else {
            $columns = $requested;
        }

        if (! in_array($keyName, $columns, true)) {
            array_unshift($columns, $keyName);
        }

        $builder->select(array_values($columns));

        return $builder;
    }

    /**
     * Read the global search term from the `search` query parameter and
     * delegate to the concrete `search()` method. Empty or non-string
     * values are silently ignored.
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     *
     * @throws InvalidArgumentException When the search parameter is not a string.
     */
    protected function handleSearch(Builder $builder): Builder
    {
        $searchValue = $this->request->query($this->searchParameter);

        if ($searchValue === null) {
            return $builder;
        }

        if (! is_string($searchValue)) {
            throw new InvalidArgumentException(sprintf(
                'Search parameter "%s" must be a string.',
                $this->searchParameter,
            ));
        }

        $searchValue = trim($searchValue);

        if ($searchValue === '') {
            return $builder;
        }

        return $this->search($builder, $searchValue);
    }

    /**
     * Dispatch each entry in the `filter[...]` query parameter to a named
     * method on the filter class. Only keys listed in `allowedFilters`
     * are processed for security. Non-string and empty values are skipped.
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    protected function handleFilters(Builder $builder): Builder
    {
        $filters = $this->request->query('filter');

        if (! is_array($filters)) {
            return $builder;
        }

        $filtersToProcess = $this->allowedFilters !== []
            ? array_intersect_key($filters, array_flip($this->allowedFilters))
            : $filters;

        foreach ($filtersToProcess as $name => $value) {
            if ($name === $this->searchParameter) {
                continue;
            }

            if (! is_string($value) || $value === '') {
                continue;
            }

            $method = Str::camel(strval($name));

            if (method_exists($this, $method)) {
                $result = $this->{$method}($builder, $value);

                if ($result instanceof Builder) {
                    $builder = $result;
                }
            }
        }

        return $builder;
    }

    /**
     * Apply sorting from the `sort` query parameter. Multiple columns
     * are supported as a comma-separated list. Prefix a column with `-`
     * for descending order. Falls back to primary key descending when
     * the parameter is missing, empty, or contains no allowed columns.
     *
     * @param  Builder<TModel>  $builder
     */
    protected function applySorting(Builder $builder): void
    {
        $sortParam = $this->request->query('sort');

        if (! is_string($sortParam) || ($sortParam = trim($sortParam)) === '') {
            $builder->orderBy($builder->getModel()->getQualifiedKeyName(), 'desc');

            return;
        }

        $columns = explode(',', $sortParam);
        $applied = false;

        foreach ($columns as $column) {
            $column = trim($column);

            if ($column === '') {
                continue;
            }

            $direction = 'asc';

            if (str_starts_with($column, '-')) {
                $direction = 'desc';
                $column = substr($column, 1);
            }

            if (in_array($column, $this->allowedSorts, true)) {
                $builder->orderBy($column, $direction);
                $applied = true;
            }
        }

        if (! $applied) {
            $builder->orderBy($builder->getModel()->getQualifiedKeyName(), 'desc');
        }
    }

    /**
     * Build a WHERE clause that matches all search tokens against the
     * given columns using LIKE. Each token must match at least one column
     * (AND between tokens, OR between columns within a token).
     *
     * @param  Builder<TModel>  $builder
     * @param  array<int, string>  $columns  The columns to search in.
     * @return Builder<TModel>
     */
    protected function applySearch(Builder $builder, string $value, array $columns): Builder
    {
        $tokens = $this->tokenizeSearch($value);

        if ($tokens === []) {
            return $builder;
        }

        return $builder->where(function (Builder $query) use ($tokens, $columns): void {
            foreach ($tokens as $token) {
                $query->where(function (Builder $q) use ($token, $columns): void {
                    foreach ($columns as $index => $column) {
                        if ($index === 0) {
                            $q->where($column, 'like', "%{$token}%");
                        } else {
                            $q->orWhere($column, 'like', "%{$token}%");
                        }
                    }
                });
            }
        });
    }

    /**
     * Split a search string into individual tokens, trim whitespace,
     * and escape LIKE wildcards (`%` and `_`) for safe usage.
     *
     * @return array<int, string>
     */
    protected function tokenizeSearch(string $value): array
    {
        $tokens = explode(' ', $value);

        $filtered = array_filter($tokens, fn (string $token): bool => trim($token) !== '');

        return array_map(fn (string $token): string => addcslashes(trim($token), '%_'), array_values($filtered));
    }

    /**
     * Apply a full-text search across the model's searchable columns.
     * Implement this method in each concrete filter to define which
     * columns are searched and how the search term is processed.
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    abstract public function search(Builder $builder, string $value): Builder;
}

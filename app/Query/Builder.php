<?php

declare(strict_types=1);

namespace App\Query;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Custom query builder for Eloquent models.
 *
 * Provides sparse field selection, full-text search across model-defined
 * columns, named filter methods dispatched to local scopes, and multi-column
 * sorting. Attach to a model via the #[UseEloquentBuilder] attribute.
 *
 * @template TModel of Model
 *
 * @extends EloquentBuilder<TModel>
 */
class Builder extends EloquentBuilder
{
    /**
     * Allowed filter keys. Only these keys from the `filter[...]` query
     * parameter are dispatched to a local scope on the model.
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
     * columns are accepted via the `fields[{table}]` query parameter. An
     * empty array disables sparse field selection entirely.
     *
     * @var array<int, string>
     */
    protected array $allowedFields = [];

    /**
     * Columns searched by the global `search` query parameter.
     *
     * @var array<int, string>
     */
    protected array $searchable = [];

    /**
     * Custom key used in the `fields[...]` query parameter. Null falls back
     * to the model's table name.
     */
    protected ?string $fieldsKey = null;

    /**
     * Apply all filter operations to the current query.
     *
     * Order: sparse fields, global search, named filters, then sorting.
     */
    public function filter(Request $request): static
    {
        $this->applyFields($request);
        $this->applySearch($request);
        $this->applyNamedFilters($request);
        $this->applySorting($request);

        return $this;
    }

    /**
     * Resolve allowed filter keys: model property (if declared) > builder property.
     *
     * @return array<int, string>
     */
    protected function allowedFilters(): array
    {
        $model = $this->getModel();

        if (property_exists($model, 'allowedFilters')) {
            /** @var array<int, string> $value */
            $value = $model->allowedFilters;

            return $value;
        }

        return $this->allowedFilters;
    }

    /**
     * Resolve allowed sort columns: model property (if declared) > builder property.
     *
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        $model = $this->getModel();

        if (property_exists($model, 'allowedSorts')) {
            /** @var array<int, string> $value */
            $value = $model->allowedSorts;

            return $value;
        }

        return $this->allowedSorts;
    }

    /**
     * Resolve allowed fields: model property (if declared) > builder property.
     *
     * @return array<int, string>
     */
    protected function allowedFields(): array
    {
        $model = $this->getModel();

        if (property_exists($model, 'allowedFields')) {
            /** @var array<int, string> $value */
            $value = $model->allowedFields;

            return $value;
        }

        return $this->allowedFields;
    }

    /**
     * Resolve searchable columns: model property (if declared) > builder property.
     *
     * @return array<int, string>
     */
    protected function searchableColumns(): array
    {
        $model = $this->getModel();

        if (property_exists($model, 'searchable')) {
            /** @var array<int, string> $value */
            $value = $model->searchable;

            return $value;
        }

        return $this->searchable;
    }

    /**
     * Resolve the fields parameter key: model property (if declared) > builder property.
     */
    protected function fieldsKey(): ?string
    {
        $model = $this->getModel();

        if (property_exists($model, 'fieldsKey')) {
            /** @var string|null $value */
            $value = $model->fieldsKey;

            return $value;
        }

        return $this->fieldsKey;
    }

    /**
     * Apply sparse field selection from the `fields[{table}]` query parameter.
     *
     * When no allowed fields are declared the feature is disabled and the
     * full column set is returned (defence against leaking hidden columns
     * via user-supplied column lists).
     */
    protected function applyFields(Request $request): static
    {
        $allowed = $this->allowedFields();

        if ($allowed === []) {
            return $this;
        }

        $key = $this->fieldsKey() ?? $this->getModel()->getTable();

        $fieldsGroup = $request->query('fields');
        $fields = is_array($fieldsGroup) ? ($fieldsGroup[$key] ?? null) : null;

        if (! is_string($fields) || $fields === '') {
            return $this;
        }

        $requested = array_values(array_filter(
            array_map('trim', explode(',', $fields)),
            fn (string $field): bool => $field !== '',
        ));

        if ($requested === []) {
            return $this;
        }

        $columns = array_intersect($requested, $allowed);

        $keyName = $this->getModel()->getKeyName();

        if (! in_array($keyName, $columns, true)) {
            array_unshift($columns, $keyName);
        }

        $this->select(array_values($columns));

        return $this;
    }

    /**
     * Apply the global search term from the `search` query parameter.
     */
    protected function applySearch(Request $request): static
    {
        $columns = $this->searchableColumns();

        if ($columns === []) {
            return $this;
        }

        $value = $request->query('search');

        if ($value === null || ! is_string($value) || ($value = trim($value)) === '') {
            return $this;
        }

        return $this->applySearchClause($value, $columns);
    }

    /**
     * Dispatch each allowed entry in the `filter[...]` query parameter to a
     * local scope `scopeFilter{Name}` on the model. Keys not in the allowed
     * list and keys without a matching scope are silently ignored.
     *
     * @return $this
     */
    protected function applyNamedFilters(Request $request): self
    {
        $allowed = $this->allowedFilters();

        if ($allowed === []) {
            return $this;
        }

        $filters = $request->query('filter');

        if (! is_array($filters)) {
            return $this;
        }

        $model = $this->getModel();

        foreach ($filters as $name => $value) {
            if (! is_string($name) || ! in_array($name, $allowed, true)) {
                continue;
            }

            if (! is_string($value) || $value === '') {
                continue;
            }

            $method = 'filter'.Str::studly($name);

            if (! $model->hasNamedScope($method)) {
                continue;
            }

            $this->{$method}($value);
        }

        return $this;
    }

    /**
     * Apply sorting from the `sort` query parameter. Multiple columns are
     * supported as a comma-separated list. A `-` prefix denotes descending
     * order. Falls back to the primary key descending when no allowed column
     * is supplied.
     */
    protected function applySorting(Request $request): static
    {
        $allowed = $this->allowedSorts();

        $sortParam = $request->query('sort');

        if (! is_string($sortParam) || ($sortParam = trim($sortParam)) === '') {
            $this->orderBy($this->getModel()->getQualifiedKeyName(), 'desc');

            return $this;
        }

        $applied = false;

        foreach (explode(',', $sortParam) as $column) {
            $column = trim($column);

            if ($column === '') {
                continue;
            }

            $direction = 'asc';

            if (str_starts_with($column, '-')) {
                $direction = 'desc';
                $column = substr($column, 1);
            }

            if (in_array($column, $allowed, true)) {
                $this->orderBy($column, $direction);
                $applied = true;
            }
        }

        if (! $applied) {
            $this->orderBy($this->getModel()->getQualifiedKeyName(), 'desc');
        }

        return $this;
    }

    /**
     * Build a WHERE clause matching all search tokens against the columns
     * using LIKE (AND between tokens, OR between columns within a token).
     *
     * @param  array<int, string>  $columns
     */
    protected function applySearchClause(string $value, array $columns): static
    {
        $tokens = $this->tokenizeSearch($value);

        if ($tokens === []) {
            return $this;
        }

        $this->where(function (EloquentBuilder $query) use ($tokens, $columns): void {
            foreach ($tokens as $token) {
                $query->where(function (EloquentBuilder $q) use ($token, $columns): void {
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

        return $this;
    }

    /**
     * Split a search string into trimmed tokens and escape LIKE wildcards.
     *
     * @return array<int, string>
     */
    protected function tokenizeSearch(string $value): array
    {
        $tokens = array_filter(
            explode(' ', $value),
            fn (string $token): bool => trim($token) !== '',
        );

        return array_values(array_map(
            fn (string $token): string => addcslashes(trim($token), '%_'),
            $tokens,
        ));
    }
}

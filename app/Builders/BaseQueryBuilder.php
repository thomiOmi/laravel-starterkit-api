<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Declarative, whitelist-based query builder for Eloquent models.
 *
 * Register on a model via the `#[UseEloquentBuilder]` attribute and chain
 * the fluent methods in controllers:
 * ```php
 * User::query()
 *     ->with(['roles'])
 *     ->allowedSearch()
 *     ->allowedFilters()
 *     ->allowedSorts()
 *     ->allowedFields()
 *     ->allowedIncludes()
 *     ->paginate();
 * ```
 *
 * **Query param → SQL:**
 * ```
 * ?filter[name]=bob               → WHERE name LIKE '%bob%'
 * ?filter[id]=gt:5                → WHERE id > 5
 * ?filter[name]=in:bob,alice      → WHERE name IN ('bob', 'alice')
 * ?sort=-created_at,name          → ORDER BY created_at DESC, name ASC
 * (no sort)                       → ORDER BY created_at DESC, id DESC
 * ?fields[users]=id,name          → SELECT id, name FROM users
 * ?include=roles,permissions      → ->with(['roles', 'permissions'])
 * ?search=bob                     → WHERE (name LIKE '%bob%' OR email LIKE '%bob%')
 * ?filter[trashed]=only           → only soft-deleted records
 * ```
 *
 * **Extending:**
 * Override `$allowedFilters`, `$allowedSorts`, `$allowedFields`, `$allowedIncludes`,
 * `$searchableColumns`, `$exactMatchColumns`. Add strategy methods (camelCased filter
 * key, single `mixed $value` argument) for complex logic. Model named scopes (e.g.
 * Spatie's `role`) are dispatched automatically when the camelCased key matches.
 * Override `search()` for custom search logic (takes priority over `$searchableColumns`).
 *
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
abstract class BaseQueryBuilder extends Builder
{
    /** @var array<int, string> Columns allowed in `?filter[key]=value`. */
    protected array $allowedFilters = [];

    /** @var array<int, string> Columns allowed in `?sort=-col1,col2`. Empty = unlisted only. */
    protected array $allowedSorts = [];

    /** @var array<int, string> Columns allowed in `?fields[table]=a,b`. Empty = wildcard. */
    protected array $allowedFields = [];

    /** @var array<int, string> Root relation names allowed in `?include=relation(.sub)*`. */
    protected array $allowedIncludes = [];

    /** @var array<int, string> Columns searched by `?search=`. Empty = search disabled. */
    protected array $searchableColumns = [];

    /** @var array<int, string> Columns where string values use exact match instead of LIKE. */
    protected array $exactMatchColumns = [];

    /** Maximum byte length for a single filter value. Values exceeding this are truncated. */
    private const int MAX_FILTER_VALUE_LENGTH = 200;

    /** Maximum byte length for the `?search=` param value. */
    private const int MAX_SEARCH_LENGTH = 100;

    /** @var array<string, string> Map of operator prefix → SQL operator */
    private const array OPERATOR_PREFIXES = [
        'eq:' => '=',
        'neq:' => '!=',
        'gt:' => '>',
        'gte:' => '>=',
        'lt:' => '<',
        'lte:' => '<=',
        'like:' => 'like',
        'in:' => 'in',
    ];

    /**
     * Apply the `?search=` param via `$searchableColumns` or a custom `search()` method.
     *
     * Dispatch priority:
     * 1. Custom `search()` method — for complex search logic.
     * 2. `$searchableColumns` property — auto-generates `LIKE` on listed columns.
     * 3. Neither — search is silently disabled.
     *
     * Values are truncated to `MAX_SEARCH_LENGTH` bytes to prevent heavy
     * LIKE scans on oversized input.
     *
     * @example ?search=bob → calls $this->search('bob')
     * @example ?search=bob, $searchableColumns=['name', 'email']
     *          → WHERE (name LIKE '%bob%' OR email LIKE '%bob%')
     */
    public function allowedSearch(): static
    {
        $value = request()->query('search');

        if (! is_string($value) || $value === '') {
            return $this;
        }

        $value = $this->truncateValue($value, self::MAX_SEARCH_LENGTH);

        if (method_exists($this, 'search')) {
            $this->search($value);
        } elseif ($this->searchableColumns !== []) {
            $this->applySearch($this, $value, $this->searchableColumns);
        }

        return $this;
    }

    /**
     * Iterate `?filter[key]=value` entries and dispatch each to the
     * appropriate handler: strategy method, model named scope, or simple auto-map.
     *
     * Dispatch order:
     * 1. Strategy method on the builder (camelCased filter key) - highest priority.
     * 2. Model named scope with the camelCased filter key - lets Spatie scopes
     *    (e.g. `role` on HasRoles) be reached through the filter API.
     * 3. Auto-map to a column via {@see applyFilterToColumn()}.
     *
     * Unknown keys trigger {@see reportWarning()}.
     *
     * @example ?filter[name]=bob&filter[age]=gt:18
     *          → WHERE name LIKE '%bob%' AND age > 18
     */
    public function allowedFilters(): static
    {
        $filters = request()->query('filter', []);

        if (! is_array($filters)) {
            return $this;
        }

        foreach ($filters as $key => $value) {
            if (! collect($this->allowedFilters)->containsStrict($key)) {
                $this->reportWarning("BaseQueryBuilder: unknown filter key [{$key}] ignored.");

                continue;
            }

            $method = Str::camel($key);

            if (method_exists($this, $method) && ! method_exists(Builder::class, $method)) {
                $this->{$method}($value);

                continue;
            }

            if ($this->hasNamedScope($method)) {
                $this->{$method}($value);

                continue;
            }

            $this->applyFilterToColumn($key, $value);
        }

        return $this;
    }

    /**
     * Apply multi-column sort from `?sort=-created_at,name`.
     *
     * Prefix a column with `-` for descending order, no prefix for ascending.
     * Unknown columns trigger a warning and are skipped.
     * When the `sort` param is absent, defaults to `ORDER BY created_at DESC,
     * PK DESC` for a natural newest-first ordering with stable pagination.
     *
     * @example ?sort=-created_at,name → ORDER BY created_at DESC, name ASC
     * @example no sort param → ORDER BY created_at DESC, id DESC
     */
    public function allowedSorts(): static
    {
        $sortRaw = request()->string('sort')->trim()->toString();

        if ($sortRaw === '') {
            $this->latest($this->getModel()->getCreatedAtColumn() ?? 'created_at')
                ->latest($this->getModel()->getKeyName());

            return $this;
        }

        if ($this->allowedSorts === []) {
            return $this;
        }

        foreach (explode(',', $sortRaw) as $segment) {
            $segment = trim($segment);

            if ($segment === '') {
                continue;
            }

            $column = ltrim($segment, '-');
            $isDesc = str_starts_with($segment, '-');

            if (! collect($this->allowedSorts)->containsStrict($column)) {
                $this->reportWarning("BaseQueryBuilder: unknown sort column [{$column}] ignored.");

                continue;
            }

            $isDesc ? $this->latest($column) : $this->oldest($column);
        }

        return $this;
    }

    /**
     * Apply sparse field selection from `?fields[table]=col1,col2`.
     *
     * Only columns present in `$allowedFields` are kept. The model's
     * primary key is always appended. Unknown fields trigger a warning
     * and are silently dropped.
     *
     * @example ?fields[users]=id,name,email → SELECT id, name, email FROM users
     */
    public function allowedFields(): static
    {
        /** @var array<string, string>|null $fields */
        $fields = request()->query('fields');

        if (! is_array($fields) || $fields === []) {
            return $this;
        }

        $model = $this->getModel();
        $table = $model->getTable();
        $raw = $fields[$table] ?? null;

        if (! is_string($raw) || $raw === '') {
            return $this;
        }

        $requested = explode(',', $raw);
        $unknown = collect($requested)->diff($this->allowedFields)->all();
        $selected = collect($requested)->intersect($this->allowedFields)->all();

        if ($unknown !== []) {
            $this->reportWarning('BaseQueryBuilder: unknown fields ['.collect($unknown)->join(',')."] for table [{$table}] ignored.");
        }

        if ($selected !== []) {
            $selected[] = $model->getKeyName();
            $this->select(collect($selected)->unique()->values()->all());
        }

        return $this;
    }

    /**
     * Apply eager-loading from `?include=relation1,relation2`.
     *
     * Only the root relation must be in `$allowedIncludes`. Nested eager
     * loading via dot-notation (`roles.permissions`) is allowed as long
     * as the top-level relation is whitelisted.
     *
     * @example ?include=roles,permissions       → ->with(['roles', 'permissions'])
     * @example ?include=roles.permissions       → ->with(['roles.permissions'])
     */
    public function allowedIncludes(): static
    {
        $include = request()->query('include');

        if (! is_string($include) || $include === '') {
            return $this;
        }

        $requested = explode(',', $include);
        $relations = [];
        $unknown = [];

        foreach ($requested as $name) {
            $root = str_contains($name, '.') ? explode('.', $name, 2)[0] : $name;

            if (collect($this->allowedIncludes)->containsStrict($root)) {
                $relations[] = $name;
            } else {
                $unknown[] = $name;
            }
        }

        if ($unknown !== []) {
            $this->reportWarning('BaseQueryBuilder: unknown includes ['.collect($unknown)->join(',').'] ignored.');
        }

        if ($relations !== []) {
            $this->with(collect($relations)->values()->all());
        }

        return $this;
    }

    /**
     * Build a grouped `WHERE (col1 LIKE %value% OR col2 LIKE %value%)`.
     *
     * Uses Laravel's `whereAny()` to apply the same constraint across multiple
     * columns with OR logic.
     *
     * @example $this->applySearch($query, 'bob', ['name', 'email'])
     *          → WHERE (name LIKE '%bob%' OR email LIKE '%bob%')
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $columns
     * @return Builder<TModel>
     */
    protected function applySearch(Builder $query, string $value, array $columns): Builder
    {
        $query->whereAny($columns, 'like', "%{$value}%");

        return $query;
    }

    /**
     * Handle `?filter[trashed]=with|only` for soft-deletable models.
     *
     * - `?filter[trashed]=with` — include soft-deleted records
     * - `?filter[trashed]=only` — only soft-deleted records
     *
     * Silently ignored if the model does not use the `SoftDeletes` trait
     * or if the value is invalid. Supports custom `deleted_at` column names.
     *
     * @example ?filter[trashed]=with → include soft-deleted records
     * @example ?filter[trashed]=only → only soft-deleted records
     */
    protected function trashed(mixed $value): void
    {
        if (! is_string($value) || ! in_array($value, ['with', 'only'], true)) {
            return;
        }

        $model = $this->getModel();

        if (! method_exists($model, 'getDeletedAtColumn')) {
            return;
        }

        $this->withoutGlobalScope(SoftDeletingScope::class);

        if ($value === 'only') {
            $column = is_string($model->getDeletedAtColumn()) ? $model->getDeletedAtColumn() : 'deleted_at';

            $this->whereNotNull($model->getTable().'.'.$column);
        }
    }

    /**
     * Map a single filter value to the correct SQL clause based on its type and content.
     *
     * **Dispatch chain:**
     * - Array values → `WHERE IN`
     * - `null` string → `WHERE IS NULL`
     * - `!null` string → `WHERE IS NOT NULL`
     * - `true`/`false` string → boolean exact match
     * - Operator prefixes: `eq:`, `neq:`, `gt:`, `gte:`, `lt:`, `lte:`, `like:`, `in:`
     * - Numeric → exact match
     * - String in `$exactMatchColumns` → exact match
     * - String → `LIKE partial match`
     *
     * String values are truncated to `MAX_FILTER_VALUE_LENGTH`.
     * Numeric comparison operator values (`gt:`, `gte:`, `lt:`, `lte:`)
     * are cast to int/float.
     *
     * @example ['A', 'B'] → WHERE IN ('A', 'B')
     * @example 'null'     → WHERE IS NULL
     * @example '!null'    → WHERE IS NOT NULL
     * @example 'true'     → WHERE = 1
     * @example 'gt:18'    → WHERE > 18 (float)
     * @example 'eq:John'  → WHERE = 'John'
     * @example 'like:Al%' → WHERE LIKE 'Al%'
     * @example 'in:a,b'   → WHERE IN ('a', 'b')
     * @example '42'       → WHERE = 42
     * @example 'bob'      → WHERE LIKE '%bob%'
     */
    private function applyFilterToColumn(string $column, mixed $value): void
    {
        if (is_array($value)) {
            $values = collect($value)
                ->filter(fn (mixed $v): bool => is_string($v) && $v !== '')
                ->values()
                ->all();

            if ($values !== []) {
                $this->whereIn($column, $values);
            }

            return;
        }

        if (! is_string($value) || $value === '') {
            return;
        }

        $value = $this->truncateValue($value);

        if (strtolower($value) === 'null') {
            $this->whereNull($column);

            return;
        }

        if ($value === '!null') {
            $this->whereNotNull($column);

            return;
        }

        if (collect(['true', 'false'])->containsStrict(strtolower($value))) {
            $this->where($column, '=', strtolower($value) === 'true');

            return;
        }

        foreach (self::OPERATOR_PREFIXES as $prefix => $operator) {
            if (! str_starts_with($value, $prefix)) {
                continue;
            }

            $rawValue = substr($value, strlen($prefix));

            if ($prefix === 'in:') {
                $values = collect(explode(',', $rawValue))
                    ->filter(fn (string $v): bool => $v !== '')
                    ->values()
                    ->all();

                if ($values !== []) {
                    $this->whereIn($column, $values);
                }

                return;
            }

            if (collect(['gt:', 'gte:', 'lt:', 'lte:'])->containsStrict($prefix)) {
                $rawValue = is_numeric($rawValue) ? $rawValue + 0 : $rawValue;
            }

            $this->where($column, $operator, $rawValue);

            return;
        }

        if (is_numeric($value)) {
            $this->where($column, '=', $value);
        } elseif (collect($this->exactMatchColumns)->containsStrict($column)) {
            $this->where($column, '=', $value);
        } else {
            $this->where($column, 'like', "%{$value}%");
        }
    }

    /** Truncate a string at the given byte limit. */
    private function truncateValue(string $value, int $max = self::MAX_FILTER_VALUE_LENGTH): string
    {
        return str($value)->substr(0, $max)->toString();
    }

    /**
     * Report a configuration warning.
     *
     * In non-production environments this throws an exception so the developer
     * is notified immediately. In production it degrades to a debug log entry.
     *
     * @throws InvalidArgumentException
     */
    private function reportWarning(string $message): void
    {
        if (app()->isProduction()) {
            Log::debug($message);
        } else {
            throw new InvalidArgumentException($message);
        }
    }
}

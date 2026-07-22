<?php

declare(strict_types=1);

namespace App\Support\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Declarative, whitelist-based query filter for Eloquent models.
 *
 * **Usage in Action:**
 * ```php
 * User::query()
 *     ->with(['roles'])
 *     ->tap(new UserFilter(request()))
 * ```
 *
 * **Query param → SQL:**
 * ```
 * ?filter[name]=bob               → WHERE name LIKE '%bob%'
 * ?filter[id]=gt:5                → WHERE id > 5
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
 * key) for complex logic. Override `search()` for custom search logic (takes priority
 * over `$searchableColumns`).
 *
 * @template TModel of Model
 */
abstract class BaseFilter
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
    ];

    public function __construct(
        protected Request $request
    ) {}

    /**
     * Apply all enabled features (search, filter, sort, fields, includes) to the query.
     *
     * @param  Builder<TModel>  $query
     */
    public function __invoke(Builder $query): void
    {
        $this->applySearchParam($query);
        $this->applyFilters($query);
        $this->applySort($query);
        $this->applyFields($query);
        $this->applyIncludes($query);
    }

    /**
     * Apply the `?search=` param via `$searchableColumns` or custom `search()` method.
     *
     * Dispatch priority:
     * 1. Custom `search()` strategy method — for complex search logic.
     * 2. `$searchableColumns` property — auto-generates `LIKE` on listed columns.
     * 3. Neither — search is silently disabled.
     *
     * Values are truncated to `MAX_SEARCH_LENGTH` bytes to prevent heavy
     * LIKE scans on oversized input.
     *
     * @example ?search=bob → calls $this->search($query, 'bob')
     * @example ?search=bob, $searchableColumns=['name', 'email']
     *          → WHERE (name LIKE '%bob%' OR email LIKE '%bob%')
     *
     * @param  Builder<TModel>  $query
     */
    protected function applySearchParam(Builder $query): void
    {
        $value = $this->request->query('search');

        if (! is_string($value) || $value === '') {
            return;
        }

        $value = $this->truncateValue($value, self::MAX_SEARCH_LENGTH);

        if (method_exists($this, 'search')) {
            $this->search($query, $value);
        } elseif ($this->searchableColumns !== []) {
            $this->applySearch($query, $value, $this->searchableColumns);
        }
    }

    /**
     * Iterate `?filter[key]=value` entries and dispatch each to the
     * appropriate handler: strategy method or simple auto-map.
     *
     * Unknown keys trigger {@see reportWarning()}.
     *
     * @example ?filter[name]=bob&filter[age]=gt:18
     *          → WHERE name LIKE '%bob%' AND age > 18
     *
     * @param  Builder<TModel>  $query
     */
    protected function applyFilters(Builder $query): void
    {
        $filters = $this->request->query('filter', []);

        if (! is_array($filters)) {
            return;
        }

        foreach ($filters as $key => $value) {
            if (! collect($this->allowedFilters)->containsStrict($key)) {
                $this->reportWarning("BaseFilter: unknown filter key [{$key}] ignored.");

                continue;
            }

            $method = Str::camel($key);

            if (method_exists($this, $method)) {
                $this->{$method}($query, $value);

                continue;
            }

            $this->applySimpleFilter($query, $key, $value);
        }
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
     *
     * @param  Builder<TModel>  $query
     */
    protected function applySort(Builder $query): void
    {
        $sortRaw = $this->request->string('sort')->trim()->toString();

        if ($sortRaw === '') {
            $query->latest($query->getModel()->getCreatedAtColumn() ?? 'created_at')
                ->latest($query->getModel()->getKeyName());

            return;
        }

        if (empty($this->allowedSorts)) {
            return;
        }

        foreach (explode(',', $sortRaw) as $segment) {
            $segment = trim($segment);

            if ($segment === '') {
                continue;
            }

            $column = ltrim($segment, '-');
            $isDesc = str_starts_with($segment, '-');

            if (! collect($this->allowedSorts)->containsStrict($column)) {
                $this->reportWarning("BaseFilter: unknown sort column [{$column}] ignored.");

                continue;
            }

            $isDesc ? $query->latest($column) : $query->oldest($column);
        }
    }

    /**
     * Apply sparse field selection from `?fields[table]=col1,col2`.
     *
     * Only columns present in `$allowedFields` are kept. The model's
     * primary key is always appended. Unknown fields trigger a warning
     * and are silently dropped.
     *
     * @example ?fields[users]=id,name,email → SELECT id, name, email FROM users
     *
     * @param  Builder<TModel>  $query
     */
    protected function applyFields(Builder $query): void
    {
        /** @var array<string, string>|null $fields */
        $fields = $this->request->query('fields');

        if (! is_array($fields) || $fields === []) {
            return;
        }

        $model = $query->getModel();
        $table = $model->getTable();
        $raw = $fields[$table] ?? null;

        if (! is_string($raw) || $raw === '') {
            return;
        }

        $requested = explode(',', $raw);
        $unknown = collect($requested)->diff($this->allowedFields)->all();
        $selected = collect($requested)->intersect($this->allowedFields)->all();

        if ($unknown !== []) {
            $this->reportWarning('BaseFilter: unknown fields ['.collect($unknown)->join(',')."] for table [{$table}] ignored.");
        }

        if ($selected !== []) {
            $selected[] = $model->getKeyName();
            $query->select(collect($selected)->unique()->values()->all());
        }
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
     *
     * @param  Builder<TModel>  $query
     */
    protected function applyIncludes(Builder $query): void
    {
        $include = $this->request->query('include');

        if (! is_string($include) || $include === '') {
            return;
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
            $this->reportWarning('BaseFilter: unknown includes ['.collect($unknown)->join(',').'] ignored.');
        }

        if ($relations !== []) {
            $query->with(collect($relations)->values()->all());
        }
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
     *
     * @param  Builder<TModel>  $query
     */
    protected function trashed(Builder $query, mixed $value): void
    {
        if (! is_string($value) || ! in_array($value, ['with', 'only'], true)) {
            return;
        }

        $model = $query->getModel();

        if (! method_exists($model, 'getDeletedAtColumn')) {
            return;
        }

        $query->withoutGlobalScope(SoftDeletingScope::class);

        if ($value === 'only') {
            $column = 'deleted_at';
            $deletedColumn = $model->getDeletedAtColumn();

            if (is_string($deletedColumn)) {
                $column = $deletedColumn;
            }

            $query->whereNotNull($model->getTable().'.'.$column);
        }
    }

    /**
     * Delegate a non-relational, non-strategy filter to {@see applyFilterToColumn()}.
     *
     * @param  Builder<TModel>  $query
     */
    protected function applySimpleFilter(Builder $query, string $key, mixed $value): void
    {
        $this->applyFilterToColumn($query, $key, $value);
    }

    /**
     * Map a single filter value to the correct SQL clause based on its type and content.
     *
     * **Dispatch chain:**
     * - Array values → `WHERE IN`
     * - `null` string → `WHERE IS NULL`
     * - `!null` string → `WHERE IS NOT NULL`
     * - `true`/`false` string → boolean exact match
     * - Operator prefixes: `eq:`, `neq:`, `gt:`, `gte:`, `lt:`, `lte:`, `like:`
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
     * @example '42'       → WHERE = 42
     * @example 'bob'      → WHERE LIKE '%bob%'
     *
     * @param  Builder<*>  $query
     */
    private function applyFilterToColumn(Builder $query, string $column, mixed $value): void
    {
        if (is_array($value)) {
            $values = collect($value)
                ->filter(fn ($v): bool => is_string($v) && $v !== '')
                ->values()
                ->all();

            if ($values !== []) {
                $query->whereIn($column, $values);
            }

            return;
        }

        if (! is_string($value) || $value === '') {
            return;
        }

        $value = $this->truncateValue($value);

        if (strtolower($value) === 'null') {
            $query->whereNull($column);

            return;
        }

        if ($value === '!null') {
            $query->whereNotNull($column);

            return;
        }

        if (collect(['true', 'false'])->containsStrict(strtolower($value))) {
            $query->where($column, '=', strtolower($value) === 'true');

            return;
        }

        foreach (self::OPERATOR_PREFIXES as $prefix => $operator) {
            if (str_starts_with($value, $prefix)) {
                $rawValue = substr($value, strlen($prefix));

                if (collect(['gt:', 'gte:', 'lt:', 'lte:'])->containsStrict($prefix)) {
                    $rawValue = is_numeric($rawValue) ? $rawValue + 0 : $rawValue;
                }

                $query->where($column, $operator, $rawValue);

                return;
            }
        }

        if (is_numeric($value)) {
            $query->where($column, '=', $value);
        } elseif (collect($this->exactMatchColumns)->containsStrict($column)) {
            $query->where($column, '=', $value);
        } else {
            $query->where($column, 'like', "%{$value}%");
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

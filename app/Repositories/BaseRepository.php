<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\DataTableDTO;
use App\Filters\BaseFilter;
use App\Traits\Repositories\HasCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository class providing common data access logic.
 *
 * @template T of \Illuminate\Database\Eloquent\Model
 */
abstract class BaseRepository
{
    use HasCache;

    /**
     * The query builder instance.
     *
     * @var Builder<T>|null
     */
    protected ?Builder $query = null;

    /**
     * Create a new repository instance.
     *
     * @param  T  $model  The model instance.
     */
    public function __construct(protected Model $model)
    {
        $this->newQuery();
    }

    /**
     * Initialize a new query.
     *
     * @return $this
     */
    public function newQuery(): self
    {
        /** @var Builder<T> $query */
        $query = $this->model->newQuery();
        $this->query = $query;

        return $this;
    }

    /**
     * Apply query filters.
     *
     * @param  BaseFilter<T>  $filters
     * @return $this
     */
    public function applyFilter(BaseFilter $filters): self
    {
        if ($this->query === null) {
            $this->newQuery();
        }

        /** @var Builder<T> $query */
        $query = $this->query;

        $this->query = $filters->apply($query);

        return $this;
    }

    /**
     * Get all records.
     *
     * @param  array<int, string>  $columns  The columns to retrieve.
     * @param  array<int, string>|string  $relations  The relations to eager load.
     * @return Collection<int, T>
     */
    public function all(array $columns = ['*'], array|string $relations = []): Collection
    {
        if ($this->query === null) {
            $this->newQuery();
        }

        /** @var Builder<T> $query */
        $query = $this->query;

        /** @var Collection<int, T> $collection */
        $collection = $query->with($relations)->get($columns);

        $this->newQuery();

        return $collection;
    }

    /**
     * Get a paginated list of records.
     *
     * @param  int  $perPage  The number of items per page.
     * @param  array<int, string>  $columns  The columns to retrieve.
     * @param  array<int, string>|string  $relations  The relations to eager load.
     * @return LengthAwarePaginator<int, T>
     */
    public function paginate(int $perPage = 10, array $columns = ['*'], array|string $relations = []): LengthAwarePaginator
    {
        if ($this->query === null) {
            $this->newQuery();
        }

        /** @var Builder<T> $query */
        $query = $this->query;

        /** @var LengthAwarePaginator<int, T> $paginator */
        $paginator = $query->with($relations)->paginate($perPage, $columns);

        $this->newQuery();

        return $paginator;
    }

    /**
     * Get a paginated list of models suitable for a data table.
     *
     * @param  DataTableDTO  $dto  The data table parameters.
     * @param  array<int, string>  $columns  The columns to retrieve.
     * @param  array<int, string>|string  $relations  The relations to eager load.
     * @return LengthAwarePaginator<int, T>
     */
    public function getDataTable(DataTableDTO $dto, array $columns = ['*'], array|string $relations = []): LengthAwarePaginator
    {
        /** @var Builder<T> $query */
        $query = $this->model->newQuery()->with($relations);

        if ($dto->search) {
            $query = $this->applySearch($query, $dto->search);
        }

        if (! empty($dto->filters)) {
            $query = $this->applyFilters($query, $dto->filters);
        }

        if ($dto->sort_by && in_array($dto->sort_by, $this->getSortableColumns(), true)) {
            /** @var 'asc'|'desc' $direction */
            $direction = $dto->sort_direction;
            $query->orderBy($dto->sort_by, $direction);
        }

        /** @var LengthAwarePaginator<int, T> $paginator */
        $paginator = $query->paginate($dto->per_page, $columns, 'page', $dto->page);

        return $paginator;
    }

    /**
     * Apply a search query to the database query.
     *
     * This method should be overridden in child repositories to implement
     * specific search logic for the model.
     *
     * @param  Builder<T>  $query  The query builder.
     * @param  string  $search  The search query.
     * @return Builder<T>
     */
    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query;
    }

    /**
     * Apply column filters to the query.
     *
     * @param  Builder<T>  $query  The query builder.
     * @param  array<string, mixed>  $filters  The filters to apply.
     * @return Builder<T>
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $allowedColumns = $this->getFilterableColumns();

        foreach ($filters as $column => $value) {
            if (in_array($column, $allowedColumns, true) && $value !== null && $value !== '') {
                // Support exact match with '=' prefix
                if (is_string($value) && str_starts_with($value, '=')) {
                    $exactValue = ltrim($value, '=');
                    $query->where($column, $exactValue);
                } elseif (is_bool($value) || is_numeric($value)) {
                    $query->where($column, $value);
                } else {
                    $valueString = is_string($value) ? $value : (string) (is_scalar($value) ? $value : '');
                    $query->where($column, 'like', "%{$valueString}%");
                }
            }
        }

        return $query;
    }

    /**
     * Get the columns that can be filtered.
     *
     * @return array<int, string>
     */
    protected function getFilterableColumns(): array
    {
        return [];
    }

    /**
     * Get the columns that can be sorted.
     *
     * @return array<int, string>
     */
    protected function getSortableColumns(): array
    {
        return [];
    }

    /**
     * Find a record by its ID.
     *
     * @param  string|int  $id  The record ID.
     * @param  array<int, string>  $columns  The columns to retrieve.
     * @param  array<int, string>|string  $relations  The relations to eager load.
     * @return T
     */
    public function findById(string|int $id, array $columns = ['*'], array|string $relations = []): Model
    {
        $cacheKey = "find.{$id}.".md5(serialize($columns).serialize($relations));

        /** @var T */
        return $this->cache($cacheKey, function () use ($id, $columns, $relations) {
            /** @var T */
            return $this->model->with($relations)->findOrFail($id, $columns);
        });
    }

    /**
     * Update or create a record.
     *
     * @param  array<string, mixed>  $attributes  The attributes to find the record.
     * @param  array<string, mixed>  $values  The values to update or create with.
     * @return T
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        /** @var T $model */
        $model = $this->model->newQuery()->updateOrCreate($attributes, $values);
        $this->clearCache();

        return $model;
    }

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $details  The record details.
     * @return T
     */
    public function create(array $details): Model
    {
        /** @var T $model */
        $model = $this->model->newQuery()->create($details);
        $this->clearCache();

        return $model;
    }

    /**
     * Update an existing record.
     *
     * @param  string|int  $id  The record ID.
     * @param  array<string, mixed>  $details  The record details.
     * @return T
     */
    public function update(string|int $id, array $details): Model
    {
        $record = $this->findById($id);
        $record->update($details);

        $this->clearCache();

        /** @var T */
        return $record->refresh();
    }

    /**
     * Delete a record.
     *
     * @param  string|int  $id  The record ID.
     */
    public function delete(string|int $id): bool
    {
        $deleted = $this->findById($id)->delete();
        $this->clearCache();

        return is_bool($deleted) ? $deleted : true;
    }

    /**
     * Perform bulk actions on records.
     *
     * @param  array<int, string|int>  $ids  The record IDs.
     * @param  string  $action  The action to perform (delete, update, restore, forceDelete).
     * @param  array<string, mixed>  $data  The data for update action.
     * @return int The number of affected records.
     */
    public function bulk(array $ids, string $action, array $data = []): int
    {
        /** @var Builder<T> $query */
        $query = $this->model->newQuery()->whereIn($this->model->getKeyName(), $ids);

        $result = match ($action) {
            'delete' => $query->delete(),
            'update' => $query->update($data),
            'restore' => $this->callActionOnQuery($query, 'restore'),
            'forceDelete' => $this->callActionOnQuery($query, 'forceDelete'),
            default => 0,
        };

        if (is_numeric($result) && $result > 0) {
            $this->clearCache();
        }

        return is_numeric($result) ? (int) $result : 0;
    }

    /**
     * Helper to call a method on a query if it exists.
     *
     * @param  Builder<T>  $query
     */
    private function callActionOnQuery(Builder $query, string $method): int
    {
        if (is_callable([$query, $method])) {
            /** @var int */
            return $query->{$method}();
        }

        return 0;
    }
}

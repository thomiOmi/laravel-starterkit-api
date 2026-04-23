<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\DataTableDTO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository class providing common data access logic.
 *
 * @template T of Model
 */
abstract class BaseRepository
{
    /**
     * Create a new repository instance.
     *
     * @param  T  $model  The model instance.
     */
    public function __construct(protected Model $model) {}

    /**
     * Get all records.
     *
     * @param  array  $columns  The columns to retrieve.
     * @param  array  $relations  The relations to eager load.
     * @return Collection<int, T>
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        /** @var Collection<int, T> $collection */
        $collection = $this->model->with($relations)->get($columns);

        return $collection;
    }

    /**
     * Get a paginated list of records.
     *
     * @param  int  $perPage  The number of items per page.
     * @param  array  $columns  The columns to retrieve.
     * @param  array  $relations  The relations to eager load.
     * @return LengthAwarePaginator<T>
     */
    public function paginate(int $perPage = 10, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage, $columns);
    }

    /**
     * Get a paginated list of models suitable for a data table.
     *
     * @param  DataTableDTO  $dto  The data table parameters.
     * @param  array  $columns  The columns to retrieve.
     * @param  array  $relations  The relations to eager load.
     * @return LengthAwarePaginator<T>
     */
    public function getDataTable(DataTableDTO $dto, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->with($relations);

        if ($dto->search) {
            $query = $this->applySearch($query, $dto->search);
        }

        if (! empty($dto->filters)) {
            $query = $this->applyFilters($query, $dto->filters);
        }

        if ($dto->sort_by && in_array($dto->sort_by, $this->getSortableColumns(), true)) {
            $query->orderBy($dto->sort_by, $dto->sort_direction);
        }

        return $query->paginate($dto->per_page, $columns, 'page', $dto->page);
    }

    /**
     * Apply a search query to the database query.
     *
     * This method should be overridden in child repositories to implement
     * specific search logic for the model.
     *
     * @param  Builder  $query  The query builder.
     * @param  string  $search  The search query.
     */
    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query;
    }

    /**
     * Apply column filters to the query.
     *
     * @param  Builder  $query  The query builder.
     * @param  array  $filters  The filters to apply.
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
                    $query->where($column, 'like', "%{$value}%");
                }
            }
        }

        return $query;
    }

    /**
     * Get the columns that can be filtered.
     */
    protected function getFilterableColumns(): array
    {
        return [];
    }

    /**
     * Get the columns that can be sorted.
     */
    protected function getSortableColumns(): array
    {
        return [];
    }

    /**
     * Find a record by its ID.
     *
     * @param  string|int  $id  The record ID.
     * @param  array  $columns  The columns to retrieve.
     * @param  array  $relations  The relations to eager load.
     * @return T
     */
    public function findById(string|int $id, array $columns = ['*'], array $relations = []): Model
    {
        return $this->model->with($relations)->findOrFail($id, $columns);
    }

    /**
     * Create a new record.
     *
     * @param  array  $details  The record details.
     * @return T
     */
    public function create(array $details): Model
    {
        return $this->model->create($details);
    }

    /**
     * Update an existing record.
     *
     * @param  string|int  $id  The record ID.
     * @param  array  $details  The record details.
     * @return T
     */
    public function update(string|int $id, array $details): Model
    {
        $record = $this->findById($id);
        $record->update($details);

        return $record->refresh();
    }

    /**
     * Delete a record.
     *
     * @param  string|int  $id  The record ID.
     */
    public function delete(string|int $id): bool
    {
        return $this->findById($id)->delete();
    }

    /**
     * Perform bulk actions on records.
     *
     * @param  array<int|string>  $ids  The record IDs.
     * @param  string  $action  The action to perform (delete, update, restore, forceDelete).
     * @param  array  $data  The data for update action.
     * @return int The number of affected records.
     */
    public function bulk(array $ids, string $action, array $data = []): int
    {
        /** @var mixed $query */
        $query = $this->model->whereIn($this->model->getKeyName(), $ids);

        return match ($action) {
            'delete' => (int) $query->delete(),
            'update' => (int) $query->update($data),
            'restore' => (int) $query->restore(),
            'forceDelete' => (int) $query->forceDelete(),
            default => 0,
        };
    }
}

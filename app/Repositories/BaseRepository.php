<?php

namespace App\Repositories;

use App\DTOs\DataTableDTO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * Create a new repository instance.
     *
     * @param  Model  $model  The model instance.
     */
    public function __construct(protected Model $model) {}

    /**
     * Get all records.
     *
     * @param  array  $columns  The columns to retrieve.
     * @param  array  $relations  The relations to eager load.
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    /**
     * Get a paginated list of records.
     *
     * @param  int  $perPage  The number of items per page.
     * @param  array  $columns  The columns to retrieve.
     * @param  array  $relations  The relations to eager load.
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage, $columns);
    }

    /**
     * Get a paginated list of models suitable for a data table.
     *
     * @param  DataTableDTO  $dto  The data table parameters.
     * @param  array  $columns  The columns to retrieve.
     * @param  array  $relations  The relations to eager load.
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

        if ($dto->sort_by) {
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
     * @return Builder
     */
    protected function applySearch($query, string $search)
    {
        return $query;
    }

    /**
     * Apply column filters to the query.
     *
     * @param  Builder  $query  The query builder.
     * @param  array  $filters  The filters to apply.
     * @return Builder
     */
    protected function applyFilters($query, array $filters)
    {
        $allowedColumns = $this->getFilterableColumns();

        foreach ($filters as $column => $value) {
            if (in_array($column, $allowedColumns) && $value !== null && $value !== '') {
                $query->where($column, 'like', "%{$value}%");
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
     * Find a record by its ID.
     *
     * @param  string|int  $id  The record ID.
     * @param  array  $columns  The columns to retrieve.
     * @param  array  $relations  The relations to eager load.
     */
    public function findById(string|int $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->findOrFail($id, $columns);
    }

    /**
     * Create a new record.
     *
     * @param  array  $details  The record details.
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
     */
    public function update(string|int $id, array $details): bool
    {
        return $this->findById($id)->update($details);
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
     * Bulk delete records.
     *
     * @param  array  $ids  The record IDs to delete.
     * @return int The number of deleted records.
     */
    public function bulkDelete(array $ids): int
    {
        return $this->model->whereIn($this->model->getKeyName(), $ids)->delete();
    }
}

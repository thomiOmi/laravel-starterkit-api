<?php

namespace App\Repositories;

use App\DTOs\DataTableDTO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
    public function __construct(protected Model $model) {}

    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

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

    public function findById(string|int $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->findOrFail($id, $columns);
    }

    public function create(array $details): Model
    {
        return $this->model->create($details);
    }

    public function update(string|int $id, array $details): bool
    {
        return $this->findById($id)->update($details);
    }

    public function delete(string|int $id): bool
    {
        return $this->findById($id)->delete();
    }
}

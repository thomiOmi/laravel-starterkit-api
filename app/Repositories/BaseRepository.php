<?php

namespace App\Repositories;

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

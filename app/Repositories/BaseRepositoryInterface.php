<?php

namespace App\Repositories;

use App\DTOs\DataTableDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    public function all(array $columns = ['*'], array $relations = []): Collection;

    public function paginate(int $perPage = 10, array $columns = ['*'], array $relations = []): LengthAwarePaginator;

    /**
     * Get a paginated list of models suitable for a data table.
     *
     * @param  DataTableDTO  $dto  The data table parameters.
     * @param  array  $columns  The columns to retrieve.
     * @param  array  $relations  The relations to eager load.
     */
    public function getDataTable(DataTableDTO $dto, array $columns = ['*'], array $relations = []): LengthAwarePaginator;

    public function findById(string|int $id, array $columns = ['*'], array $relations = []): ?Model;

    public function create(array $details): Model;

    public function update(string|int $id, array $details): bool;

    public function delete(string|int $id): bool;
}

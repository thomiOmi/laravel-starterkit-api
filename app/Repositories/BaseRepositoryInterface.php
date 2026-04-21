<?php

namespace App\Repositories;

use App\DTOs\DataTableDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Get all records.
     *
     * @param  array  $columns  The columns to retrieve.
     * @param  array  $relations  The relations to eager load.
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get a paginated list of records.
     *
     * @param  int  $perPage  The number of items per page.
     * @param  array  $columns  The columns to retrieve.
     * @param  array  $relations  The relations to eager load.
     */
    public function paginate(int $perPage = 10, array $columns = ['*'], array $relations = []): LengthAwarePaginator;

    /**
     * Get a paginated list of models suitable for a data table.
     *
     * @param  DataTableDTO  $dto  The data table parameters.
     * @param  array  $columns  The columns to retrieve.
     * @param  array  $relations  The relations to eager load.
     */
    public function getDataTable(DataTableDTO $dto, array $columns = ['*'], array $relations = []): LengthAwarePaginator;

    /**
     * Find a record by its ID.
     *
     * @param  string|int  $id  The record ID.
     * @param  array  $columns  The columns to retrieve.
     * @param  array  $relations  The relations to eager load.
     */
    public function findById(string|int $id, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Create a new record.
     *
     * @param  array  $details  The record details.
     */
    public function create(array $details): Model;

    /**
     * Update an existing record.
     *
     * @param  string|int  $id  The record ID.
     * @param  array  $details  The record details.
     */
    public function update(string|int $id, array $details): bool;

    /**
     * Delete a record.
     *
     * @param  string|int  $id  The record ID.
     */
    public function delete(string|int $id): bool;

    /**
     * Bulk delete records.
     *
     * @param  array  $ids  The record IDs to delete.
     * @return int The number of deleted records.
     */
    public function bulkDelete(array $ids): int;
}

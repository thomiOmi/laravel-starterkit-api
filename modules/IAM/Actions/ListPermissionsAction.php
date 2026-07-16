<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Modules\IAM\Models\Permission;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

final readonly class ListPermissionsAction
{
    /**
     * Handle the action to list permissions with filtering, sorting, and sparse fields.
     *
     * @param  int  $pageSize  The number of items per page.
     * @param  int|null  $page  The page number to retrieve.
     * @return Paginator<int, Permission>
     */
    public function handle(int $pageSize = 20, ?int $page = null): Paginator
    {
        $query = Permission::query();

        $this->applySearch($query, ['name']);

        $permissions = QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::partial('name'),
            )
            ->allowedSorts(AllowedSort::field('name'), AllowedSort::field('created_at'))
            ->allowedFields('id', 'name', 'description', 'created_at', 'updated_at')
            ->defaultSort('-created_at')
            ->paginate($pageSize, ['*'], 'page', $page);

        return $permissions;
    }

    /**
     * Apply a global `search` query parameter as a case-insensitive LIKE across columns.
     *
     * @param  Builder<Permission>  $query
     * @param  array<int, string>  $columns
     */
    private function applySearch(Builder $query, array $columns): void
    {
        $term = Request::query('search');

        if (! is_string($term) || $term === '') {
            return;
        }

        $query->where(function (Builder $inner) use ($columns, $term) {
            foreach ($columns as $column) {
                $inner->orWhere($column, 'like', "%{$term}%");
            }
        });
    }
}

<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Modules\IAM\Filters\UserRoleFilter;
use Modules\IAM\Filters\UserStatusFilter;
use Modules\IAM\Models\User;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

final readonly class ListUsersAction
{
    /**
     * Handle the action to list users with filtering, sorting, and sparse fields.
     *
     * @param  int  $pageSize  The number of items per page.
     * @param  int|null  $page  The page number to retrieve.
     * @return Paginator<int, User>
     */
    public function handle(int $pageSize = 10, ?int $page = null): Paginator
    {
        $query = User::query()
            ->with(['roles:id,name,guard_name', 'roles.permissions:id,name', 'permissions:id,name']);

        $this->applySearch($query, ['name', 'email']);

        $users = QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::custom('role', new UserRoleFilter),
                AllowedFilter::custom('status', new UserStatusFilter),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('email'),
            )
            ->allowedSorts(AllowedSort::field('name'), AllowedSort::field('email'), AllowedSort::field('created_at'))
            ->allowedFields('id', 'name', 'email', 'avatar', 'email_verified_at', 'created_at', 'updated_at', 'deleted_at')
            ->defaultSort('-created_at')
            ->paginate($pageSize, ['*'], 'page', $page);

        return $users;
    }

    /**
     * Apply a global `search` query parameter as a case-insensitive LIKE across columns.
     *
     * @param  Builder<User>  $query
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

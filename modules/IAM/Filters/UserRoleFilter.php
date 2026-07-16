<?php

declare(strict_types=1);

namespace Modules\IAM\Filters;

use Illuminate\Database\Eloquent\Builder;
use Modules\IAM\Models\User;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<User>
 */
final class UserRoleFilter implements Filter
{
    /**
     * Scope a query to users assigned the given role.
     *
     * @param  Builder<User>  $query
     */
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $role = is_string($value) ? $value : '';

        $query->whereRelation('roles', 'name', $role);
    }
}

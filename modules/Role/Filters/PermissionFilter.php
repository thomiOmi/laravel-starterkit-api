<?php

declare(strict_types=1);

namespace Modules\Role\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Modules\Role\Models\Permission;

/**
 * @extends BaseFilter<Permission>
 */
class PermissionFilter extends BaseFilter
{
    protected array $allowedFilters = [
        'guard',
    ];

    protected array $allowedSorts = [
        'name',
        'guard_name',
        'created_at',
    ];

    /**
     * @param  Builder<Permission>  $builder
     * @return Builder<Permission>
     */
    public function search(Builder $builder, string $value): Builder
    {
        return $this->applySearch($builder, $value, ['name', 'guard_name']);
    }

    /**
     * @param  Builder<Permission>  $builder
     * @return Builder<Permission>
     */
    public function guard(Builder $builder, string $value): Builder
    {
        return $builder->where('guard_name', $value);
    }
}

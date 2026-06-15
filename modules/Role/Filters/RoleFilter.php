<?php

declare(strict_types=1);

namespace Modules\Role\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Modules\Role\Models\Role;

/**
 * @extends BaseFilter<Role>
 */
class RoleFilter extends BaseFilter
{
    protected array $allowedFilters = [];

    protected array $allowedSorts = [
        'name',
        'created_at',
    ];

    /**
     * @param  Builder<Role>  $builder
     * @return Builder<Role>
     */
    public function search(Builder $builder, string $value): Builder
    {
        return $this->applySearch($builder, $value, ['name', 'description']);
    }
}

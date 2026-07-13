<?php

declare(strict_types=1);

namespace Modules\IAM\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Modules\IAM\Models\Role;

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

    protected array $allowedFields = [
        'id',
        'name',
        'description',
        'created_at',
        'updated_at',
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

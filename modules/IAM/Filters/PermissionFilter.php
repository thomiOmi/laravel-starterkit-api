<?php

declare(strict_types=1);

namespace Modules\IAM\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Modules\IAM\Models\Permission;

/**
 * @extends BaseFilter<Permission>
 */
class PermissionFilter extends BaseFilter
{
    protected array $allowedFilters = [];

    protected array $allowedSorts = [
        'name',
        'created_at',
    ];

    protected array $allowedFields = [
        'id',
        'name',
        'created_at',
        'updated_at',
    ];

    /**
     * @param  Builder<Permission>  $builder
     * @return Builder<Permission>
     */
    public function search(Builder $builder, string $value): Builder
    {
        return $this->applySearch($builder, $value, ['name']);
    }
}

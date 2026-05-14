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
    /**
     * Filter by search term.
     *
     * @param  string  $value  The search term.
     * @return Builder<Role>
     */
    public function search(string $value): Builder
    {
        /** @var Builder<Role> $builder */
        $builder = $this->builder;

        return $builder->where('name', 'like', "%{$value}%");
    }
}

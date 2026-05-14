<?php

declare(strict_types=1);

namespace Modules\Role\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Modules\Role\Models\Role;

/**
 * @template T of Role
 *
 * @extends BaseFilter<T>
 */
class RoleFilter extends BaseFilter
{
    /**
     * Filter by search term.
     *
     * @param  string  $value  The search term.
     * @return Builder<T>
     */
    public function search(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }
}

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
     * The list of allowed filters.
     *
     * @var array<int, string>
     */
    protected array $allowedFilters = [
        'search',
    ];

    /**
     * The list of allowed sorts.
     *
     * @var array<int, string>
     */
    protected array $allowedSorts = [
        'name',
        'created_at',
    ];

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

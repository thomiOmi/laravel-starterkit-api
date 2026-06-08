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
     * @param  Builder<Role>  $builder
     * @return Builder<Role>
     */
    public function search(string $value, Builder $builder): Builder
    {
        return $builder->where(function (Builder $query) use ($value) {
            /** @var Builder<Role> $query */
            $query->where('name', 'like', "%{$value}%")
                ->orWhere('description', 'like', "%{$value}%");
        });
    }
}

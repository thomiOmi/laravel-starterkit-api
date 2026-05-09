<?php

declare(strict_types=1);

namespace Modules\Role\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Modules\Role\Models\Role;

/**
 * @extends BaseRepository<Role>
 */
class RoleRepository extends BaseRepository
{
    /**
     * Create a new RoleRepository instance.
     *
     * @param  Role  $model  The role model.
     */
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    /**
     * Apply search to the role query.
     *
     * @param  Builder<Role>  $query  The query builder.
     * @param  string  $search  The search query.
     * @return Builder<Role> The updated query builder instance.
     */
    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    /**
     * Get the columns that can be sorted for roles.
     *
     * @return array<int, string> The list of sortable columns.
     */
    protected function getSortableColumns(): array
    {
        return ['name', 'created_at'];
    }
}

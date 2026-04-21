<?php

declare(strict_types=1);

namespace Modules\Role\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

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
     * @param  Builder  $query  The query builder.
     * @param  string  $search  The search query.
     */
    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}

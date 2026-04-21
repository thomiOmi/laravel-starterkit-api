<?php

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
     * @param  Builder  $query
     * @return Builder
     */
    protected function applySearch($query, string $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}

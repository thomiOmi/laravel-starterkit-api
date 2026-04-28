<?php

declare(strict_types=1);

namespace Modules\Tenant\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class TenantFilter extends BaseFilter
{
    /**
     * Filter by search term.
     */
    public function search(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%$value%");
    }
}

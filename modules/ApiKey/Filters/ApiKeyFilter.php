<?php

declare(strict_types=1);

namespace Modules\ApiKey\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class ApiKeyFilter extends BaseFilter
{
    /**
     * Filter by search term.
     */
    public function search(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%$value%");
    }
}

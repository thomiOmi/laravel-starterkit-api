<?php

declare(strict_types=1);

namespace Modules\User\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class UserFilter extends BaseFilter
{
    /**
     * Filter by search term (name or email).
     */
    public function search(string $value): Builder
    {
        return $this->builder->where(function (Builder $query) use ($value) {
            $query->where('name', 'like', "%{$value}%")
                ->orWhere('email', 'like', "%{$value}%");
        });
    }

    /**
     * Filter by role name.
     */
    public function role(string $value): Builder
    {
        return $this->builder->whereHas('roles', function (Builder $query) use ($value) {
            $query->where('name', $value);
        });
    }

    /**
     * Filter by created date range.
     * Expects: ?created_at[from]=2023-01-01&created_at[to]=2023-12-31
     */
    public function createdAt(array $value): Builder
    {
        if (isset($value['from'])) {
            $this->builder->whereDate('created_at', '>=', $value['from']);
        }

        if (isset($value['to'])) {
            $this->builder->whereDate('created_at', '<=', $value['to']);
        }

        return $this->builder;
    }
}

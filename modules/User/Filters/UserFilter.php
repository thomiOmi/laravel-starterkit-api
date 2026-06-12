<?php

declare(strict_types=1);

namespace Modules\User\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Modules\User\Models\User;

/**
 * @extends BaseFilter<User>
 */
class UserFilter extends BaseFilter
{
    /**
     * The list of allowed filters.
     *
     * @var array<int, string>
     */
    protected array $allowedFilters = [
        'search',
        'role',
        'created_at',
    ];

    /**
     * The list of allowed sorts.
     *
     * @var array<int, string>
     */
    protected array $allowedSorts = [
        'name',
        'email',
        'created_at',
    ];

    /**
     * Filter by search term (name or email).
     *
     * @param  string  $value  The search term.
     * @return Builder<User> The updated query builder instance.
     */
    public function search(string $value): Builder
    {
        /** @var Builder<User> $builder */
        $builder = $this->builder;

        return $builder->where(function (Builder $query) use ($value) {
            /** @var Builder<User> $query */
            $query->where('name', 'like', "%{$value}%")
                ->orWhere('email', 'like', "%{$value}%");
        });
    }

    /**
     * Filter by role name.
     *
     * @param  string  $value  The role name.
     * @return Builder<User> The updated query builder instance.
     */
    public function role(string $value): Builder
    {
        /** @var Builder<User> $builder */
        $builder = $this->builder;

        return $builder->whereRelation('roles', 'name', $value);
    }

    /**
     * Filter by created date range.
     * Expects: ?created_at[from]=2023-01-01&created_at[to]=2023-12-31
     *
     * @param  mixed  $value  The date range values.
     * @return Builder<User> The updated query builder instance.
     */
    public function createdAt(mixed $value): Builder
    {
        /** @var Builder<User> $builder */
        $builder = $this->builder;

        if (! is_array($value)) {
            return $builder;
        }

        if (isset($value['from']) && is_string($value['from']) && strtotime($value['from']) !== false) {
            $builder->where('created_at', '>=', $value['from']);
        }

        if (isset($value['to']) && is_string($value['to']) && strtotime($value['to']) !== false) {
            $builder->where('created_at', '<=', (string) Carbon::parse($value['to'])->endOfDay());
        }

        return $builder;
    }
}

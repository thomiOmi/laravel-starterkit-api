<?php

declare(strict_types=1);

namespace Modules\User\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Modules\User\Models\User;

/**
 * @extends BaseFilter<User>
 */
class UserFilter extends BaseFilter
{
    protected array $allowedFilters = [
        'role',
        'status',
    ];

    protected array $allowedSorts = [
        'name',
        'email',
        'created_at',
    ];

    /**
     * @param  Builder<User>  $builder
     * @return Builder<User>
     */
    public function search(Builder $builder, string $value): Builder
    {
        return $this->applySearch($builder, $value, ['name', 'email']);
    }

    /**
     * @param  Builder<User>  $builder
     * @return Builder<User>
     */
    public function role(Builder $builder, mixed $value): Builder
    {
        if (! is_string($value)) {
            return $builder;
        }

        return $builder->whereRelation('roles', 'name', $value);
    }

    /**
     * @param  Builder<User>  $builder
     * @return Builder<User>
     */
    public function status(Builder $builder, mixed $value): Builder
    {
        if (! is_string($value)) {
            return $builder;
        }

        if ($value === 'verified') {
            return $builder->whereNotNull('email_verified_at');
        }

        if ($value === 'unverified') {
            return $builder->whereNull('email_verified_at');
        }

        return $builder;
    }
}

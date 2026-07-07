<?php

declare(strict_types=1);

namespace Modules\IAM\Filters;

use Illuminate\Database\Eloquent\Builder;
use Modules\IAM\Models\User;

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

    protected array $allowedFields = [
        'id',
        'name',
        'email',
        'avatar',
        'email_verified_at',
        'created_at',
        'updated_at',
        'deleted_at',
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
            $builder->whereNotNull('email_verified_at');

            return $builder;
        }

        if ($value === 'unverified') {
            $builder->whereNull('email_verified_at');

            return $builder;
        }

        return $builder;
    }
}

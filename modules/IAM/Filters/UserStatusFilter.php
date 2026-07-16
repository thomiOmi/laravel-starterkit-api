<?php

declare(strict_types=1);

namespace Modules\IAM\Filters;

use Illuminate\Database\Eloquent\Builder;
use Modules\IAM\Models\User;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<User>
 */
final class UserStatusFilter implements Filter
{
    /**
     * Scope a query to users by verification status (verified|unverified).
     *
     * @param  Builder<User>  $query
     */
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        if (! is_string($value)) {
            return;
        }

        if ($value === 'verified') {
            $query->whereNotNull('email_verified_at');

            return;
        }

        if ($value === 'unverified') {
            $query->whereNull('email_verified_at');
        }
    }
}

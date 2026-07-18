<?php

declare(strict_types=1);

namespace Modules\IAM\Filters;

use App\Http\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Modules\IAM\Models\User;

/**
 * @extends BaseFilter<User>
 */
class UserFilter extends BaseFilter
{
    /** @var array<int, string> */
    protected array $allowedFilters = [
        'name',
        'email',
        'role',
        'status',
        'trashed',
    ];

    /** @var array<int, string> */
    protected array $allowedSorts = [
        'name',
        'email',
        'created_at',
    ];

    /** @var array<int, string> */
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

    /** @var array<int, string> */
    protected array $allowedIncludes = [
        'roles',
        'permissions',
    ];

    /** @var array<int, string> */
    protected array $searchableColumns = [
        'name',
        'email',
    ];

    /**
     * @param  Builder<User>  $query
     */
    public function role(Builder $query, mixed $value): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $query->role($value);
    }

    /**
     * @param  Builder<User>  $query
     */
    public function status(Builder $query, mixed $value): void
    {
        if (! is_string($value)) {
            return;
        }

        if ($value === 'verified') {
            $query->whereNotNull('email_verified_at');
        } elseif ($value === 'unverified') {
            $query->whereNull('email_verified_at');
        }
    }
}

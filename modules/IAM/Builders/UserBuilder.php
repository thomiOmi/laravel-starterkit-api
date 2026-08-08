<?php

declare(strict_types=1);

namespace Modules\IAM\Builders;

use App\Builders\BaseQueryBuilder;
use Modules\IAM\Models\User;

/**
 * @extends BaseQueryBuilder<User>
 */
class UserBuilder extends BaseQueryBuilder
{
    /** @var array<int, string> */
    protected array $allowedFilters = [
        'name',
        'email',
        'role',
        'status',
        'created_at',
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

    /** @var array<int, string> */
    protected array $exactMatchColumns = [
        'status',
    ];

    // The `role` filter key is dispatched to the `role` named scope
    // provided by Spatie's HasRoles trait on the User model.
}

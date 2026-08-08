<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\Sanctum\PersonalAccessToken;

/**
 * @extends BaseQueryBuilder<PersonalAccessToken>
 */
class PersonalAccessTokenBuilder extends BaseQueryBuilder
{
    /** @var array<int, string> */
    protected array $allowedFilters = [
        'name',
        'ip_address',
        'last_used_at',
        'created_at',
    ];

    /** @var array<int, string> */
    protected array $allowedSorts = [
        'name',
        'last_used_at',
        'created_at',
    ];

    /** @var array<int, string> */
    protected array $allowedFields = [
        'id',
        'name',
        'ip_address',
        'user_agent',
        'last_used_at',
        'created_at',
        'expires_at',
    ];

    /** @var array<int, string> */
    protected array $allowedIncludes = [];

    /** @var array<int, string> */
    protected array $searchableColumns = [
        'name',
        'ip_address',
    ];
}

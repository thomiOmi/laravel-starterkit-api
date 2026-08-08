<?php

declare(strict_types=1);

namespace Modules\IAM\Builders;

use App\Builders\BaseQueryBuilder;
use Modules\IAM\Models\Permission;

/**
 * @extends BaseQueryBuilder<Permission>
 */
class PermissionBuilder extends BaseQueryBuilder
{
    /** @var array<int, string> */
    protected array $allowedFilters = [
        'name',
    ];

    /** @var array<int, string> */
    protected array $allowedSorts = [
        'name',
        'created_at',
    ];

    /** @var array<int, string> */
    protected array $allowedFields = [
        'id',
        'name',
        'description',
        'created_at',
        'updated_at',
    ];

    /** @var array<int, string> */
    protected array $allowedIncludes = [];

    /** @var array<int, string> */
    protected array $searchableColumns = [
        'name',
    ];
}

<?php

declare(strict_types=1);

namespace Modules\IAM\Filters;

use App\Support\Filters\BaseFilter;
use Modules\IAM\Models\Permission;

/**
 * @extends BaseFilter<Permission>
 */
class PermissionFilter extends BaseFilter
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

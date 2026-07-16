<?php

declare(strict_types=1);

namespace Modules\IAM\Models;

use App\Concerns\HasDefaultBehavior;
use App\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Modules\IAM\Database\Factories\PermissionFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property string $id
 * @property string $name
 * @property string $guard_name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'guard_name',
    'description',
])]
#[Hidden(['guard_name'])]
#[UseFactory(PermissionFactory::class)]
#[UseEloquentBuilder(QueryBuilder::class)]
class Permission extends SpatiePermission
{
    /** @use HasFactory<PermissionFactory> */
    use HasDefaultBehavior, HasFactory;

    /**
     * Filter keys accepted from the `filter[...]` query parameter.
     *
     * @var array<int, string>
     */
    public array $allowedFilters = [];

    /**
     * Columns accepted from the `sort` query parameter.
     *
     * @var array<int, string>
     */
    public array $allowedSorts = [
        'name',
        'created_at',
    ];

    /**
     * Columns accepted from the `fields[permissions]` query parameter.
     *
     * @var array<int, string>
     */
    public array $allowedFields = [
        'id',
        'name',
        'description',
        'created_at',
        'updated_at',
    ];

    /**
     * Columns searched by the global `search` query parameter.
     *
     * @var array<int, string>
     */
    public array $searchable = [
        'name',
    ];

    public ?string $fieldsKey = null;
}

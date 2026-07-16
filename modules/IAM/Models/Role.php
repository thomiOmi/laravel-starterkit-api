<?php

declare(strict_types=1);

namespace Modules\IAM\Models;

use App\Concerns\HasDefaultBehavior;
use App\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Modules\IAM\Database\Factories\RoleFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Permission> $permissions
 */
#[Fillable([
    'name',
    'guard_name',
    'description',
])]
#[Hidden(['guard_name'])]
#[UseFactory(RoleFactory::class)]
#[UseEloquentBuilder(QueryBuilder::class)]
class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
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
     * Columns accepted from the `fields[roles]` query parameter.
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
        'description',
    ];

    public ?string $fieldsKey = null;
}

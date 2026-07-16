<?php

declare(strict_types=1);

namespace Modules\IAM\Models;

use App\Concerns\HasDefaultBehavior;
use App\Query\Builder as QueryBuilder;
use App\Query\FilterConfigurable;
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
class Permission extends SpatiePermission implements FilterConfigurable
{
    /** @use HasFactory<PermissionFactory> */
    use HasDefaultBehavior, HasFactory;

    /**
     * Filter keys accepted from the `filter[...]` query parameter.
     *
     * @var array<int, string>
     */
    protected array $allowedFilters = [];

    /**
     * Columns accepted from the `sort` query parameter.
     *
     * @var array<int, string>
     */
    protected array $allowedSorts = [
        'name',
        'created_at',
    ];

    /**
     * Columns accepted from the `fields[permissions]` query parameter.
     *
     * @var array<int, string>
     */
    protected array $allowedFields = [
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
    protected array $searchable = [
        'name',
    ];

    protected ?string $fieldsKey = null;

    /**
     * @return array<int, string>
     */
    public function getAllowedFilters(): array
    {
        return $this->allowedFilters;
    }

    /**
     * @return array<int, string>
     */
    public function getAllowedSorts(): array
    {
        return $this->allowedSorts;
    }

    /**
     * @return array<int, string>
     */
    public function getAllowedFields(): array
    {
        return $this->allowedFields;
    }

    /**
     * @return array<int, string>
     */
    public function getSearchable(): array
    {
        return $this->searchable;
    }

    public function getFieldsKey(): ?string
    {
        return $this->fieldsKey;
    }
}

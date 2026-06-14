<?php

declare(strict_types=1);

namespace Modules\Role\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Modules\Role\Models\Permission;

/**
 * @extends BaseFilter<Permission>
 */
class PermissionFilter extends BaseFilter
{
    /** @var array<int, string> */
    protected array $allowedFilters = [
        'guard',
    ];

    /** @var array<int, string> */
    protected array $allowedSorts = [
        'name',
        'guard_name',
        'created_at',
    ];

    /**
     * @param  Builder<Permission>  $builder
     * @return Builder<Permission>
     */
    public function search(Builder $builder, string $value): Builder
    {
        $tokens = $this->tokenizeSearch($value);

        if ($tokens === []) {
            return $builder;
        }

        return $builder->where(function (Builder $query) use ($tokens): void {
            foreach ($tokens as $token) {
                $query->where(function (Builder $q) use ($token): void {
                    $q->where('name', 'like', "%{$token}%")
                        ->orWhere('guard_name', 'like', "%{$token}%");
                });
            }
        });
    }

    /**
     * @param  Builder<Permission>  $builder
     * @return Builder<Permission>
     */
    public function guard(Builder $builder, mixed $value): Builder
    {
        if (! is_string($value)) {
            return $builder;
        }

        return $builder->where('guard_name', $value);
    }
}

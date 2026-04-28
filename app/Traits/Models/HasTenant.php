<?php

declare(strict_types=1);

namespace App\Traits\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Models\Tenant;

/**
 * Trait HasTenant
 */
trait HasTenant
{
    /**
     * Boot the trait.
     */
    protected static function bootHasTenant(): void
    {
        static::creating(function (Model $model) {
            if (! $model->getAttribute('tenant_id') && tenancy()->initialized) {
                $model->setAttribute('tenant_id', tenant('id'));
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenancy()->initialized) {
                $builder->where($builder->getQuery()->from.'.tenant_id', tenant('id'));
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}

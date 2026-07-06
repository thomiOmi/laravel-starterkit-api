<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Trait providing default behaviors for models, driven by `config/architecture.php`.
 *
 * - ID strategy: ulid (default), uuid, or integer
 * - Soft deletes: respects `architecture.model.use_soft_deletes`
 * - Date serialization: Y-m-d H:i:s
 */
trait HasDefaultBehavior
{
    use HasUlids, SoftDeletes;

    /**
     * Initialize the trait based on architecture config.
     */
    public function initializeHasDefaultBehavior(): void
    {
        $idStrategy = config()->string('architecture.model.default_id', 'ulid');

        if ($idStrategy === 'integer') {
            $this->keyType = 'int';
            $this->incrementing = true;
        } else {
            $this->keyType = 'string';
            $this->incrementing = false;
        }
    }

    /**
     * Override SoftDeletes initialization to respect config.
     */
    public function initializeSoftDeletes(): void
    {
        if (! config()->boolean('architecture.model.use_soft_deletes', true)) {
            return;
        }

        $this->mergeCasts([$this->getDeletedAtColumn() => 'datetime']);
    }

    /**
     * Override SoftDeletes boot to respect config.
     */
    public static function bootSoftDeletes(): void
    {
        if (! config()->boolean('architecture.model.use_soft_deletes', true)) {
            return;
        }

        static::addGlobalScope(new SoftDeletingScope);
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

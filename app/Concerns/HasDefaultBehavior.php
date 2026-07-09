<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Trait providing default behaviors for models, driven by `config/architecture.php`.
 *
 * - ID strategy: ulid (default), uuid, or integer
 * - Date serialization: Y-m-d H:i:s
 */
trait HasDefaultBehavior
{
    use HasUlids;

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
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

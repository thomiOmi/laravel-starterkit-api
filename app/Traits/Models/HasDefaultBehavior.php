<?php

declare(strict_types=1);

namespace App\Traits\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Trait providing default behaviors for models, including ULIDs and Soft Deletes.
 */
trait HasDefaultBehavior
{
    use HasUlids, SoftDeletes;

    /**
     * Initialize the trait.
     * Sets default properties for models using ULIDs.
     */
    public function initializeHasDefaultBehavior(): void
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }
}

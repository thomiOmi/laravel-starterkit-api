<?php

namespace App\Traits\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasDefaultBehavior
{
    use HasUlids, SoftDeletes;

    /**
     * Initialize the trait.
     * Sets default properties for models using ULIDs.
     *
     * @return void
     */
    public function initializeHasDefaultBehavior()
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }

    /**
     * Standardize date formats for API responses.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
            'deleted_at' => 'datetime:Y-m-d H:i:s',
        ];
    }
}

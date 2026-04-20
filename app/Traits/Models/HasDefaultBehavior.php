<?php

namespace App\Traits\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasDefaultBehavior
{
    use HasUlids, SoftDeletes;

    /**
     * Set default properti untuk ULID
     */
    public function initializeHasDefaultBehavior()
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }

    /**
     * Standarisasi format tanggal untuk API response
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

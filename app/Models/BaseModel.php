<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Models\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Model class for all models in the application.
 * Uses ULIDs and common behaviors.
 */
abstract class BaseModel extends Model
{
    use HasDefaultBehavior;
}

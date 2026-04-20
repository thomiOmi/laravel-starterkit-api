<?php

namespace App\Models;

use App\Traits\Models\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use HasDefaultBehavior;
}

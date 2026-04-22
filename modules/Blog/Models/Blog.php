<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

use App\Traits\Models\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\User\Models\User;

class Blog extends Model
{
    use HasDefaultBehavior, HasFactory;

    protected $fillable = [
        'title',
        'content',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

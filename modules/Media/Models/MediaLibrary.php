<?php

declare(strict_types=1);

namespace Modules\Media\Models;

use App\Traits\Models\HasDefaultBehavior;
use App\Traits\Models\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibrary extends Model implements HasMedia
{
    use HasDefaultBehavior, HasTenant, InteractsWithMedia;

    protected $table = 'media_libraries';

    protected $fillable = [
        'tenant_id',
        'name',
    ];

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10);

        $this->addMediaConversion('preview')
            ->width(400)
            ->height(400);
    }
}

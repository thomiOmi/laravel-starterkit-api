<?php

declare(strict_types=1);

namespace Modules\Media\Repositories;

use App\Repositories\BaseRepository;
use Modules\Media\Models\Media;

/**
 * @extends BaseRepository<Media>
 */
class MediaRepository extends BaseRepository
{
    public function __construct(Media $model)
    {
        parent::__construct($model);
    }

    public function model(): string
    {
        return Media::class;
    }
}

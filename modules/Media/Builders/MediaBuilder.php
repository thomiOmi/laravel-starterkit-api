<?php

declare(strict_types=1);

namespace Modules\Media\Builders;

use App\Builders\BaseQueryBuilder;
use Modules\Media\Models\Media;

/**
 * @extends BaseQueryBuilder<Media>
 */
class MediaBuilder extends BaseQueryBuilder
{
    /** @var array<int, string> */
    protected array $allowedFilters = [
        'mime_type',
        'disk',
        'size',
        'created_at',
    ];

    /** @var array<int, string> */
    protected array $allowedSorts = [
        'mime_type',
        'size',
        'created_at',
    ];

    /** @var array<int, string> */
    protected array $allowedFields = [
        'id',
        'disk',
        'mime_type',
        'size',
        'meta',
        'uploaded_by',
        'created_at',
        'updated_at',
    ];

    /** @var array<int, string> */
    protected array $allowedIncludes = [
        'uploadedBy',
    ];

    /** @var array<int, string> */
    protected array $searchableColumns = [
    ];
}

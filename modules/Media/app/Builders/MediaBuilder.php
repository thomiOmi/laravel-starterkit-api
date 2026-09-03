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
        'collection_name',
    ];

    /** @var array<int, string> */
    protected array $allowedSorts = [
        'created_at',
        'size',
        'order_column',
    ];

    /** @var array<int, string> */
    protected array $allowedFields = [
        'id',
        'collection_name',
        'disk',
        'mime_type',
        'size',
        'file_name',
        'name',
        'visibility',
        'order_column',
        'created_at',
    ];

    /** @var array<int, string> */
    protected array $allowedIncludes = [];

    /** @var array<int, string> */
    protected array $searchableColumns = [
        'collection_name',
    ];

    /** @var array<int, string> */
    protected array $exactMatchColumns = [
        'collection_name',
    ];
}

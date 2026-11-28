<?php

declare(strict_types=1);

namespace Modules\Media\Builders;

use App\Builders\BaseQueryBuilder;
use Modules\Media\Models\Media;
use Override;

/**
 * @extends BaseQueryBuilder<Media>
 */
class MediaBuilder extends BaseQueryBuilder
{
    /**
     * Columns the resource and its eager-loaded relations need regardless of
     * the requested fields. Sparse selects stay a minimum, not a contract
     * that every other response attribute disappears.
     *
     * @var list<string>
     */
    private const array RESOURCE_FIELDS = [
        'name', 'file_name', 'collection_name', 'mime_type', 'size',
        'visibility', 'disk', 'conversions_disk', 'original_name',
        'original_extension', 'sha256', 'custom_properties', 'processing_status',
        'processing_error', 'processed_at', 'order_column', 'uploaded_by_type',
        'uploaded_by_id', 'model_type', 'model_id', 'responsive_images', 'meta',
        'created_at', 'updated_at',
    ];

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

    /**
     * Keep the resource and morph/conversion eager loads working under
     * `?fields[media]=...`: the primary key is added by the parent, and the
     * remaining columns needed by MediaResource are always selected as well.
     */
    #[Override]
    public function allowedFields(): static
    {
        parent::allowedFields();

        $fields = request()->query('fields');
        $raw = is_array($fields) ? ($fields[$this->getModel()->getTable()] ?? null) : null;

        if (is_string($raw) && $raw !== '') {
            $this->addSelect(self::RESOURCE_FIELDS);
        }

        return $this;
    }
}

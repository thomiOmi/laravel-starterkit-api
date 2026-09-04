<?php

declare(strict_types=1);

namespace Modules\Media\Http\Resources;

use App\Concerns\FormatDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Models\Media;

/**
 * @property Media $resource
 *
 * @mixin Media
 */
class MediaResource extends JsonResource
{
    use FormatDate;

    /**
     * @param  Media  $resource
     * @param  string|null  $resolvedUrl  Pre-resolved override (e.g. a signed
     *                                    link) taking precedence over the
     *                                    default visibility-based url.
     */
    public function __construct($resource, private ?string $resolvedUrl = null)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * The url is only resolved for publicly visible media; private media
     * yields null until a caller explicitly passes a pre-resolved url such
     * as a temporary signed link.
     *
     * @return array{id: string, name: string, file_name: string, collection_name: string, mime_type: string, size: int, visibility: string, url: string|null, original_name: string|null, original_extension: string|null, sha256: string|null, custom_properties: array<string, mixed>|null, order_column: int, uploaded_by_type: string|null, uploaded_by_id: string|null, model_type: string|null, model_id: string|null, conversions: array<string, string|null>, srcset: string|null, fallback_url: string|null, fallback_path: string|null, created_at: ?string}
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        /** @var Media $media */
        $media = $this->resource;

        $conversions = [];

        foreach ($media->conversions()->get() as $conv) {
            $conversions[$conv->name] = $media->url($conv->name);
        }

        // Fallback handling: if model is available via morph, try to get fallback
        $fallbackUrl = null;
        $fallbackPath = null;

        if ($media->model_type !== null && $media->model_id !== null) {
            $modelClass = $media->model_type;

            if ($modelClass !== '' && class_exists($modelClass) && is_a($modelClass, Model::class, true)) {
                /** @var class-string<Model> $modelClass */
                $model = $modelClass::query()->whereKey($media->model_id)->first();

                if ($model instanceof HasMedia) {
                    $collection = $model->getMediaCollection($media->collection_name);
                    $fallbackUrl = $collection?->fallbackUrl;
                    $fallbackPath = $collection?->fallbackPath;
                }
            }
        }

        return [
            'id' => $media->id,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'collection_name' => $media->collection_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'visibility' => $media->visibility->value,
            'url' => $this->resolvedUrl ?? $media->url() ?? $fallbackUrl,
            'original_name' => $media->original_name ?? (is_array($media->meta) && is_string($media->meta['original_name'] ?? null)
                ? $media->meta['original_name']
                : null),
            'original_extension' => $media->original_extension,
            'sha256' => $media->sha256,
            'custom_properties' => $media->custom_properties,
            'order_column' => $media->order_column,
            'uploaded_by_type' => $media->uploaded_by_type,
            'uploaded_by_id' => $media->uploaded_by_id,
            'model_type' => $media->model_type,
            'model_id' => $media->model_id,
            'conversions' => $conversions,
            'srcset' => $media->getSrcset(),
            'fallback_url' => $fallbackUrl,
            'fallback_path' => $fallbackPath,
            'created_at' => $this->formatDate($media->created_at),
        ];
    }
}

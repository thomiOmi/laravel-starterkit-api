<?php

declare(strict_types=1);

namespace Modules\Media\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Media\Models\Media;
use Modules\Media\Requests\V1\ListMediaRequest;
use Modules\Media\Resources\MediaResource;

final readonly class MediaListController extends Controller
{
    /**
     * Display a paginated listing of the media.
     *
     * @return SuccessResponse<AnonymousResourceCollection>
     */
    public function __invoke(ListMediaRequest $request): SuccessResponse
    {
        $media = Media::query()
            ->allowedSearch()
            ->allowedFilters()
            ->allowedSorts()
            ->allowedFields()
            ->allowedIncludes()
            ->paginate(
                perPage: $request->getPerPage(),
                page: $request->getPage(),
            );

        return new SuccessResponse(
            data: MediaResource::collection($media),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Media']),
        );
    }
}

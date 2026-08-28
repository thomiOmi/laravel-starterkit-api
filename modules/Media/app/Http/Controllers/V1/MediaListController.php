<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Models\User;
use Modules\Media\Http\Requests\V1\MediaListRequest;
use Modules\Media\Http\Resources\MediaResource;
use Modules\Media\Models\Media;

final readonly class MediaListController extends Controller
{
    /**
     * Display a paginated listing of the authenticated user's media.
     *
     * @return SuccessResponse<AnonymousResourceCollection>
     */
    public function __invoke(MediaListRequest $request, #[CurrentUser] User $currentUser): SuccessResponse
    {
        $media = Media::query()
            ->select([
                'id',
                'collection_name',
                'mime_type',
                'size',
                'visibility',
                'disk',
                'path',
                'meta',
                'uploaded_by',
                'created_at',
            ])
            ->where('uploaded_by', $currentUser->id)
            ->allowedSearch()
            ->allowedFilters()
            ->allowedSorts()
            ->allowedFields()
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

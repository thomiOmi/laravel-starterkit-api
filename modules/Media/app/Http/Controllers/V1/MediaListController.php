<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers\V1;

use App\Contracts\Identity;
use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
    public function __invoke(MediaListRequest $request, #[CurrentUser] Identity $currentUser): SuccessResponse
    {
        abort_unless($currentUser instanceof Model, 500, 'Invalid user model');

        $media = Media::query()
            ->where('model_type', $currentUser->getMorphClass())
            ->where('model_id', $currentUser->getKey())
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

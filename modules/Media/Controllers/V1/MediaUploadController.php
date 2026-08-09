<?php

declare(strict_types=1);

namespace Modules\Media\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Modules\Media\Actions\UploadMediaAction;
use Modules\Media\Requests\V1\MediaUploadRequest;
use Modules\Media\Resources\MediaResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class MediaUploadController extends Controller
{
    public function __construct(
        private UploadMediaAction $uploadMedia,
    ) {}

    /**
     * Store a newly uploaded media.
     *
     * @return SuccessResponse<MediaResource>
     */
    public function __invoke(MediaUploadRequest $request): SuccessResponse
    {
        $media = $this->uploadMedia->handle($request->payload());

        return new SuccessResponse(
            data: new MediaResource($media),
            title: 'Created',
            detail: __('general.resource_created', ['resource' => 'Media']),
            status: Response::HTTP_CREATED,
        );
    }
}

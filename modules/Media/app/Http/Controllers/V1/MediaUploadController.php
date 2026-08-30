<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers\V1;

use App\Contracts\Identity;
use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Model;
use Modules\Media\Actions\UploadMediaAction;
use Modules\Media\Http\Requests\V1\MediaUploadRequest;
use Modules\Media\Http\Resources\MediaResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class MediaUploadController extends Controller
{
    public function __construct(
        private UploadMediaAction $uploadMedia
    ) {}

    /**
     * Upload a media file for the authenticated user.
     *
     * @return SuccessResponse<array{media: MediaResource, url: string|null}>
     */
    public function __invoke(MediaUploadRequest $request, #[CurrentUser] Identity $currentUser): SuccessResponse
    {
        abort_unless($currentUser instanceof Model, Response::HTTP_INTERNAL_SERVER_ERROR, 'Invalid user model');

        $result = $this->uploadMedia->handle($request->payload(), $currentUser, $currentUser);

        return new SuccessResponse(
            data: [
                'media' => new MediaResource($result['media']),
                'url' => $result['url'],
            ],
            title: __('general.resource_created', ['resource' => 'Media']),
            detail: __('general.resource_created', ['resource' => 'Media']),
            status: Response::HTTP_CREATED,
        );
    }
}

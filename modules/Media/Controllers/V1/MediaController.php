<?php

declare(strict_types=1);

namespace Modules\Media\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Media\Actions\DeleteMediaAction;
use Modules\Media\Actions\UploadMediaAction;
use Modules\Media\Repositories\MediaRepository;
use Modules\Media\Resources\MediaResource;

/**
 * @tags Media
 */
class MediaController extends Controller
{
    /**
     * List Media
     *
     * Get the list of uploaded media.
     */
    public function index(Request $request, MediaRepository $repository): JsonResponse
    {
        $media = $repository->paginate($request->integer('per_page', 15));

        return $this->successResponse(
            MediaResource::collection($media),
            'Media list retrieved successfully'
        );
    }

    /**
     * Upload Media
     *
     * Upload a new media file.
     */
    public function store(Request $request, UploadMediaAction $action): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
            'collection' => 'nullable|string',
        ]);

        $media = $action->execute($request->file('file'), $request->string('collection', 'default')->toString());

        return $this->successResponse(
            new MediaResource($media),
            'Media uploaded successfully'
        );
    }

    /**
     * Delete Media
     *
     * Delete a media file.
     */
    public function destroy(string $id, DeleteMediaAction $action): JsonResponse
    {
        $action->execute($id);

        return $this->successResponse(null, 'Media deleted successfully');
    }
}

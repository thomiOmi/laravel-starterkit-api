<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams a media file to whoever holds a valid signed URL.
 *
 * The route is public: the signature is the credential. Access is granted
 * regardless of the media visibility until the link expires.
 */
final readonly class MediaFileController extends Controller
{
    /**
     * Stream the stored media file.
     */
    public function __invoke(Media $media): Response
    {
        $disk = Storage::disk($media->disk);
        $path = $media->getPath();

        abort_unless(is_string($path) && $disk->exists($path), 404, __('general.resource_not_found', ['resource' => 'File']));

        return $disk->response($path);
    }
}
